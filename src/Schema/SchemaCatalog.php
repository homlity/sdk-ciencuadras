<?php

declare(strict_types=1);

namespace Ciencuadras\Sdk\Schema;

final class SchemaCatalog
{
    /**
     * @var array<string, mixed>
     */
    private array $spec;

    public function __construct(?string $openApiFilePath = null)
    {
        $path = $openApiFilePath ?? $this->defaultOpenApiPath();

        if (!\is_file($path)) {
            throw new \RuntimeException(\sprintf('OpenAPI file not found: %s', $path));
        }

        $raw = \file_get_contents($path);
        if ($raw === false) {
            throw new \RuntimeException(\sprintf('Unable to read OpenAPI file: %s', $path));
        }

        $decoded = \json_decode($raw, true);
        if (!\is_array($decoded)) {
            throw new \RuntimeException(\sprintf('Invalid OpenAPI JSON in: %s', $path));
        }

        $this->spec = $decoded;
    }

    public function title(): string
    {
        return (string) ($this->spec['info']['title'] ?? '');
    }

    public function version(): string
    {
        return (string) ($this->spec['info']['version'] ?? '');
    }

    /**
     * @return array<int, array{method: string, path: string, operationId: string, parameters: array<int, array<string, mixed>>}>
     */
    public function operations(): array
    {
        $operations = [];

        foreach (($this->spec['paths'] ?? []) as $path => $methods) {
            if (!\is_array($methods)) {
                continue;
            }

            foreach ($methods as $method => $operation) {
                if (!\is_array($operation)) {
                    continue;
                }

                $operations[] = [
                    'method' => \strtoupper((string) $method),
                    'path' => (string) $path,
                    'operationId' => (string) ($operation['operationId'] ?? ''),
                    'parameters' => \is_array($operation['parameters'] ?? null) ? $operation['parameters'] : [],
                ];
            }
        }

        return $operations;
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(string $name): array
    {
        $schema = $this->spec['components']['schemas'][$name] ?? null;

        if (!\is_array($schema)) {
            throw new \InvalidArgumentException(\sprintf('Schema `%s` not found in OpenAPI.', $name));
        }

        return $schema;
    }

    /**
     * @return list<string>
     */
    public function listingCreateRequiredFields(): array
    {
        return $this->requiredFieldsFromArraySchema('ListingPOST');
    }

    /**
     * @return list<string>
     */
    public function listingUpdateRequiredFields(): array
    {
        return $this->requiredFieldsFromArraySchema('ListingPATCH');
    }

    /**
     * @return list<string>
     */
    public function listingStatusRequiredFields(): array
    {
        return $this->requiredFieldsFromArraySchema('ListingStatus');
    }

    // ---------------------------------------------------------------
    // Accesores de items schema (para validación estricta)
    // ---------------------------------------------------------------

    /**
     * Retorna el schema `items` completo de ListingPOST
     * (con `properties`, `required`, etc.).
     *
     * @return array<string, mixed>
     */
    public function listingCreateItemSchema(): array
    {
        return $this->itemsFromSchema('ListingPOST');
    }

    /**
     * @return array<string, mixed>
     */
    public function listingUpdateItemSchema(): array
    {
        return $this->itemsFromSchema('ListingPATCH');
    }

    /**
     * @return array<string, mixed>
     */
    public function listingStatusItemSchema(): array
    {
        return $this->itemsFromSchema('ListingStatus');
    }

    /**
     * @return array<string, mixed>
     */
    private function itemsFromSchema(string $schemaName): array
    {
        $schema = $this->schema($schemaName);
        $items = $schema['items'] ?? [];

        return \is_array($items) ? $items : [];
    }

    /**
     * @return list<string>
     */
    private function requiredFieldsFromArraySchema(string $schemaName): array
    {
        $schema = $this->schema($schemaName);
        $required = $schema['items']['required'] ?? [];

        if (!\is_array($required)) {
            return [];
        }

        return \array_values(\array_filter($required, static fn ($value): bool => \is_string($value)));
    }

    private function defaultOpenApiPath(): string
    {
        return \dirname(__DIR__, 2) . '/resources/openapi/ciencuadras-integradores-1.0.0.json';
    }
}
