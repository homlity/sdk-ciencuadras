<?php

declare(strict_types=1);

namespace Ciencuadras\Sdk\Data;

/**
 * Snapshot tipado de una tarea devuelta por GET /task/{task_id}.
 *
 * Uso:
 *   $snap = $client->tasks()->getSnapshot($taskId);
 *   if ($snap->isSuccessful()) { ... }
 */
final class TaskSnapshot
{
    private function __construct(
        private readonly string $id,
        private readonly TaskStatus $status,
        private readonly array $raw,
    ) {}

    // ---------------------------------------------------------------
    // Fábricas
    // ---------------------------------------------------------------

    /**
     * Construye el snapshot a partir del array devuelto por tasks()->get().
     *
     * Acepta tanto la respuesta con sobre (`['task' => [...]]`) como
     * el objeto interno directamente.
     *
     * @param mixed $data
     */
    public static function fromApiResponse(mixed $data): self
    {
        if (!is_array($data)) {
            throw new \InvalidArgumentException('Task response must be an array.');
        }

        // Desenvuelve el sobre { "task": {...} } si está presente
        $task = isset($data['task']) && is_array($data['task']) ? $data['task'] : $data;

        $rawId = $task['id'] ?? null;
        if (!is_string($rawId) || $rawId === '') {
            throw new \InvalidArgumentException('Task response is missing a valid `id` field.');
        }

        $rawStatus = $task['status'] ?? null;
        $status = is_string($rawStatus) ? TaskStatus::tryFrom($rawStatus) : null;

        if ($status === null) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown task status "%s". Expected one of: %s.',
                $rawStatus ?? 'null',
                implode(', ', array_column(TaskStatus::cases(), 'value')),
            ));
        }

        return new self($rawId, $status, $task);
    }

    // ---------------------------------------------------------------
    // Accesores
    // ---------------------------------------------------------------

    public function id(): string
    {
        return $this->id;
    }

    public function status(): TaskStatus
    {
        return $this->status;
    }

    /** Datos crudos del objeto `task` tal como los devuelve la API. */
    public function raw(): array
    {
        return $this->raw;
    }

    // ---------------------------------------------------------------
    // Delegados de conveniencia
    // ---------------------------------------------------------------

    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    public function isSettled(): bool
    {
        return $this->status->isSettled();
    }

    public function isSuccessful(): bool
    {
        return $this->status->isSuccessful();
    }

    public function isFailed(): bool
    {
        return $this->status->isFailed();
    }

    /**
     * Items de contenido del task (vacío si aún no está settled).
     *
     * @return list<array<string, mixed>>
     */
    public function contentItems(): array
    {
        $content = $this->raw['content'] ?? [];
        return is_array($content) ? array_values($content) : [];
    }
}
