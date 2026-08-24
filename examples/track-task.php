<?php

/**
 * Seguir una tarea asincrónica hasta conocer el resultado de cada inmueble.
 *
 * Homlity · https://homlity.com/ · https://homlity.com/desarrolladores/
 *
 * Uso:
 *   CIENCUADRAS_API_KEY=... php examples/track-task.php <task_id>
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Ciencuadras\Sdk\CiencuadrasClient;
use Ciencuadras\Sdk\Config;

$taskId = $argv[1] ?? '';

if ($taskId === '') {
    fwrite(STDERR, "Uso: php examples/track-task.php <task_id>\n");
    exit(1);
}

$sdk = new CiencuadrasClient(new Config(getenv('CIENCUADRAS_API_KEY') ?: ''));

// Espera hasta un estado terminal: COMPLETED, FORWARDED o ERROR.
// Tolera errores transitorios (429/502) reintentando.
$task = $sdk->tasks()->waitUntilSettled($taskId, [
    'maxAttempts'     => 20,
    'intervalSeconds' => 3,
    'throwOnTimeout'  => false,   // devuelve el último snapshot en vez de lanzar excepción
]);

printf("Tarea %s → %s\n", $task->id(), $task->status()->value);

if ($task->isPending()) {
    echo "Todavía en proceso. Vuelve a consultar más tarde.\n";
    exit(0);
}

// FORWARDED también es éxito: significa que la información ya se envió hoy (duplicado).
echo $task->isSuccessful() ? "Resultado: OK\n" : "Resultado: ERROR\n";

echo "\n--- Inmuebles procesados ---\n";
foreach ($task->contentItems() as $item) {
    printf(
        "  [%s] external_code=%s listing_id=%s fr_property_id=%s\n",
        $item['status'] ?? '?',
        $item['external_code'] ?? '-',
        $item['listing_id'] ?? '-',
        $item['fr_property_id'] ?? '-',
    );

    foreach ($item['multimedia'] ?? [] as $media) {
        printf("      imagen %s → %s\n", $media['id'] ?? '-', $media['status'] ?? '-');
    }
}

$messages = $task->raw()['messages'] ?? [];

if (!empty($messages['listings'])) {
    echo "\n--- Errores de inmuebles ---\n";
    foreach ($messages['listings'] as $problema) {
        printf(
            "  %s: %s [%s] %s (tracking: %s)\n",
            $problema['external_code'] ?? $problema['listing_id'] ?? '-',
            $problema['error']['message'] ?? '-',
            $problema['error']['field']['default_code'] ?? '-',
            $problema['error']['field']['description'] ?? '',
            $problema['error']['tracking_id'] ?? '-',
        );
    }
}

if (!empty($messages['images'])) {
    echo "\n--- Errores de imágenes ---\n";
    foreach ($messages['images'] as $problema) {
        printf(
            "  %s → %s\n",
            $problema['url'] ?? '-',
            $problema['error']['field']['description'] ?? '-',
        );
    }
}
