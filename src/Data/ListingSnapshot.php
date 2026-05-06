<?php

declare(strict_types=1);

namespace Ciencuadras\Sdk\Data;

/**
 * Snapshot tipado de un inmueble devuelto por GET /listing/{listing_id}
 * o por los resultados de GET /listing.
 *
 * Uso:
 *   $snap = $client->listings()->getSnapshot($listingId);
 *   if ($snap->isActive()) { ... }
 */
final class ListingSnapshot
{
    private function __construct(
        private readonly string $id,
        private readonly ?string $externalCode,
        private readonly ?ListingStatus $status,
        private readonly array $raw,
    ) {}

    // ---------------------------------------------------------------
    // Fábricas
    // ---------------------------------------------------------------

    /**
     * Construye el snapshot a partir del array de un inmueble.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $id = (string) ($data['id'] ?? $data['listing_id'] ?? '');
        if ($id === '') {
            throw new \InvalidArgumentException('Listing data is missing a valid `id` field.');
        }

        $externalCode = isset($data['external_code']) ? (string) $data['external_code'] : null;

        $rawStatus = $data['status'] ?? null;
        $status = null;

        if (is_int($rawStatus)) {
            $status = ListingStatus::tryFrom($rawStatus);
        } elseif (is_string($rawStatus) && is_numeric($rawStatus)) {
            $status = ListingStatus::tryFrom((int) $rawStatus);
        }

        return new self($id, $externalCode, $status, $data);
    }

    // ---------------------------------------------------------------
    // Accesores
    // ---------------------------------------------------------------

    public function id(): string
    {
        return $this->id;
    }

    public function externalCode(): ?string
    {
        return $this->externalCode;
    }

    /**
     * Estado tipado, o `null` si la respuesta no incluye el campo `status`
     * (p. ej. en respuestas de creación).
     */
    public function status(): ?ListingStatus
    {
        return $this->status;
    }

    /** Datos crudos tal como los devuelve la API. */
    public function raw(): array
    {
        return $this->raw;
    }

    // ---------------------------------------------------------------
    // Delegados de conveniencia
    // ---------------------------------------------------------------

    public function isActive(): bool
    {
        return $this->status?->isActive() ?? false;
    }

    public function isDeleted(): bool
    {
        return $this->status?->isDeleted() ?? false;
    }

    public function isPublished(): bool
    {
        return $this->status?->isPublished() ?? false;
    }

    public function isPending(): bool
    {
        return $this->status?->isPending() ?? false;
    }

    public function statusCode(): ?int
    {
        return $this->status?->value;
    }
}
