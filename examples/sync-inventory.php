<?php

/**
 * Sincronización idempotente de inventario: crea lo que no existe, actualiza lo que sí.
 *
 * Homlity · https://homlity.com/ · https://homlity.com/desarrolladores/
 *
 * Patrón recomendado cuando no guardas el `listing_id` de Ciencuadras y solo
 * tienes tu propio `external_code`.
 *
 * Uso:
 *   CIENCUADRAS_API_KEY=... CIENCUADRAS_CLIENT_ID=... CIENCUADRAS_CLIENT_COOKIE=... \
 *   php examples/sync-inventory.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Ciencuadras\Sdk\CiencuadrasClient;
use Ciencuadras\Sdk\Config;
use Ciencuadras\Sdk\Exception\ApiException;
use Ciencuadras\Sdk\Exception\TransportException;

$clientId = getenv('CIENCUADRAS_CLIENT_ID') ?: '';
$cookie   = getenv('CIENCUADRAS_CLIENT_COOKIE') ?: '';

$sdk = new CiencuadrasClient(new Config(
    apiKey: getenv('CIENCUADRAS_API_KEY') ?: '',
    timeoutSeconds: 60,   // los lotes grandes tardan más
));

/** Tu inventario local. En una app real vendría de la base de datos. */
$inventarioLocal = [
    [
        'external_code'  => 'INT-1001',
        'client_id'      => $clientId,
        'offer'          => 'sell',
        'property_type'  => 'apartment',
        'description'    => 'Apartamento con vista abierta y dos parqueaderos.',
        'price'          => 450000000,
        'area'           => 78,
        'rooms'          => 3,
        'baths'          => 2,
        'stratum'        => 4,
        'address'        => ['address' => 'Calle 12 # 34-56'],
        'locations'      => [
            'location_point'   => ['latitude' => 4.729795079, 'longitude' => -74.044724493],
            'location_main_id' => '1895e0a3-60b8-4a9d-858d-f2c7297b48b2',
            'view_map'         => 2,
        ],
        'listing_contact' => [
            'emails' => [['email' => 'ventas@midominio.com', 'is_main' => true, 'sort_order' => 0]],
            'phones' => [['phone' => '+573001112233', 'is_whatsapp_number' => true, 'sort_order' => 0]],
        ],
        'photos' => [
            ['sort_order' => 1, 'is_main' => true, 'image' => 'https://cdn.midominio.com/1001-1.jpg'],
        ],
    ],
];

$paraCrear     = [];
$paraActualizar = [];

foreach ($inventarioLocal as $listing) {
    // ¿Ya existe en Ciencuadras con ese external_code?
    $existente = $sdk->listings()->findByExternalCode($cookie, $listing['external_code']);

    if ($existente === null) {
        $paraCrear[] = $listing;
        continue;
    }

    $listing['listing_id'] = $existente->id();
    $paraActualizar[] = $listing;
}

/** Reintento con backoff para errores transitorios (429/500/502) y de red. */
function conReintentos(callable $fn, int $maxIntentos = 4): mixed
{
    $intento = 0;

    while (true) {
        try {
            return $fn();
        } catch (ApiException $e) {
            $recuperable = in_array($e->statusCode(), [429, 500, 502, 503, 504], true);
            if (!$recuperable || ++$intento >= $maxIntentos) {
                throw $e;
            }
        } catch (TransportException $e) {
            if (++$intento >= $maxIntentos) {
                throw $e;
            }
        }

        sleep(min(60, 2 ** $intento));
    }
}

$tareas = [];

// Enviar por lotes: un request con N inmuebles consume mucha menos cuota que N requests.
if ($paraCrear !== []) {
    $r = conReintentos(fn () => $sdk->listings()->create($paraCrear));
    $tareas[] = $r['task']['id'] ?? null;
    printf("Creados %d inmuebles → task %s\n", count($paraCrear), $r['task']['id'] ?? '-');
}

if ($paraActualizar !== []) {
    $r = conReintentos(fn () => $sdk->listings()->update($paraActualizar));
    $tareas[] = $r['task']['id'] ?? null;
    printf("Actualizados %d inmuebles → task %s\n", count($paraActualizar), $r['task']['id'] ?? '-');
}

// En producción prefiere webhooks; este polling es la red de seguridad.
foreach (array_filter($tareas) as $taskId) {
    $task = $sdk->tasks()->waitUntilSettled($taskId, [
        'maxAttempts'    => 20,
        'throwOnTimeout' => false,
    ]);

    printf("\nTarea %s → %s\n", $task->id(), $task->status()->value);

    foreach ($task->contentItems() as $item) {
        printf(
            "  [%s] %s → listing_id=%s fr_property_id=%s\n",
            $item['status'] ?? '?',
            $item['external_code'] ?? '-',
            $item['listing_id'] ?? '-',
            $item['fr_property_id'] ?? '-',
        );
        // Guarda listing_id y fr_property_id en tu base de datos.
    }

    foreach ($task->raw()['messages']['listings'] ?? [] as $p) {
        printf("  ERROR %s: %s\n", $p['external_code'] ?? '-', $p['error']['message'] ?? '-');
    }
}
