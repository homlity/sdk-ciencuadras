<p align="center">
  <a href="https://homlity.com/">
    <img src="https://homlity.com/wp-content/uploads/2026/08/Diseno-sin-titulo-1-e1787507729419-1024x338.webp" alt="Homlity" width="360">
  </a>
</p>

<h1 align="center">Homlity SDK · Ciencuadras</h1>

<p align="center">
  SDK oficial en PHP para integrar tu sistema inmobiliario con el <b>API de Integradores de Ciencuadras</b>.<br>
  Parte del ecosistema abierto de <a href="https://homlity.com/">Homlity</a>.
</p>

<p align="center">
  <a href="https://packagist.org/packages/homlity/sdk-ciencuadras"><img src="https://img.shields.io/packagist/v/homlity/sdk-ciencuadras.svg?style=flat-square&label=packagist" alt="Packagist"></a>
  <a href="https://www.php.net/"><img src="https://img.shields.io/badge/php-%5E8.1-777bb3?style=flat-square" alt="PHP ^8.1"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-green?style=flat-square" alt="MIT"></a>
  <a href="https://github.com/homlity/sdk-ciencuadras"><img src="https://img.shields.io/badge/github-homlity%2Fsdk--ciencuadras-181717?style=flat-square&logo=github" alt="GitHub"></a>
</p>

<p align="center">
  <b><a href="https://homlity.com/">homlity.com</a></b> ·
  <b><a href="https://homlity.com/desarrolladores/">Portal de desarrolladores</a></b> ·
  <b><a href="https://github.com/homlity">GitHub @homlity</a></b>
</p>

---

## Tabla de contenido

