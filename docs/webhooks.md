<p align="center">
  <a href="https://homlity.com/"><img src="https://homlity.com/wp-content/uploads/2026/08/Diseno-sin-titulo-1-e1787507729419-1024x338.webp" alt="Homlity" width="240"></a>
</p>

# Webhooks

Los webhooks son la forma recomendada de conocer el resultado de cada operación en producción:
sin polling, sin throttling y en tiempo real.

Portal de desarrolladores: <https://homlity.com/desarrolladores/> · Página principal: <https://homlity.com/>

---

## Flujo completo

```
1. Tu integrador publica o actualiza inmuebles       →  listings()->create() / update() / updateStatus()
2. Ciencuadras encola la tarea y devuelve un task_id →  ['task' => ['id' => ..., 'status' => 'READY']]
3. Ciencuadras procesa la tarea
4. Ciencuadras hace POST a la URL que tengas suscrita
5. Tú validas los headers HUB.ID y VERIFY-TOKEN
6. Lees el resultado por inmueble y respondes 200
```

## 1. Suscribir tu endpoint

```php
use Ciencuadras\Sdk\Config;
use Ciencuadras\Sdk\CiencuadrasClient;

$sdk = new CiencuadrasClient(new Config(getenv('CIENCUADRAS_API_KEY')));

$sdk->webhooks()->subscribeTarget(
    integratorId: '696d939e-4cc3-43ac-a312-6bf2e7f15868',
    targetUrl: 'https://midominio.com/webhooks/ciencuadras',
);
```

`subscribeTarget()` valida que la URL sea absoluta y bien formada antes de enviarla; si no lo es,
lanza `InvalidArgumentException`.

Para construir el payload manualmente:

```php
use Ciencuadras\Sdk\Webhook\WebhookSubscription;

$payload = WebhookSubscription::target('https://midominio.com/webhooks/ciencuadras');
// ['target' => 'https://midominio.com/webhooks/ciencuadras']

$sdk->webhooks()->subscribe($integratorId, $payload);
```

Para dar de baja la suscripción:

```php
$sdk->webhooks()->unsubscribe($integratorId);
```

### Requisitos del endpoint

- **HTTPS** con certificado válido.
- Accesible públicamente (sin VPN ni autenticación básica).
- Responde rápido: valida, encola y devuelve `200`. No proceses de forma síncrona.
- Idempotente: puede llegar el mismo evento más de una vez.

## 2. Suscripción idempotente

La API de Ciencuadras **no expone un `GET`** para consultar la suscripción activa. El SDK
implementa entonces un patrón de *desired state*: tú guardas la última URL suscrita con éxito y la
pasas en cada arranque; el SDK evita el `POST` si no hubo cambio.

```php
use Ciencuadras\Sdk\Webhook\SubscriptionResult;

$knownUrl = Cache::get('ciencuadras_webhook_url');   // BD, cache, variable de entorno…

$result = $sdk->webhooks()->subscribeTargetIfChanged(
    integratorId: '696d939e-4cc3-43ac-a312-6bf2e7f15868',
    targetUrl: 'https://midominio.com/webhooks/ciencuadras',
    knownUrl: $knownUrl,
);

if ($result->subscribed) {
    // Solo entra aquí si la URL era diferente o desconocida.
    Cache::put('ciencuadras_webhook_url', $result->url);
    logger()->info('Webhook resuscrito', ['respuesta' => $result->response]);
}
```

`SubscriptionResult`:

| Propiedad | Tipo | Descripción |
| --- | --- | --- |
| `subscribed` | `bool` | `true` si se hizo la llamada; `false` si la URL ya estaba vigente |
| `url` | `string` | URL normalizada |
| `response` | `mixed` | Respuesta cruda de la API, o `null` si no hubo llamada |

Llámalo en el arranque de la aplicación o en un comando de despliegue; es barato y seguro.

## 3. Recibir y validar el callback

```php
use Ciencuadras\Sdk\Webhook\WebhookNotification;
use Ciencuadras\Sdk\Exception\WebhookException;

header('Content-Type: application/json');

try {
    $notification = WebhookNotification::fromGlobals();

    $notification->assertAuthorized(
        expectedHubId: getenv('CIENCUADRAS_WEBHOOK_HUB_ID'),
        expectedVerifyToken: getenv('CIENCUADRAS_WEBHOOK_VERIFY_TOKEN'),
    );

    match ($notification->event()) {
        'LISTING_CORE'   => encolarResultadoPublicacion($notification->listingResults()),
        'LISTING_STATUS' => encolarCambioEstado($notification->listingStatusUpdates()),
        default          => null,
    };

    http_response_code(200);
    echo json_encode(['received' => true]);
} catch (WebhookException | InvalidArgumentException $e) {
    http_response_code(401);
    echo json_encode(['received' => false, 'error' => $e->getMessage()]);
}
```

