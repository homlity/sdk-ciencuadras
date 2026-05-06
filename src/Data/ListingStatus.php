<?php

declare(strict_types=1);

namespace Ciencuadras\Sdk\Data;

/**
 * Estado interno de un inmueble devuelto por la API de Ciencuadras
 * (campo `status` en las respuestas GET /listing).
 *
 * Nota: el endpoint PATCH /listing/status recibe los strings "ACTIVE" / "DELETED",
 * no estos códigos enteros. Los códigos enteros son de solo lectura.
 */
enum ListingStatus: int
{
    /** Estado inicial al publicar, aún incompleto. */
    case INCOMPLETE = 0;

    /** Desactivado desde la OV. */
    case DISABLED = 1;

    /** Sin cupo disponible. */
    case NO_QUOTA = 2;

    /** Publicado y visible. */
    case ACTIVE = 4;

    /** Producto de cuota expirado. */
    case EXPIRED = 5;

    /** Eliminado. */
    case DELETED = 7;

    /** Error interno del sistema en el proceso de publicación. */
    case SYSTEM_ERROR = 9;

    /** En proceso de publicación. */
    case PUBLISHING = 10;

    /** Moderado y rechazado por contenido fraudulento. */
    case REJECTED = 11;

    // ---------------------------------------------------------------
    // Helpers de estado
    // ---------------------------------------------------------------

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isDeleted(): bool
    {
        return $this === self::DELETED;
    }

    /** El inmueble está visible al público. */
    public function isPublished(): bool
    {
        return $this === self::ACTIVE;
    }

    /** Terminal negativo: eliminado, rechazado o con error. */
    public function isTerminalFailure(): bool
    {
        return match ($this) {
            self::DELETED, self::SYSTEM_ERROR, self::REJECTED => true,
            default => false,
        };
    }

    /** Aún en tránsito hacia un estado estable. */
    public function isPending(): bool
    {
        return $this === self::INCOMPLETE || $this === self::PUBLISHING;
    }
}
