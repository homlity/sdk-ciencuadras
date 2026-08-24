<?php

/**
 * Explorar catálogos: clientes, agentes, ubicaciones, categorías y el propio OpenAPI.
 *
 * Homlity · https://homlity.com/ · https://homlity.com/desarrolladores/
 *
 * Es la primera llamada útil al integrar: verifica el API key y resuelve los ids
 * que necesitas para construir un inmueble.
 *
 * Uso:
 *   CIENCUADRAS_API_KEY=... php examples/explore-catalogs.php [texto-ubicacion]
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Ciencuadras\Sdk\CiencuadrasClient;
use Ciencuadras\Sdk\Config;
use Ciencuadras\Sdk\Exception\ApiException;

$busqueda = $argv[1] ?? 'Chapinero';

$sdk = new CiencuadrasClient(new Config(getenv('CIENCUADRAS_API_KEY') ?: ''));

// ---------------------------------------------------------------
// 1. El OpenAPI embebido: no requiere red ni credenciales
// ---------------------------------------------------------------
$catalog = $sdk->schemaCatalog();

echo "=== OpenAPI ===\n";
echo $catalog->title() . ' v' . $catalog->version() . PHP_EOL;
echo 'Campos requeridos para crear: ' . implode(', ', $catalog->listingCreateRequiredFields()) . PHP_EOL;
echo 'Tipos de inmueble: ' . implode(', ', $catalog->listingCreateItemSchema()['properties']['property_type']['enum']) . PHP_EOL;

echo "\nOperaciones disponibles:\n";
foreach ($catalog->operations() as $op) {
    printf("  %-6s %-30s %s\n", $op['method'], $op['path'], $op['operationId']);
}

try {
    // ---------------------------------------------------------------
    // 2. Clientes (inmobiliarias) y su cupo
    // ---------------------------------------------------------------
    echo "\n=== Clientes ===\n";
    $clients = $sdk->clients()->all();

    foreach ((array) $clients as $client) {
        printf(
            "  %s | %s | cupo %s/%s (%s%%)\n",
            $client['id'] ?? '-',
            $client['name'] ?? '-',
            $client['used_quota'] ?? '-',
            $client['initial_quota'] ?? '-',
            $client['percentage_used_quota'] ?? '-',
        );

        // ---------------------------------------------------------------
        // 3. Agentes / sucursales del cliente
        // ---------------------------------------------------------------
        foreach ((array) $sdk->clients()->agents($client['id']) as $agent) {
            printf("      agente %s — %s\n", $agent['id'] ?? '-', $agent['email'] ?? '-');
        }
    }

    // ---------------------------------------------------------------
    // 4. Ubicaciones: el id de un NEIGHBOURHOOD va en locations.location_main_id
    // ---------------------------------------------------------------
    echo "\n=== Ubicaciones para \"{$busqueda}\" ===\n";
    foreach ((array) $sdk->locations()->search($busqueda) as $loc) {
        printf(
            "  %-14s %s | %s (%s, %s)\n",
            $loc['location_type'] ?? '-',
            $loc['id'] ?? '-',
            $loc['name'] ?? '-',
            $loc['city'] ?? '-',
            $loc['state'] ?? '-',
        );
    }

    // ---------------------------------------------------------------
    // 5. Categorías: los ids van en el campo `categories[]` del inmueble
    // ---------------------------------------------------------------
    echo "\n=== Categorías ===\n";
    $categorias = $sdk->categories()->list();
    echo 'Total: ' . ($categorias['total'] ?? 0) . PHP_EOL;
    print_r(array_slice($categorias['list']['hits'] ?? [], 0, 10));
} catch (ApiException $e) {
    fwrite(STDERR, sprintf(
        "Error %s: %s (tracking: %s)\n",
        $e->statusCode() ?? '-',
        $e->firstErrorMessage() ?? $e->getMessage(),
        $e->trackingId() ?? '-',
    ));
    exit(1);
}