### Constructores disponibles

| Método | Cuándo usarlo |
| --- | --- |
| `WebhookNotification::fromGlobals()` | PHP puro: lee `php://input` y los headers del request |
| `WebhookNotification::fromGlobals($rawBody)` | Ya tienes el cuerpo pero quieres los headers globales |
| `WebhookNotification::fromRequest($rawBody, $headers)` | Frameworks (Laravel, Symfony, WordPress) donde el request ya está parseado |

`fromGlobals()` usa `getallheaders()` si está disponible y cae a `$_SERVER['HTTP_*']` si no.
Todos los nombres de header se normalizan a minúsculas.

### Validación de autenticidad

`assertAuthorized()` compara `HUB.ID` y `VERIFY-TOKEN` con `hash_equals()` (tiempo constante,
resistente a *timing attacks*). Lanza `WebhookException` si no coinciden, e
`InvalidArgumentException` si los valores esperados vienen vacíos.

Si prefieres decidir tú qué hacer:

```php
if (! $notification->isAuthorized($hubId, $verifyToken)) {
    http_response_code(401);
    return;
}
```

## 4. Leer el contenido

### API de `WebhookNotification`

| Método | Devuelve |
| --- | --- |
| `payload()` | `array` — cuerpo completo decodificado |
| `headers()` | `array<string,string>` — headers normalizados a minúsculas |
| `header(string $name)` | `string\|null` |
| `task()` | `array` — el objeto `task` del payload |
| `event()` | `string\|null` — `LISTING_CORE` o `LISTING_STATUS` |
| `taskStatus()` | `string\|null` — `COMPLETED`, `FORWARDED`, `ERROR`… |
| `isListingStatusEvent()` | `bool` |
| `contentItems()` | `list<array>` — `task.content` crudo |
| `listingResults()` | `list<array>` — resultado normalizado por inmueble, con el error adjunto |
| `listingStatusUpdates()` | `list<array>` — igual que `listingResults()` pero vacío si el evento no es `LISTING_STATUS` |

### Eventos

| `event` | Se dispara con |
| --- | --- |
| `LISTING_CORE` | `listings()->create()` y `listings()->update()` |
| `LISTING_STATUS` | `listings()->updateStatus()` |

### `listingResults()`

Normaliza `task.content[]` y le adjunta el error correspondiente tomado de
`task.messages.listings[]`, cruzando por `listing_id` o por `external_code`:

```php
[
    'listing_id'        => '07bcf513-d39a-42ff-8370-f42d39cd9494',
    'external_code'     => 'INT-1001',
    'fr_property_id'    => 10002073,
    'processing_status' => 'COMPLETED',   // ERROR | COMPLETED | FORWARDED
    'multimedia'        => [
        ['id' => '...', 'url' => 'https://...', 'status' => 'COMPLETED'],
    ],
    'error'             => null,          // o el objeto de error del inmueble
]
```

| Campo | Para qué sirve |
| --- | --- |
| `listing_id` | Id interno de Ciencuadras. **Guárdalo**: lo necesitas para actualizar o eliminar |
| `external_code` | Tu código interno; úsalo para localizar el registro en tu sistema |
| `fr_property_id` | Código numérico visible al usuario final en el portal |
| `processing_status` | Resultado del procesamiento **de ese inmueble** |
| `multimedia` | Estado de cada imagen |
| `error` | `null` o el detalle del fallo |

> **`processing_status` no es el estado de publicación.** Según el OpenAPI, el callback de
> `LISTING_STATUS` expone el resultado del *procesamiento de la operación*, no el estado final del
> inmueble (`ACTIVE` / `DELETED`). Si necesitas el estado real, consúltalo con
> `listings()->getSnapshot($listingId)->status()`.

### Ejemplo de procesamiento

```php
foreach ($notification->listingResults() as $r) {
    $inmueble = Inmueble::where('codigo', $r['external_code'])->first();

    if ($inmueble === null) {
        continue;
    }

    if ($r['processing_status'] === 'ERROR') {
        $inmueble->update([
            'estado_sync' => 'ERROR',
            'error_sync'  => $r['error']['message'] ?? 'Error desconocido',
        ]);
        continue;
    }

    $inmueble->update([
        'estado_sync'          => 'PUBLICADO',
        'ciencuadras_id'       => $r['listing_id'],
        'ciencuadras_codigo'   => $r['fr_property_id'],
        'error_sync'           => null,
        'sincronizado_en'      => now(),
    ]);
}
```