- [¿Qué es Homlity?](#qué-es-homlity)
- [¿Para qué sirve este SDK?](#para-qué-sirve-este-sdk)
- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Configuración](#configuración)
- [Primeros pasos en 5 minutos](#primeros-pasos-en-5-minutos)
- [Recursos disponibles](#recursos-disponibles)
- [Publicar inmuebles](#publicar-inmuebles)
- [Actualizar inmuebles](#actualizar-inmuebles)
- [Activar o eliminar inmuebles](#activar-o-eliminar-inmuebles)
- [Consultar inmuebles](#consultar-inmuebles)
- [Tareas asincrónicas](#tareas-asincrónicas)
- [Webhooks](#webhooks)
- [Catálogos: clientes, agentes, ubicaciones y categorías](#catálogos-clientes-agentes-ubicaciones-y-categorías)
- [Validación de payloads](#validación-de-payloads)
- [Manejo de errores](#manejo-de-errores)
- [Uso en Laravel y WordPress](#uso-en-laravel-y-wordpress)
- [Testing y HTTP client propio](#testing-y-http-client-propio)
- [Documentación extendida](#documentación-extendida)
- [Otros SDKs de Homlity](#otros-sdks-de-homlity)
- [Contribuir](#contribuir)
- [Licencia](#licencia)

---

## ¿Qué es Homlity?

[Homlity](https://homlity.com/) es un **ecosistema tecnológico para inmobiliarias**: conecta,
automatiza y optimiza la operación integrando CRMs, ERPs, portales inmobiliarios y herramientas
especializadas en un solo lugar.

Para los desarrolladores, Homlity publica un conjunto de **SDKs open source en PHP** (distribuidos
por Composer/Packagist) que encapsulan la comunicación con cada portal o CRM del sector. Así no
tienes que reimplementar autenticación, validaciones, reintentos y webhooks para cada proveedor.

> Portal de desarrolladores: **<https://homlity.com/desarrolladores/>**

## ¿Para qué sirve este SDK?

`homlity/sdk-ciencuadras` es el cliente PHP del **API de Integradores de Ciencuadras**, el portal
inmobiliario colombiano. Sirve para **sincronizar el inventario de inmuebles de tu sistema con
Ciencuadras de forma masiva y automática**.

Casos de uso típicos:

| Necesidad | Qué hace el SDK |
| --- | --- |
| Publicar el inventario de una inmobiliaria en Ciencuadras | `listings()->create()` acepta uno o varios inmuebles en un solo request |
| Mantener precios, fotos y descripciones sincronizados | `listings()->update()` con envío por lotes |
| Retirar o reactivar publicaciones | `listings()->updateStatus()` con `ACTIVE` / `DELETED` |
| Saber si Ciencuadras aceptó cada inmueble | `tasks()->waitUntilSettled()` o webhooks |
| Reaccionar en tiempo real a cambios de estado | `WebhookNotification` con validación de `HUB.ID` / `VERIFY-TOKEN` |
| No enviar payloads inválidos (y gastar cupo) | Validación local contra el snapshot OpenAPI antes del request |
| Consultar catálogos (ciudades, barrios, características) | `locations()`, `categories()`, `clients()` |

Lo que aporta sobre un `curl` directo:

- **Cliente HTTP con cURL** y manejo homogéneo de errores (`ApiException`, `TransportException`).
- **Módulos por recurso**: `listings`, `clients`, `categories`, `locations`, `tasks`, `webhooks`.
- **Validación previa** de campos requeridos (y opcionalmente tipos/enums) contra el OpenAPI oficial.
- **Catálogo de esquemas en runtime** para inspeccionar parámetros sin abrir el Swagger.
- **Enums tipados** (`TaskStatus`, `ListingStatus`) y snapshots (`TaskSnapshot`, `ListingSnapshot`).
- **Polling de tareas** con `waitUntilSettled()`.
- **Utilidades de webhooks**, incluyendo suscripción idempotente (`subscribeTargetIfChanged`).
- **Doble header de autenticación** (`apikey` + `X-API-KEY`) porque el OpenAPI de Ciencuadras
  usa ambos nombres en distintas secciones.

## Requisitos

| Requisito | Versión |
| --- | --- |
| PHP | `^8.1` |
| Extensión `curl` | requerida |
| Extensión `json` | requerida |
| Composer | 2.x |

No requiere framework: funciona en PHP puro, Laravel, Symfony, WordPress o cualquier proyecto
con autoload PSR-4.

## Instalación

```bash
composer require homlity/sdk-ciencuadras
```

> **Nota sobre versiones.** Mientras el paquete no tenga un tag estable publicado, instálalo
> apuntando a la rama principal:
>
> ```bash
> composer require homlity/sdk-ciencuadras:dev-main
> ```
>
> Si tu `composer.json` tiene `"minimum-stability": "stable"`, agrega también:
>
> ```json
> {
>   "minimum-stability": "dev",
>   "prefer-stable": true
> }
> ```

Instalación desde el repositorio (fork o desarrollo local):

```json
{
  "repositories": [
    { "type": "vcs", "url": "https://github.com/homlity/sdk-ciencuadras" }
  ],
  "require": {
    "homlity/sdk-ciencuadras": "dev-main"
  }
}
```

El namespace del paquete es `Ciencuadras\Sdk\` (autoload PSR-4 sobre `src/`).

## Configuración

### Credenciales

Solicita a Ciencuadras, como parte del proceso de verificación de integrador:

| Dato | Para qué sirve | Dónde se usa |
| --- | --- | --- |
| `API KEY` | Autenticación de todas las llamadas | `Config::__construct(apiKey: ...)` |
| `client_id` (uuid) | Inmobiliaria/cliente al que pertenecen los inmuebles | Campo obligatorio de cada inmueble |
| `client_agent` (int) | Sucursal/agente | Campo opcional del inmueble |
| Cookie de cliente | Header obligatorio del listado `GET /listing` | `listings()->list(clientCookie: ...)` |
| `integrator_id` (uuid) | Identificador para suscribir el webhook | `webhooks()->subscribeTarget()` |
| `HUB.ID` y `VERIFY-TOKEN` | Validar que un callback vino de Ciencuadras | `WebhookNotification::assertAuthorized()` |

**Nunca** hardcodees el API key: usa variables de entorno o el gestor de secretos de tu plataforma.

### Ambientes disponibles

```php
use Ciencuadras\Sdk\Config;

Config::BASE_URL_PRODUCTION;        // https://kong.ciencuadras.com.co/management/api/1.0
Config::BASE_URL_QA;                // https://kong-qa.ciencuadras.com.co/management/api/1.0
Config::BASE_URL_MOCK;              // SwaggerHub mock server (útil para pruebas sin credenciales)
Config::BASE_URL_PORTAL_SANDBOX;    // https://dev-ws-api.ciencuadras.com
Config::BASE_URL_PORTAL_PRODUCTION; // https://ws-api.ciencuadras.com
```

### Opción A — configuración explícita

```php
use Ciencuadras\Sdk\Config;
use Ciencuadras\Sdk\CiencuadrasClient;

$config = new Config(
    apiKey: getenv('CIENCUADRAS_API_KEY'),
    baseUrl: Config::BASE_URL_PRODUCTION,
    timeoutSeconds: 30,
);

$sdk = new CiencuadrasClient($config);
```

`Config` valida en el constructor: lanza `InvalidArgumentException` si el API key está vacío o si
el timeout no es positivo.

### Opción B — configuración por entorno Homlity

Pensada para instalaciones donde el ambiente y las rutas se controlan por configuración
(por ejemplo el plugin de Homlity para WordPress).

```php
$config = Config::fromHomlityEnv(
    apiKey: $_ENV['CIENCUADRAS_API_KEY'],
    env: $_ENV,
    timeoutSeconds: 30,
);
```

Lee `ciencuadras_environment` y resuelve base URL + rutas automáticamente:

| Variable | Descripción | Default |
| --- | --- | --- |
| `ciencuadras_environment` | `sandbox`, `qa`, `development`, `dev` → sandbox. Cualquier otro valor → producción | `production` |
| `CIENCUADRAS_SANDBOX_BASE_URL` | Base URL del ambiente sandbox | `https://dev-ws-api.ciencuadras.com` |
| `CIENCUADRAS_PRODUCTION_BASE_URL` | Base URL del ambiente productivo | `https://ws-api.ciencuadras.com` |
| `CIENCUADRAS_{ENV}_LOGIN_ENDPOINT` | Ruta de login | `/login` |
| `CIENCUADRAS_{ENV}_CREATE_ENDPOINT` | Ruta de creación | `/api/create` |
| `CIENCUADRAS_{ENV}_UPDATE_ENDPOINT` | Ruta de actualización | `/api/update` |
| `CIENCUADRAS_{ENV}_CONSULT_STATUS_ENDPOINT` | Consulta de estado de tarea | `/api/consult-status` |
| `CIENCUADRAS_{ENV}_CONSULT_PROPERTY_ENDPOINT` | Detalle de inmueble | `/api/consult-property` |
| `CIENCUADRAS_{ENV}_CONSULT_ALL_PROPERTIES_ENDPOINT` | Listado de inmuebles | `/api/consult-all-properties` |
| `CIENCUADRAS_{ENV}_PRICE_CAPS_ENDPOINT` | Topes de precio (opcional) | — |
| `CIENCUADRAS_{ENV}_AREA_CAPS_ENDPOINT` | Topes de área (opcional) | — |
| `CIENCUADRAS_{ENV}_ZONE_CAPS_ENDPOINT` | Topes por zona (opcional) | — |

Donde `{ENV}` es `SANDBOX` o `PRODUCTION`. Las rutas se normalizan: se les antepone `/` si no lo
traen y se aceptan URLs absolutas (`https://...`) para apuntar a otro host.

Cuando hay endpoints configurados, el SDK ajusta el verbo HTTP: `update()` y `updateStatus()`
usan `PATCH` contra el API estándar (`/listing`) y `POST` contra las rutas tipo portal
(`/api/update`).

```php
$sdk->config()->endpoint('create');   // "/api/create"
$sdk->config()->endpoints();          // array completo
```

## Primeros pasos en 5 minutos

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Ciencuadras\Sdk\Config;
use Ciencuadras\Sdk\CiencuadrasClient;
use Ciencuadras\Sdk\Exception\ApiException;

$sdk = new CiencuadrasClient(new Config(getenv('CIENCUADRAS_API_KEY')));

try {
    // 1. ¿Con qué clientes (inmobiliarias) puedo trabajar y cuánto cupo tienen?
    $clients = $sdk->clients()->all();
    print_r($clients);

    // 2. ¿Qué agente/sucursal uso?
    $clientId = $clients[0]['id'];
    $agentId  = $sdk->clients()->resolveSingleAgentId($clientId);

    // 3. ¿Cuál es el id del barrio donde está el inmueble?
    $locations = $sdk->locations()->search('Chapinero');
    print_r($locations);
} catch (ApiException $e) {
    echo $e->statusCode() . ' → ' . ($e->firstErrorMessage() ?? $e->getMessage());
}
```

## Recursos disponibles

```php
$sdk->listings();   // Inmuebles: crear, actualizar, estado, consultar, validar
$sdk->clients();    // Clientes (inmobiliarias), cupos y agentes
$sdk->categories(); // Características/amenidades disponibles
$sdk->locations();  // Búsqueda de ubicaciones (país, ciudad, barrio, zona…)
$sdk->tasks();      // Estado de las tareas asincrónicas
$sdk->webhooks();   // Suscripción y publicación de eventos

$sdk->config();         // Config activa
$sdk->schemaCatalog();  // Acceso al OpenAPI embebido
```

Todos los módulos se instancian de forma perezosa y se reutilizan en la misma instancia del cliente.

## Publicar inmuebles

`POST /listing`. Acepta un inmueble o una lista de inmuebles (envío por lotes).

### Campos obligatorios

`description`, `external_code`, `client_id`, `offer`, `property_type`, `price`, `address`,
`locations`, `area`, `listing_contact`.

### Ejemplo completo

```php
$listing = [
    // --- Identificación ---
    'external_code'  => 'INT-1001',                                 // tu código interno
    'client_id'      => 'df03d199-be5c-4c5c-98f6-849361cb7fae',     // inmobiliaria
    'client_agent'   => 42,                                         // sucursal (opcional)

    // --- Oferta ---
    'offer'          => 'sell',      // sell | rent | lease
    'property_type'  => 'house',     // ver tabla de tipos
    'description'    => 'Casa amplia y bien ubicada, con patio y garaje doble.',
    'price'          => 450000000,
    'negotiable'     => true,
    'administration' => ['is_included' => false, 'price' => 350000],

    // --- Características ---
    'area'           => 120,   // área construida (m²)
    'living_area'    => 98,    // área privada (m²)
    'condition'      => 2,     // 2 = Excelente
    'stratum'        => 4,     // estrato
    'age'            => 3,     // 3 = de 9 a 15 años
    'rooms'          => 3,
    'baths'          => 2,
    'garages'        => 2,
    'floor'          => 1,
    'categories'     => [100, 104, 117],   // ids de GET /category

    // --- Ubicación ---
    'address'   => ['address' => 'Calle 12 # 34-56'],
    'locations' => [
        'location_point'   => ['latitude' => 4.729795079, 'longitude' => -74.044724493],
        'location_main_id' => '1895e0a3-60b8-4a9d-858d-f2c7297b48b2',  // id de barrio
        'view_map'         => 2,  // 0 = punto exacto, 1 = oculto, 2 = solo zona
    ],
    'postal_code' => '110221',

    // --- Contacto ---
    'listing_contact' => [
        'emails' => [
            ['email' => 'ventas@midominio.com', 'is_main' => true, 'sort_order' => 0],
        ],
        'phones' => [
            [
                'phone'              => '+573001112233',
                'is_whatsapp_number' => true,
                'is_click_to_call'   => true,
                'sort_order'         => 0,
            ],
        ],
    ],

    // --- Multimedia (máximo 30 fotos) ---
    'photos' => [
        ['sort_order' => 1, 'is_main' => true,  'image' => 'https://cdn.midominio.com/1.jpg'],
        ['sort_order' => 2, 'is_main' => false, 'image' => 'https://cdn.midominio.com/2.jpg'],
    ],
    'video' => 'https://www.youtube.com/watch?v=xxxxxxx',
];

$response = $sdk->listings()->create($listing);
// => ['task' => ['id' => '...', 'status' => 'READY', ...]]
```

### Envío por lotes

```php
$response = $sdk->listings()->create([$listingA, $listingB, $listingC]);
```

El SDK detecta automáticamente si le pasas un objeto o una lista (`normalizeBatchPayload`),
así que ambas formas son válidas.

> La respuesta **no** confirma que los inmuebles quedaron publicados: devuelve un `task_id`.
> Ver [Tareas asincrónicas](#tareas-asincrónicas).

### Tipos de inmueble (`property_type`)

| Valor | Español | Valor | Español |
| --- | --- | --- | --- |
| `apartment` | Apartamento | `building` | Edificio |
| `house` | Casa | `cabin` | Cabaña |
| `studio` | Apartaestudio | `country-house` | Casa campestre |
| `house-lot` | Casa lote | `lot` | Lote |
| `room` | Habitación | `farm` | Finca |
| `office` | Oficina | `commercial` | Local |
| `consulting-room` | Consultorio | `warehouse` | Bodega |
| `parking` | Parqueadero | | |

### Tipo de oferta (`offer`)

| Valor | Significado |
| --- | --- |
| `sell` | Venta |
| `rent` | Arriendo |
| `lease` | Alquiler vacacional |

Los enums numéricos completos (`condition`, `stratum`, `age`, `capacity`, `parking_size`…) están en
[`docs/listing-parameters.md`](docs/listing-parameters.md).

## Actualizar inmuebles

`PATCH /listing`. Mismos campos que la creación **más `listing_id`** (el uuid que asignó Ciencuadras).

```php
$sdk->listings()->update([
    'listing_id'      => '07bcf513-d39a-42ff-8370-f42d39cd9494',
    'external_code'   => 'INT-1001',
    'client_id'       => 'df03d199-be5c-4c5c-98f6-849361cb7fae',
    'offer'           => 'sell',
    'property_type'   => 'house',
    'description'     => 'Casa remodelada con cocina nueva.',
    'price'           => 439000000,   // bajó de precio
    'area'            => 120,
    'address'         => ['address' => 'Calle 12 # 34-56'],
    'locations'       => [
        'location_point' => ['latitude' => 4.729795079, 'longitude' => -74.044724493],
    ],
    'listing_contact' => $contacto,
]);
```

### Actualizar o eliminar fotos

Envía el `id` de la imagen para reemplazarla; si omites el `id`, se crea una nueva.
Para eliminar una imagen usa `status => 3`.

```php
'photos' => [
    ['id' => 'bd08d894-a117-46f2-a7a5-c6c02e5764b3', 'image' => 'https://cdn.midominio.com/nueva.jpg', 'sort_order' => 1, 'is_main' => true],
    ['id' => '4e3a...', 'status' => 3],   // eliminar
],
```

## Activar o eliminar inmuebles

`PATCH /listing/status`. Requiere `listing_id`, `client_id` y `status`.

```php
use Ciencuadras\Sdk\Data\ListingStatus;

$sdk->listings()->updateStatus([
    'listing_id' => '07bcf513-d39a-42ff-8370-f42d39cd9494',
    'client_id'  => 'df03d199-be5c-4c5c-98f6-849361cb7fae',
    'status'     => 'DELETED',   // 'ACTIVE' | 'DELETED'
]);
```

> **Cuidado con dos vocabularios distintos.** El endpoint de cambio de estado recibe los strings
> `ACTIVE` / `DELETED`, pero las respuestas de consulta devuelven un `status` **entero**. El enum
> `ListingStatus` mapea esos enteros de solo lectura:

| Código | Caso | Significado |
| --- | --- | --- |
| `0` | `INCOMPLETE` | Publicado pero aún incompleto |
| `1` | `DISABLED` | Desactivado desde la OV |
| `2` | `NO_QUOTA` | Sin cupo disponible |
| `4` | `ACTIVE` | Publicado y visible |
| `5` | `EXPIRED` | Producto de cuota expirado |
| `7` | `DELETED` | Eliminado |
| `9` | `SYSTEM_ERROR` | Error interno de publicación |
| `10` | `PUBLISHING` | En proceso de publicación |
| `11` | `REJECTED` | Rechazado por moderación |

Helpers: `isActive()`, `isDeleted()`, `isPublished()`, `isPending()`, `isTerminalFailure()`.

## Consultar inmuebles

### Listado

```php
$page = $sdk->listings()->list(
    clientCookie: getenv('CIENCUADRAS_CLIENT_COOKIE'),   // header Cookie obligatorio
    query: [
        'page'      => 1,
        'page_size' => 20,
        'ordering'  => '-created',   // created, -created, updated, -updated, status, -status, …
        'search'    => 'chapinero',
    ],
);
// => ['count' => 120, 'next' => ..., 'previous' => ..., 'results' => [...]]
```

### Detalle tipado

```php
$snap = $sdk->listings()->getSnapshot('07bcf513-d39a-42ff-8370-f42d39cd9494');

$snap->id();            // uuid
$snap->externalCode();  // tu código interno
$snap->status();        // ListingStatus|null
$snap->statusCode();    // int|null
$snap->isActive();      // bool
$snap->isPending();     // bool
$snap->raw();           // array crudo de la API
```

`listings()->get()` devuelve el array crudo si prefieres trabajar sin el snapshot.

### Buscar por tu propio código

Muy útil para reconciliar tu inventario con el de Ciencuadras sin guardar el `listing_id`:

```php
$snap = $sdk->listings()->findByExternalCode(
    clientCookie: $cookie,
    externalCode: 'INT-1001',
);

if ($snap === null) {
    // no existe todavía → crearlo
} else {
    // ya existe → actualizarlo con $snap->id()
}
```

> Solo busca dentro de la primera página de resultados. Para colecciones grandes pasa
> `['page' => N]` en `$extraQuery`.

## Tareas asincrónicas

Publicar, actualizar o cambiar el estado de un inmueble **no es inmediato**: Ciencuadras encola el
trabajo y devuelve un `task_id`. El ciclo de vida es:

```
READY → RUNNING → COMPLETED | FORWARDED | ERROR
```

| Estado | Significado |
| --- | --- |
| `READY` | Tarea creada, aún no iniciada |
| `RUNNING` | En ejecución |
| `COMPLETED` | Terminó sin errores |
| `FORWARDED` | Recibida pero no procesada: ya llegó un request idéntico el mismo día (duplicado). **Se considera éxito** |
| `ERROR` | Error al crear o ejecutar la tarea |

### Polling automático

```php
$response = $sdk->listings()->create($listing);
$taskId   = $response['task']['id'];

$task = $sdk->tasks()->waitUntilSettled($taskId, [
    'maxAttempts'     => 20,     // default 30
    'intervalSeconds' => 3,      // default 3
    'throwOnTimeout'  => true,   // default true
]);

if ($task->isSuccessful()) {
    foreach ($task->contentItems() as $item) {
        // $item['listing_id'], $item['fr_property_id'], $item['external_code'],
        // $item['status'], $item['multimedia']
    }
} elseif ($task->isFailed()) {
    // revisar $task->raw()['messages']
}
```

`waitUntilSettled()` tolera errores transitorios: si una consulta falla, reintenta hasta agotar
`maxAttempts`. Con `throwOnTimeout => false` devuelve el último snapshot en lugar de lanzar
`RuntimeException`.

### Consulta puntual

```php
$task = $sdk->tasks()->getSnapshot($taskId);

$task->id();
$task->status();        // TaskStatus enum
$task->isPending();     // READY | RUNNING
$task->isSettled();     // COMPLETED | FORWARDED | ERROR
$task->isSuccessful();  // COMPLETED | FORWARDED
$task->isFailed();      // ERROR
$task->contentItems();  // resultado por inmueble
$task->raw();           // objeto task completo
```

> En producción, **prefiere webhooks sobre polling**: consume menos cuota y no te expone al
> throttling (`429`).

## Webhooks

Ciencuadras notifica el resultado de cada tarea a la URL que registres.

### 1. Suscribir tu endpoint

```php
$sdk->webhooks()->subscribeTarget(
    integratorId: '696d939e-4cc3-43ac-a312-6bf2e7f15868',
    targetUrl: 'https://midominio.com/webhooks/ciencuadras',
);
```

### 2. Suscripción idempotente

La API no expone un `GET` para consultar la suscripción activa, así que el SDK implementa
*desired state*: guarda tú la última URL suscrita y pásala en cada arranque.

```php
$result = $sdk->webhooks()->subscribeTargetIfChanged(
    integratorId: $integratorId,
    targetUrl: 'https://midominio.com/webhooks/ciencuadras',
    knownUrl: Cache::get('ciencuadras_webhook_url'),
);

if ($result->subscribed) {
    Cache::put('ciencuadras_webhook_url', $result->url);
}
```

`$result->subscribed` es `false` cuando no hubo llamada porque la URL no cambió.

### 3. Recibir y validar el callback

```php
use Ciencuadras\Sdk\Webhook\WebhookNotification;
use Ciencuadras\Sdk\Exception\WebhookException;

try {
    $notification = WebhookNotification::fromGlobals();

    // Comparación en tiempo constante (hash_equals) de HUB.ID y VERIFY-TOKEN
    $notification->assertAuthorized(
        expectedHubId: getenv('CIENCUADRAS_WEBHOOK_HUB_ID'),
        expectedVerifyToken: getenv('CIENCUADRAS_WEBHOOK_VERIFY_TOKEN'),
    );

    match ($notification->event()) {
        'LISTING_CORE'   => procesarPublicacion($notification->listingResults()),
        'LISTING_STATUS' => procesarCambioEstado($notification->listingStatusUpdates()),
        default          => null,
    };

    http_response_code(200);
} catch (WebhookException $e) {
    http_response_code(401);
}
```

`listingResults()` normaliza cada inmueble del callback y le adjunta el error correspondiente
tomado de `task.messages.listings`:

```php
[
    'listing_id'        => '07bcf513-...',
    'external_code'     => 'INT-1001',
    'fr_property_id'    => 10002073,
    'processing_status' => 'COMPLETED',   // ERROR | COMPLETED | FORWARDED
    'multimedia'        => [...],
    'error'             => null,
]
```

Guarda `fr_property_id`: es el código que se muestra al usuario final en el portal.

En frameworks donde ya tienes el request parseado, usa `fromRequest()`:

```php
$notification = WebhookNotification::fromRequest(
    rawBody: $request->getContent(),
    headers: $request->headers->all(),
);
```

Detalle completo en [`docs/webhooks.md`](docs/webhooks.md).

## Catálogos: clientes, agentes, ubicaciones y categorías

```php
// Clientes (inmobiliarias) con su cupo de publicación
$sdk->clients()->all();
// [['id' => uuid, 'name' => ..., 'initial_quota' => ..., 'remained_quota' => ..., 'used_quota' => ..., 'percentage_used_quota' => ...]]

$sdk->clients()->get($clientId);

// Agentes/sucursales del cliente
$sdk->clients()->agents($clientId);            // [['id' => 42, 'email' => ...]]
$sdk->clients()->resolveSingleAgentId($clientId); // int; lanza RuntimeException si hay 0 o >1

// Ubicaciones: país, departamento, ciudad, barrio, zona, localidad, comuna
$sdk->locations()->search('Chapinero');
// [['id' => uuid, 'name' => ..., 'location_type' => 'NEIGHBOURHOOD', 'country' => ..., 'state' => ..., 'city' => ...]]

// Características/amenidades para el campo `categories`
$sdk->categories()->list();
$sdk->categories()->list(['search' => 'piscina']);
```

El `id` que devuelve `locations()->search()` para un barrio es el que va en
`locations.location_main_id` del inmueble.

## Validación de payloads

El SDK valida **antes** de gastar un request. Por defecto verifica los campos requeridos de
primer nivel; el modo estricto añade tipos, enums y requeridos anidados.

```php
use Ciencuadras\Sdk\Schema\SchemaCatalog;
use Ciencuadras\Sdk\Schema\ListingPayloadValidator;

$validator = new ListingPayloadValidator(new SchemaCatalog());

try {
    $validator->validateCreatePayload([$listing], strict: true);
} catch (InvalidArgumentException $e) {
    // "Field `offer` at index 0 has invalid value \"venta\". Allowed: \"sell\", \"rent\", \"lease\"."
    // "Missing required nested field `locations.location_point` at index 0."
}
```

### Inspeccionar el OpenAPI en runtime

Útil para construir formularios dinámicos o para depurar sin abrir el Swagger:

```php
$catalog = $sdk->schemaCatalog();

$catalog->title();                       // "Ciencuadras API: Integration Partners"
$catalog->version();                     // "1.0.0"
$catalog->operations();                  // [['method' => 'POST', 'path' => '/listing', 'operationId' => ...], ...]
$catalog->listingCreateRequiredFields(); // ['description', 'external_code', 'client_id', ...]
$catalog->listingUpdateRequiredFields();
$catalog->listingStatusRequiredFields();
$catalog->listingCreateItemSchema();     // schema completo con properties y enums
$catalog->schema('ListingPOST');
```

Puedes apuntar a otro snapshot OpenAPI: `new SchemaCatalog('/ruta/a/mi-openapi.json')`.

## Manejo de errores

```
RuntimeException
├── ApiException        → la API respondió con un status fuera de 2xx
├── TransportException  → falló la capa de red (timeout, DNS, TLS)
└── WebhookException    → cuerpo o headers de webhook inválidos

InvalidArgumentException → payload inválido detectado localmente (antes del request)
```

```php
use Ciencuadras\Sdk\Exception\ApiException;
use Ciencuadras\Sdk\Exception\TransportException;

try {
    $sdk->listings()->create($listing);
} catch (InvalidArgumentException $e) {
    // Payload mal construido: NO se envió nada. Corrige y reintenta.
} catch (ApiException $e) {
    $e->statusCode();         // 401, 404, 429, 500, 502…
    $e->firstErrorMessage();  // busca message / detail / errors[0]
    $e->defaultCode();        // defaultCode / code / error_code
    $e->trackingId();         // x-tracking-id / x-request-id / x-correlation-id
    $e->json();               // cuerpo decodificado
    $e->response();           // ApiResponse completo
} catch (TransportException $e) {
    // Red caída o timeout → reintenta con backoff
}
```

### Códigos de error de Ciencuadras

| Status | Cuerpo típico | Qué hacer |
| --- | --- | --- |
| `400` | `{"message": "Object not found"}` | Revisa ids y estructura del payload |
| `401` | `{"message": "Invalid authentication credentials"}` | API key inválida, vencida o del ambiente equivocado |
| `404` | `{"message": "Integrator id not found"}` | `integrator_id` / `listing_id` inexistente |
| `429` | `{"detail": "Request was throttled. Expected available in 59 seconds."}` | Throttling: espera y reintenta con backoff. Reduce el polling y agrupa en lotes |
| `500` | `{"error": {"tracking_id": "...", "message": "Internal Error"}}` | Reporta el `tracking_id` a soporte de Ciencuadras |
| `502` | `{"message": "An invalid response was received from the upstream server"}` | Transitorio: reintenta |

Siempre registra `trackingId()` en tus logs: es lo primero que pide soporte.

### Errores por inmueble

Un `200` en la creación **no significa éxito**. Los errores por inmueble llegan en la tarea
(`task.messages.listings[]` y `task.messages.images[]`) o en el webhook:

```php
$task = $sdk->tasks()->waitUntilSettled($taskId);

foreach ($task->raw()['messages']['listings'] ?? [] as $problema) {
    // $problema['listing_id'], $problema['external_code'],
    // $problema['error']['message'], $problema['error']['tracking_id'],
    // $problema['error']['field']['description'] / ['status_code'] / ['default_code']
}

foreach ($task->raw()['messages']['images'] ?? [] as $problema) {
    // Ej. "Invalid url" con default_code "invalid_value"
}
```

## Uso en Laravel y WordPress

### Laravel — service provider

`config/services.php`:

```php
'ciencuadras' => [
    'api_key'   => env('CIENCUADRAS_API_KEY'),
    'base_url'  => env('CIENCUADRAS_BASE_URL', \Ciencuadras\Sdk\Config::BASE_URL_PRODUCTION),
    'client_id' => env('CIENCUADRAS_CLIENT_ID'),
    'cookie'    => env('CIENCUADRAS_CLIENT_COOKIE'),
],
```

`app/Providers/AppServiceProvider.php`:

```php
use Ciencuadras\Sdk\Config;
use Ciencuadras\Sdk\CiencuadrasClient;

public function register(): void
{
    $this->app->singleton(CiencuadrasClient::class, fn () => new CiencuadrasClient(
        new Config(
            apiKey: config('services.ciencuadras.api_key'),
            baseUrl: config('services.ciencuadras.base_url'),
            timeoutSeconds: 30,
        )
    ));
}
```

Ruta del webhook (recuerda excluirla de la verificación CSRF):

```php
Route::post('/webhooks/ciencuadras', function (Request $request) {
    $notification = WebhookNotification::fromRequest($request->getContent(), $request->headers->all());
    $notification->assertAuthorized(config('services.ciencuadras.hub_id'), config('services.ciencuadras.verify_token'));

    ProcesarWebhookCiencuadras::dispatch($notification->listingResults());

    return response()->json(['ok' => true]);
})->withoutMiddleware(VerifyCsrfToken::class);
```

Publica desde una cola para no bloquear el request del usuario:

```php
class PublicarInmuebleJob implements ShouldQueue
{
    public function handle(CiencuadrasClient $sdk): void
    {
        $response = $sdk->listings()->create($this->payload);
        $this->inmueble->update(['ciencuadras_task_id' => $response['task']['id']]);
    }
}
```

### WordPress

```php
add_action('init', function () {
    $sdk = new CiencuadrasClient(new Config(get_option('homlity_ciencuadras_api_key')));
    // ...
});

// Endpoint del webhook con la REST API de WordPress
add_action('rest_api_init', function () {
    register_rest_route('homlity/v1', '/ciencuadras', [
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => function (WP_REST_Request $request) {
            $notification = WebhookNotification::fromRequest(
                $request->get_body(),
                $request->get_headers_flat() ?? []
            );
            $notification->assertAuthorized(HUB_ID, VERIFY_TOKEN);
            return ['ok' => true];
        },
    ]);
});
```

El plugin [Homlity Real Estate](https://homlity.com/desarrolladores/) expone hooks y filtros para
enganchar este SDK sin modificar el core.

## Testing y HTTP client propio

`CiencuadrasClient` acepta cualquier implementación de `HttpClientInterface`, así que puedes
sustituir cURL por Guzzle, Symfony HttpClient o un doble de prueba.

```php
use Ciencuadras\Sdk\Http\ApiResponse;
use Ciencuadras\Sdk\Http\HttpClientInterface;

final class FakeHttpClient implements HttpClientInterface
{
    public array $requests = [];

    public function __construct(private ApiResponse $response) {}

    public function request(string $method, string $path, array $options = []): ApiResponse
    {
        $this->requests[] = compact('method', 'path', 'options');
        return $this->response;
    }
}

$http = new FakeHttpClient(new ApiResponse(200, [], json_encode([
    'task' => ['id' => 'task-1', 'status' => 'COMPLETED'],
])));

$sdk = new CiencuadrasClient(new Config('fake-key'), $http);
$sdk->listings()->create($listing);

// Aserciones sobre $http->requests
```

Para `waitUntilSettled()` puedes inyectar el `sleepFn` y evitar esperas reales en tests:

```php
$sdk->tasks()->waitUntilSettled('task-1', [
    'intervalSeconds' => 0,
    'sleepFn'         => static fn (int $s) => null,
]);
```

Suite del proyecto:

```bash
composer install
composer test          # o ./vendor/bin/phpunit
```

## Documentación extendida

| Documento | Contenido |
| --- | --- |
| [`docs/getting-started.md`](docs/getting-started.md) | Guía paso a paso de la primera integración |
| [`docs/api-reference.md`](docs/api-reference.md) | Endpoints, métodos del SDK, headers y errores |
| [`docs/listing-parameters.md`](docs/listing-parameters.md) | Todos los campos del inmueble con sus enums |
| [`docs/tasks.md`](docs/tasks.md) | Tareas asincrónicas y estrategias de polling |
| [`docs/webhooks.md`](docs/webhooks.md) | Suscripción, validación y procesamiento de callbacks |
| [`docs/errors.md`](docs/errors.md) | Catálogo de errores y solución de problemas |
| `resources/openapi/ciencuadras-integradores-1.0.0.json` | Snapshot OpenAPI que usa el SDK |
| [`examples/`](examples/) | Scripts ejecutables listos para copiar |
| [`CONTRIBUTING.md`](CONTRIBUTING.md) | Cómo aportar al SDK |

## Otros SDKs de Homlity

Todos se instalan con `composer require homlity/<paquete>`:

| Paquete | Integración |
| --- | --- |
| [`homlity/sdk-ciencuadras`](https://github.com/homlity/sdk-ciencuadras) | Portal Ciencuadras |
| [`homlity/sdk-fincaraiz`](https://github.com/homlity/sdk-fincaraiz) | Portal Finca Raíz |
| [`homlity/sdk-metrocuadrado`](https://github.com/homlity/sdk-metrocuadrado) | Portal Metrocuadrado |
| [`homlity/sdk-proppit`](https://github.com/homlity/sdk-proppit) | Proppit |
| [`homlity/sdk-mobilia`](https://github.com/homlity/sdk-mobilia) | Mobilia |
| [`homlity/sdk-domus`](https://github.com/homlity/sdk-domus) | Domus |
| [`homlity/sdk-wasi-php8`](https://github.com/homlity/sdk-wasi-php8) | CRM Wasi |
| [`homlity/softinm-sdk`](https://github.com/homlity/softinm-sdk) | Softinm |
| [`homlity/sdk-smartinmobiliario`](https://github.com/homlity/sdk-smartinmobiliario) | Smart Inmobiliario |
| [`homlity/sdk-chat-homlity`](https://github.com/homlity/sdk-chat-homlity) | Chat y chatbots de WhatsApp con IA |

Catálogo completo y guías: <https://homlity.com/desarrolladores/>

## Contribuir

El desarrollo se centraliza en <https://github.com/homlity>. Guía completa en
[CONTRIBUTING.md](CONTRIBUTING.md).

1. Abre un *issue* describiendo el bug o la propuesta.
2. Haz fork y crea una rama descriptiva (`fix/listing-status-enum`).
3. Agrega pruebas: `composer test` debe pasar en verde.
4. Envía el pull request explicando el cambio y su impacto.

Reporta vulnerabilidades de seguridad de forma privada a través de <https://homlity.com/>, no en un
issue público.

## Licencia

MIT. Ver [LICENSE](LICENSE).

---

<p align="center">
  <a href="https://homlity.com/"><img src="https://homlity.com/wp-content/uploads/2026/08/Diseno-sin-titulo-1-e1787507729419-1024x338.webp" alt="Homlity" width="180"></a><br>
  <sub>Hecho para el ecosistema inmobiliario · <a href="https://homlity.com/">homlity.com</a> · <a href="https://homlity.com/desarrolladores/">Desarrolladores</a></sub>
</p>
