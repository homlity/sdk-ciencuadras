<?php

/**
 * Actualizar un inmueble ya publicado en Ciencuadras.
 *
 * Homlity · https://homlity.com/ · https://homlity.com/desarrolladores/
 *
 * Uso:
 *   CIENCUADRAS_API_KEY=... CIENCUADRAS_CLIENT_ID=... php examples/update-listing.php <listing_id>
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Ciencuadras\Sdk\CiencuadrasClient;
use Ciencuadras\Sdk\Config;
use Ciencuadras\Sdk\Exception\ApiException;

$listingId = $argv[1] ?? '07bcf513-d39a-42ff-8370-f42d39cd9494';
$clientId  = getenv('CIENCUADRAS_CLIENT_ID') ?: 'df03d199-be5c-4c5c-98f6-849361cb7fae';

$sdk = new CiencuadrasClient(new Config(
    apiKey: getenv('CIENCUADRAS_API_KEY') ?: '',
    baseUrl: Config::BASE_URL_PRODUCTION,
    timeoutSeconds: 30,
));

// En PATCH /listing hay que reenviar todos los campos requeridos, no solo los que cambian.
$listing = [
    'listing_id'     => $listingId,
    'external_code'  => 'INT-1001',
    'client_id'      => $clientId,
    'offer'          => 'sell',
    'property_type'  => 'house',
    'description'    => 'Casa remodelada: cocina nueva y pisos en madera.',
    'price'          => 439000000,   // bajó de precio
    'area'           => 120,
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
        // Con `id` se reemplaza la imagen existente; sin `id` se crea una nueva.
        ['sort_order' => 1, 'is_main' => true, 'image' => 'https://cdn.midominio.com/nueva-1.jpg'],
        // status 3 elimina la imagen
        // ['id' => 'bd08d894-a117-46f2-a7a5-c6c02e5764b3', 'status' => 3],
    ],
];

try {
    $response = $sdk->listings()->update($listing);
    print_r($response);

    // El resultado real llega en la tarea: ver examples/track-task.php
    echo 'task_id: ' . ($response['task']['id'] ?? '-') . PHP_EOL;
} catch (InvalidArgumentException $e) {
    fwrite(STDERR, 'Payload inválido: ' . $e->getMessage() . PHP_EOL);
    exit(1);
} catch (ApiException $e) {
    fwrite(STDERR, sprintf(
        "Error %s: %s (tracking: %s)\n",
        $e->statusCode() ?? '-',
        $e->firstErrorMessage() ?? $e->getMessage(),
        $e->trackingId() ?? '-',
    ));
    exit(1);
}