## Integración por framework

### Laravel

```php
// routes/web.php — recuerda excluir la ruta de la verificación CSRF
Route::post('/webhooks/ciencuadras', WebhookCiencuadrasController::class)
    ->withoutMiddleware([VerifyCsrfToken::class]);
```

```php
final class WebhookCiencuadrasController
{
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $notification = WebhookNotification::fromRequest(
                rawBody: $request->getContent(),
                headers: array_map(fn ($v) => is_array($v) ? ($v[0] ?? '') : $v, $request->headers->all()),
            );

            $notification->assertAuthorized(
                config('services.ciencuadras.hub_id'),
                config('services.ciencuadras.verify_token'),
            );
        } catch (WebhookException|\InvalidArgumentException $e) {
            return response()->json(['received' => false], 401);
        }

        // Procesa en segundo plano y responde rápido
        ProcesarWebhookCiencuadras::dispatch($notification->payload());

        return response()->json(['received' => true]);
    }
}
```

### WordPress

```php
add_action('rest_api_init', function () {
    register_rest_route('homlity/v1', '/ciencuadras', [
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'callback'            => function (WP_REST_Request $request) {
            $headers = [];
            foreach ($request->get_headers() as $name => $values) {
                $headers[$name] = is_array($values) ? ($values[0] ?? '') : $values;
            }

            try {
                $notification = WebhookNotification::fromRequest($request->get_body(), $headers);
                $notification->assertAuthorized(
                    get_option('homlity_ciencuadras_hub_id'),
                    get_option('homlity_ciencuadras_verify_token'),
                );
            } catch (Throwable $e) {
                return new WP_REST_Response(['received' => false], 401);
            }

            do_action('homlity_ciencuadras_webhook', $notification->listingResults());

            return new WP_REST_Response(['received' => true], 200);
        },
    ]);
});
```

## Probar el webhook en local

### Simular una entrega

```php
use Ciencuadras\Sdk\Webhook\WebhookNotification;

$notification = WebhookNotification::fromRequest(
    rawBody: json_encode([
        'task' => [
            'id'      => 'task-1',
            'event'   => 'LISTING_CORE',
            'status'  => 'COMPLETED',
            'content' => [[
                'status'         => 'COMPLETED',
                'listing_id'     => '07bcf513-d39a-42ff-8370-f42d39cd9494',
                'external_code'  => 'INT-1001',
                'fr_property_id' => 10002073,
                'multimedia'     => [],
            ]],
        ],
    ]),
    headers: ['HUB.ID' => 'mi-hub', 'VERIFY-TOKEN' => 'mi-token'],
);

$notification->assertAuthorized('mi-hub', 'mi-token');
```

### Con `curl`

```bash
curl -X POST https://midominio.com/webhooks/ciencuadras \
  -H 'Content-Type: application/json' \
  -H 'HUB.ID: mi-hub' \
  -H 'VERIFY-TOKEN: mi-token' \
  -d '{"task":{"id":"task-1","event":"LISTING_CORE","status":"COMPLETED","content":[]}}'
```

### Exponer localhost

Usa un túnel (ngrok, Cloudflare Tunnel, Expose) y suscribe la URL temporal con
`subscribeTargetIfChanged()`; cuando vuelvas a la URL definitiva, el SDK detectará el cambio y
re-suscribirá solo.

## Solución de problemas

| Síntoma | Causa probable |
| --- | --- |
| `401` en tu endpoint | `HUB.ID` / `VERIFY-TOKEN` incorrectos, o tu proxy elimina headers con punto |
| `Webhook body is empty.` | Un middleware ya consumió `php://input`. Usa `fromRequest()` |
| `Webhook body must be valid JSON.` | El cuerpo llegó truncado o con `Content-Type` incorrecto |
| No llegan callbacks | Verifica que la suscripción esté vigente y que tu URL sea pública y HTTPS |
| Llegan duplicados | Es esperado. Haz tu procesamiento idempotente usando `external_code` |
| `listingStatusUpdates()` vacío | El evento no es `LISTING_STATUS`; usa `listingResults()` |

## Ejemplos ejecutables

- [`examples/subscribe-webhook.php`](../examples/subscribe-webhook.php)
- [`examples/receive-webhook.php`](../examples/receive-webhook.php)
