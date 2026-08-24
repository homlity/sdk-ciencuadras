<?php

/**
 * Configuración por variables de entorno de Homlity (sandbox / producción).
 *
 * Homlity · https://homlity.com/ · https://homlity.com/desarrolladores/
 *
 * Config::fromHomlityEnv() lee `ciencuadras_environment` y resuelve base URL y rutas
 * automáticamente, lo que permite mover el integrador entre ambientes sin tocar código.
 *
 * Uso:
 *   php examples/homlity-env.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Ciencuadras\Sdk\CiencuadrasClient;
use Ciencuadras\Sdk\Config;

// En una app real esto vendría de $_ENV, getenv() o de la configuración del plugin.
$env = [
    // 'sandbox' | 'qa' | 'development' | 'dev'  → sandbox
    // cualquier otro valor                      → producción
    'ciencuadras_environment' => getenv('CIENCUADRAS_ENVIRONMENT') ?: 'sandbox',

    'CIENCUADRAS_SANDBOX_BASE_URL'    => 'https://dev-ws-api.ciencuadras.com',
    'CIENCUADRAS_PRODUCTION_BASE_URL' => 'https://ws-api.ciencuadras.com',

    // Rutas: si se omiten, el SDK usa los defaults del portal.
    'CIENCUADRAS_SANDBOX_LOGIN_ENDPOINT'                  => '/login',
    'CIENCUADRAS_SANDBOX_CREATE_ENDPOINT'                 => '/api/create',
    'CIENCUADRAS_SANDBOX_UPDATE_ENDPOINT'                 => '/api/update',
    'CIENCUADRAS_SANDBOX_CONSULT_STATUS_ENDPOINT'         => '/api/consult-status',
    'CIENCUADRAS_SANDBOX_CONSULT_PROPERTY_ENDPOINT'       => '/api/consult-property',
    'CIENCUADRAS_SANDBOX_CONSULT_ALL_PROPERTIES_ENDPOINT' => '/api/consult-all-properties',
];

$config = Config::fromHomlityEnv(
    apiKey: getenv('CIENCUADRAS_API_KEY') ?: 'dummy-key',
    env: $env,
    timeoutSeconds: 30,
);

$sdk = new CiencuadrasClient($config);

echo "Base URL: " . $config->baseUrl() . PHP_EOL;
echo "Timeout : " . $config->timeoutSeconds() . "s" . PHP_EOL;
echo PHP_EOL . "Endpoints resueltos:" . PHP_EOL;

foreach ($config->endpoints() as $nombre => $ruta) {
    printf("  %-24s %s\n", $nombre, $ruta);
}

// A partir de aquí los métodos del SDK usan esas rutas:
//   $sdk->listings()->create(...)      → CREATE_ENDPOINT
//   $sdk->listings()->update(...)      → UPDATE_ENDPOINT (con POST en vez de PATCH)
//   $sdk->listings()->get($id)         → CONSULT_PROPERTY_ENDPOINT (listing_id como query param)
//   $sdk->listings()->list($cookie)    → CONSULT_ALL_PROPERTIES_ENDPOINT
//   $sdk->tasks()->get($taskId)        → CONSULT_STATUS_ENDPOINT (task_id como query param)
echo PHP_EOL . "Ruta de creación en uso: " . $sdk->config()->endpoint('create', '/listing') . PHP_EOL;
