# Ciencuadras PHP SDK Desarrollado por codwelt Sas

SDK en PHP para integracion con el API de Integradores de Ciencuadras.
Incluye: 
- Cliente HTTP con cURL y manejo de errores.
- Modulos por recurso (`listings`, `clients`, `categories`, `locations`, `tasks`, `webhooks`).
- Validacion de campos requeridos para publicar/actualizar inmuebles.
- Catalogo de esquemas OpenAPI para consultar parametros en runtime.
- Utilidades para suscribir y procesar webhooks de tareas.

## Instalacion

```bash
composer require ciencuadras/sdk-php
```

## Uso rapido

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Ciencuadras\Sdk\Config;
use Ciencuadras\Sdk\CiencuadrasClient;

$config = new Config(
    apiKey: 'TU_API_KEY',
    baseUrl: Config::BASE_URL_PRODUCTION,
    timeoutSeconds: 30
);

$sdk = new CiencuadrasClient($config);

$clients = $sdk->clients()->all();
print_r($clients);
```

## Uso con variables de Homlity (sandbox/production)

```php
<?php

use Ciencuadras\Sdk\Config;
use Ciencuadras\Sdk\CiencuadrasClient;

$config = Config::fromHomlityEnv(
    apiKey: $_ENV['CIENCUADRAS_API_KEY'],
    env: $_ENV,
    timeoutSeconds: 30,
);

$sdk = new CiencuadrasClient($config);
```

Este modo toma `ciencuadras_environment` y resuelve automáticamente:
- Base URL (`CIENCUADRAS_SANDBOX_BASE_URL` / `CIENCUADRAS_PRODUCTION_BASE_URL`)
- Endpoints (`/api/create`, `/api/update`, `/api/consult-status`, etc.)

Con eso, los métodos del SDK (`create`, `update`, `updateStatus`, `list`, `get`, `tasks()->get`) usan esas rutas configuradas.

## Publicar inmueble (POST /listing)

```php
<?php

$payload = [
    'external_code' => 'INT-0001',
    'client_id' => 'df03d199-be5c-4c5c-98f6-849361cb7fae',
    'offer' => 'sell',
    'property_type' => 'house',
    'description' => 'Casa amplia y bien ubicada.',
    'price' => 450000000,
    'address' => [
        'address' => 'Calle 12 # 34-56',
    ],
    'locations' => [
        'location_point' => [
            'latitude' => 4.729795079,
            'longitude' => -74.044724493,
        ],
        'location_main_id' => '1895e0a3-60b8-4a9d-858d-f2c7297b48b2',
        'view_map' => 2,
    ],
    'area' => 120,
    'listing_contact' => [
        'emails' => [
            ['email' => 'ventas@midominio.com', 'is_main' => true, 'sort_order' => 0],
        ],
        'phones' => [
            [
                'phone' => '+573001112233',
                'is_whatsapp_number' => true,
                'is_click_to_call' => true,
                'sort_order' => 0,
            ],
        ],
    ],
    'photos' => [
        ['sort_order' => 1, 'is_main' => true, 'image' => 'https://example.com/foto-1.jpg'],
    ],
];

$result = $sdk->listings()->create($payload);
print_r($result);
```

## Obtener inmuebles

```php
<?php

// Listado (la API define header Cookie obligatorio en este endpoint)
$list = $sdk->listings()->list(
    clientCookie: '78bea79c-1f6b-4e6d-a800-43fb327ed7c5',
    query: [
        'page' => 1,
        'page_size' => 10,
        'ordering' => '-created',
    ]
);

// Detalle
$detail = $sdk->listings()->get('78bea79c-1f6b-4e6d-a800-43fb327ed7c5');
```

## Parametros y endpoints

- Endpoints completos: `docs/api-reference.md`
- Parametros para creacion y consulta de inmuebles: `docs/listing-parameters.md`
- Webhooks y recepcion de tareas: `docs/webhooks.md`
- Snapshot OpenAPI usado por el SDK: `resources/openapi/ciencuadras-integradores-1.0.0.json`

## Recursos disponibles en el SDK

```php
$sdk->listings();
$sdk->clients();
$sdk->categories();
$sdk->locations();
$sdk->tasks();
$sdk->webhooks();
```

## Webhook de estados de inmuebles

Suscribir el endpoint del integrador:

```php
<?php

$sdk->webhooks()->subscribeTarget(
    integratorId: '696d939e-4cc3-43ac-a312-6bf2e7f15868',
    targetUrl: 'https://midominio.com/webhooks/ciencuadras'
);
```

Procesar el callback entrante:

```php
<?php

use Ciencuadras\Sdk\Webhook\WebhookNotification;

$notification = WebhookNotification::fromGlobals();
$notification->assertAuthorized(
    expectedHubId: $_ENV['CIENCUADRAS_WEBHOOK_HUB_ID'],
    expectedVerifyToken: $_ENV['CIENCUADRAS_WEBHOOK_VERIFY_TOKEN']
);

if ($notification->isListingStatusEvent()) {
    $updates = $notification->listingStatusUpdates();
}
```

### Suscribir solo si cambió la URL

La API de Ciencuadras no expone un GET para consultar la suscripción activa, por lo
que el SDK implementa "desired state" con `subscribeTargetIfChanged()`. El integrador
guarda la URL conocida y la pasa en cada llamada; el SDK evita el POST si no hay
cambio.

```php
<?php

use Ciencuadras\Sdk\Webhook\SubscriptionResult;

// $knownUrl es la URL que guardaste la última vez que suscribiste con éxito.
// Puede venir de tu base de datos, cache, variable de entorno, etc.
$knownUrl = Cache::get('ciencuadras_webhook_url');

$result = $sdk->webhooks()->subscribeTargetIfChanged(
    integratorId: '696d939e-4cc3-43ac-a312-6bf2e7f15868',
    targetUrl: 'https://midominio.com/webhooks/ciencuadras',
    knownUrl: $knownUrl,
);

if ($result->subscribed) {
    // Solo llega aquí si la URL era diferente o desconocida.
    // Persiste la nueva URL para evitar re-suscripciones futuras.
    Cache::set('ciencuadras_webhook_url', $result->url);
}
```

Ejemplos listos:

- `examples/subscribe-webhook.php`
- `examples/receive-webhook.php`

## Nota sobre el API key

El OpenAPI mezcla `apikey` y `X-API-KEY` en distintas secciones. El SDK envia ambos headers automaticamente con el mismo token para maximizar compatibilidad.
