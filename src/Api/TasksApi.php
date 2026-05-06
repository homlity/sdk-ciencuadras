<?php

declare(strict_types=1);

namespace Ciencuadras\Sdk\Api;

use Ciencuadras\Sdk\Config;
use Ciencuadras\Sdk\Data\TaskSnapshot;

final class TasksApi extends BaseApi
{
    public function __construct(
        \Ciencuadras\Sdk\Http\HttpClientInterface $httpClient,
        private readonly ?Config $config = null,
    ) {
        parent::__construct($httpClient);
    }

    /**
     * GET /task/{task_id}
     *
     * Devuelve el array crudo de la respuesta JSON.
     * Para una versión tipada usa {@see getSnapshot()}.
     */
    public function get(string $taskId): mixed
    {
        $path = $this->config?->endpoint('consult_status', '/task/' . $this->encodePath($taskId))
            ?? '/task/' . $this->encodePath($taskId);

        $options = [];
        if ($path === ($this->config?->endpoint('consult_status') ?? '')) {
            $options['query'] = ['task_id' => $taskId];
        }

        return $this->send('GET', $path, $options);
    }

    /**
     * GET /task/{task_id}
     *
     * Igual que {@see get()} pero envuelve la respuesta en un {@see TaskSnapshot}
     * con helpers como isPending(), isSuccessful() o isFailed().
     */
    public function getSnapshot(string $taskId): TaskSnapshot
    {
        return TaskSnapshot::fromApiResponse($this->get($taskId));
    }

    /**
     * Hace polling sobre GET /task/{task_id} hasta que la tarea llegue a un
     * estado terminal (COMPLETED, FORWARDED o ERROR) o se agoten los intentos.
     *
     * @param array{
     *   maxAttempts?: int,
     *   intervalSeconds?: int,
     *   throwOnTimeout?: bool,
     *   sleepFn?: callable(int): void
     * } $options
     *
     *   - maxAttempts     (int)      Máximo de intentos antes de lanzar excepción. Default: 30.
     *   - intervalSeconds (int)     Segundos entre intentos. Default: 3.
     *   - throwOnTimeout  (bool)    Si es false, retorna el último snapshot (o uno mínimo con
     *                               status READY) en lugar de lanzar RuntimeException. Default: true.
     *   - sleepFn         (callable) Función de espera; útil para tests. Default: sleep().
     *
     * @throws \RuntimeException si la tarea no se resuelve antes de agotar los intentos
     *                           y throwOnTimeout es true.
     */
    public function waitUntilSettled(string $taskId, array $options = []): TaskSnapshot
    {
        $maxAttempts = max(1, (int) ($options['maxAttempts'] ?? 30));
        $intervalSeconds = max(0, (int) ($options['intervalSeconds'] ?? 3));
        $throwOnTimeout = (bool) ($options['throwOnTimeout'] ?? true);
        $sleep = $options['sleepFn'] ?? static fn (int $s): int => sleep($s);

        $snapshot = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $snapshot = $this->getSnapshot($taskId);
            } catch (\Throwable) {
                if ($attempt < $maxAttempts) {
                    ($sleep)($intervalSeconds);
                }
                continue;
            }

            if ($snapshot->isSettled()) {
                return $snapshot;
            }

            if ($attempt < $maxAttempts) {
                ($sleep)($intervalSeconds);
            }
        }

        if (! $throwOnTimeout) {
            if ($snapshot instanceof TaskSnapshot) {
                return $snapshot;
            }

            // getSnapshot falló en todos los intentos; devolver snapshot mínimo con READY
            return TaskSnapshot::fromApiResponse(['id' => $taskId, 'status' => 'READY']);
        }

        throw new \RuntimeException(sprintf(
            'Task "%s" did not settle after %d attempt(s) (last status: %s).',
            $taskId,
            $maxAttempts,
            $snapshot?->status()->value ?? 'unknown',
        ));
    }
}
