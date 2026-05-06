<?php

declare(strict_types=1);

namespace Ciencuadras\Sdk\Schema;

final class ListingPayloadValidator
{
    public function __construct(private readonly SchemaCatalog $catalog)
    {
    }

    /**
     * @param array<int, array<string, mixed>> $listings
     */
    public function validateCreatePayload(array $listings, bool $strict = false): void
    {
        $this->validateRequiredFields('create listing', $listings, $this->catalog->listingCreateRequiredFields());

        if ($strict) {
            $this->validateStrictSchema($listings, $this->catalog->listingCreateItemSchema());
        }
    }

    /**
     * @param array<int, array<string, mixed>> $listings
     */
    public function validateUpdatePayload(array $listings, bool $strict = false): void
    {
        $this->validateRequiredFields('update listing', $listings, $this->catalog->listingUpdateRequiredFields());

        if ($strict) {
            $this->validateStrictSchema($listings, $this->catalog->listingUpdateItemSchema());
        }
    }

    /**
     * @param array<int, array<string, mixed>> $statuses
     */
    public function validateStatusPayload(array $statuses, bool $strict = false): void
    {
        $this->validateRequiredFields('listing status', $statuses, $this->catalog->listingStatusRequiredFields());

        if ($strict) {
            $this->validateStrictSchema($statuses, $this->catalog->listingStatusItemSchema());
        }
    }

    // ---------------------------------------------------------------
    // Validación básica (requeridos de primer nivel)
    // ---------------------------------------------------------------

    /**
     * @param array<int, array<string, mixed>> $listings
     * @param list<string> $requiredFields
     */
    private function validateRequiredFields(string $payloadName, array $listings, array $requiredFields): void
    {
        if ($listings === []) {
            throw new \InvalidArgumentException(\sprintf('At least one %s item is required.', $payloadName));
        }

        foreach ($listings as $index => $listing) {
            if (!\is_array($listing)) {
                throw new \InvalidArgumentException(\sprintf('Payload item at index %d must be an object/array.', $index));
            }

            foreach ($requiredFields as $field) {
                if (!\array_key_exists($field, $listing)) {
                    throw new \InvalidArgumentException(\sprintf(
                        'Missing required field `%s` in %s payload at index %d.',
                        $field,
                        $payloadName,
                        $index
                    ));
                }
            }
        }
    }

    // ---------------------------------------------------------------
    // Validación estricta (enums, tipos y requeridos anidados)
    // ---------------------------------------------------------------

    /**
     * Valida cada item contra el schema `items` del OpenAPI:
     *   - Tipos primitivos (`type` en la propiedad)
     *   - Valores de enum (`enum` en la propiedad)
     *   - Campos requeridos en objetos anidados (`properties` con `required`)
     *
     * @param array<int, array<string, mixed>> $items
     * @param array<string, mixed> $itemSchema Schema OpenAPI del objeto (nivel `items`).
     */
    private function validateStrictSchema(array $items, array $itemSchema): void
    {
        $properties = \is_array($itemSchema['properties'] ?? null) ? $itemSchema['properties'] : [];

        foreach ($items as $index => $item) {
            if (!\is_array($item)) {
                continue; // ya detectado en validateRequiredFields
            }

            foreach ($item as $field => $value) {
                $propSchema = $properties[(string) $field] ?? null;
                if (!\is_array($propSchema) || $propSchema === []) {
                    continue; // campo no definido en el schema, se omite
                }

                $this->validatePropertyValue((string) $field, $value, $propSchema, $index);
            }

            // Requeridos en objetos anidados que estén presentes en el item
            foreach ($properties as $propName => $propSchema) {
                if (!\is_array($propSchema) || !\array_key_exists($propName, $item)) {
                    continue;
                }

                $nestedValue = $item[$propName];

                if (\is_array($nestedValue) && isset($propSchema['properties'])) {
                    $this->validateNestedRequired((string) $propName, $nestedValue, $propSchema, $index);
                }
            }
        }
    }

    /**
     * Valida tipo y enum de un valor escalar o array contra su propiedad schema.
     *
     * @param mixed $value
     * @param array<string, mixed> $propSchema
     */
    private function validatePropertyValue(string $field, mixed $value, array $propSchema, int $index): void
    {
        // Validar tipo
        if (isset($propSchema['type']) && $value !== null) {
            $expectedType = (string) $propSchema['type'];
            $actualType = \gettype($value);

            $typeMatch = match ($expectedType) {
                'string'  => \is_string($value),
                'integer' => \is_int($value),
                'number'  => \is_int($value) || \is_float($value),
                'boolean' => \is_bool($value),
                'array'   => \is_array($value),
                'object'  => \is_array($value), // OpenAPI objects arrive as PHP arrays
                default   => true,
            };

            if (!$typeMatch) {
                throw new \InvalidArgumentException(\sprintf(
                    'Field `%s` at index %d must be of type %s, got %s.',
                    $field,
                    $index,
                    $expectedType,
                    $actualType,
                ));
            }
        }

        // Validar enum (solo para escalares)
        if (isset($propSchema['enum']) && \is_array($propSchema['enum']) && !\is_array($value)) {
            if (!\in_array($value, $propSchema['enum'], strict: true)) {
                throw new \InvalidArgumentException(\sprintf(
                    'Field `%s` at index %d has invalid value %s. Allowed: %s.',
                    $field,
                    $index,
                    \json_encode($value),
                    \implode(', ', \array_map(\json_encode(...), $propSchema['enum'])),
                ));
            }
        }
    }

    /**
     * Verifica que los campos `required` de un objeto anidado estén presentes.
     *
     * @param array<string, mixed> $nestedValue
     * @param array<string, mixed> $propSchema
     */
    private function validateNestedRequired(string $parentField, array $nestedValue, array $propSchema, int $index): void
    {
        $required = \is_array($propSchema['required'] ?? null) ? $propSchema['required'] : [];

        foreach ($required as $subField) {
            if (!\is_string($subField)) {
                continue;
            }

            if (!\array_key_exists($subField, $nestedValue)) {
                throw new \InvalidArgumentException(\sprintf(
                    'Missing required nested field `%s.%s` at index %d.',
                    $parentField,
                    $subField,
                    $index,
                ));
            }
        }
    }
}
