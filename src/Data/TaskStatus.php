<?php

declare(strict_types=1);

namespace Ciencuadras\Sdk\Data;

/**
 * Estado de una tarea en la API de Ciencuadras.
 *
 * El ciclo habitual es: READY → RUNNING → COMPLETED | FORWARDED | ERROR
 */
enum TaskStatus: string
{
    /** Tarea creada, aún no iniciada. */
    case READY = 'READY';

    /** Tarea en ejecución. */
    case RUNNING = 'RUNNING';

    /** Tarea finalizada sin errores. */
    case COMPLETED = 'COMPLETED';

    /**
     * Tarea recibida pero no procesada porque ya existe un request idéntico
     * del mismo día (duplicado).
     */
    case FORWARDED = 'FORWARDED';

    /** Error al crear o ejecutar la tarea. */
    case ERROR = 'ERROR';

    // ---------------------------------------------------------------
    // Helpers de estado
    // ---------------------------------------------------------------

    /**
     * La tarea aún no terminó (READY o RUNNING).
     * Úsalo para decidir si seguir haciendo polling.
     */
    public function isPending(): bool
    {
        return $this === self::READY || $this === self::RUNNING;
    }

    /**
     * La tarea llegó a un estado terminal (COMPLETED, FORWARDED o ERROR).
     */
    public function isSettled(): bool
    {
        return match ($this) {
            self::COMPLETED, self::FORWARDED, self::ERROR => true,
            default => false,
        };
    }

    /**
     * La tarea terminó sin errores (COMPLETED o FORWARDED).
     * FORWARDED es exitoso: significa que la información ya fue entregada hoy.
     */
    public function isSuccessful(): bool
    {
        return $this === self::COMPLETED || $this === self::FORWARDED;
    }

    /**
     * La tarea terminó con error.
     */
    public function isFailed(): bool
    {
        return $this === self::ERROR;
    }
}
