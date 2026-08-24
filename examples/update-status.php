<?php

/**
 * Activar o eliminar inmuebles en Ciencuadras (PATCH /listing/status).
 *
 * Homlity · https://homlity.com/ · https://homlity.com/desarrolladores/
 *
 * Uso:
 *   php examples/update-status.php <listing_id> [ACTIVE|DELETED]
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Ciencuadras\Sdk\CiencuadrasClient;
use Ciencuadras\Sdk\Config;
use Ciencuadras\Sdk\Exception\ApiException;

$listingId = $argv[1] ?? '';
$status    = strtoupper($argv[2] ?? 'DELETED');   // ACTIVE | DELETED
$clientId  = getenv('CIENCUADRAS_CLIENT_ID') ?: '';

if ($listingId === '' || $clientId === '') {
    fwrite(STDERR, "Uso: CIENCUADRAS_CLIENT_ID=... php examples/update-status.php <listing_id> [ACTIVE|DELETED]\n");
    exit(1);
}

$sdk = new CiencuadrasClient(new Config(getenv('CIENCUADRAS_API_KEY') ?: ''));

try {
    // Acepta un objeto o una lista: para lotes pasa [[...], [...]]
    $response = $sdk->listings()->updateStatus([
        'listing_id' => $listingId,
        'client_id'  => $clientId,
        'status'     => $status,
    ]);

    $taskId = $response['task']['id'] ?? null;
    echo "Cambio de estado encolado. task_id: {$taskId}\n";

    if ($taskId !== null) {
        $task = $sdk->tasks()->waitUntilSettled($taskId, ['maxAttempts' => 10, 'intervalSeconds' => 3]);
        echo 'Estado de la tarea: ' . $task->status()->value . PHP_EOL;
    }

    // El estado real del inmueble se consulta aparte (es un entero, no el string enviado).
    $snapshot = $sdk->listings()->getSnapshot($listingId);
    printf(
        "Inmueble %s → status %s (%s)\n",
        $snapshot->id(),
        $snapshot->statusCode() ?? '-',
        $snapshot->status()?->name ?? 'desconocido',
    );
} catch (ApiException $e) {
    fwrite(STDERR, sprintf("Error %s: %s\n", $e->statusCode() ?? '-', $e->firstErrorMessage() ?? $e->getMessage()));
    exit(1);
}
