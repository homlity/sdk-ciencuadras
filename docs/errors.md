<p align="center">
  <a href="https://homlity.com/"><img src="https://homlity.com/wp-content/uploads/2026/08/Diseno-sin-titulo-1-e1787507729419-1024x338.webp" alt="Homlity" width="240"></a>
</p>

# Errores y solución de problemas

Catálogo de errores del SDK y del API de Integradores de Ciencuadras, con la acción recomendada
para cada uno.

Portal de desarrolladores: <https://homlity.com/desarrolladores/> · Página principal: <https://homlity.com/>

---

## Jerarquía de excepciones

```
\InvalidArgumentException
└── Payload inválido detectado localmente. NO se envió ningún request.

\RuntimeException
├── Ciencuadras\Sdk\Exception\ApiException        La API respondió con status fuera de 2xx
├── Ciencuadras\Sdk\Exception\TransportException  Falló la capa de red (cURL)
├── Ciencuadras\Sdk\Exception\WebhookException    Cuerpo o headers de webhook inválidos
└── Errores de flujo: resolveSingleAgentId(), waitUntilSettled() por timeout
```

Captura siempre `InvalidArgumentException` por separado: significa que el problema está en **tu**
payload y reintentar sin corregirlo no sirve de nada.

```php
use Ciencuadras\Sdk\Exception\ApiException;
use Ciencuadras\Sdk\Exception\TransportException;

try {
    $sdk->listings()->create($listing);
} catch (\InvalidArgumentException $e) {
    logger()->error('Payload inválido', ['msg' => $e->getMessage()]);
    // No reintentar: corregir el dato.
} catch (ApiException $e) {
    logger()->error('Ciencuadras API', [
        'status'   => $e->statusCode(),
        'code'     => $e->defaultCode(),
        'message'  => $e->firstErrorMessage(),
        'tracking' => $e->trackingId(),
    ]);
} catch (TransportException $e) {
    // Red caída, DNS, TLS o timeout → reintentar con backoff exponencial.
}
```

## `ApiException` — accesores de diagnóstico

| Método | Devuelve |
| --- | --- |
| `statusCode()` | `int\|null` — status HTTP |
| `firstErrorMessage()` | `string\|null` — busca en `message`, `detail`, `errors[0]`, `errors[0].message` |
| `defaultCode()` | `string\|null` — busca `defaultCode`, `code`, `error_code`, `errorCode` |
| `trackingId()` | `string\|null` — headers `x-tracking-id`, `x-request-id`, `x-correlation-id` |
| `json()` | `mixed` — cuerpo decodificado |
| `response()` | `ApiResponse\|null` — status, headers y body crudos |

El mensaje de la excepción incluye método, ruta, status y los primeros 500 caracteres del cuerpo:

```
API request failed [POST /listing] with status 401. Response: {"message":"Invalid authentication credentials"}
```

## Códigos HTTP del API

| Status | Cuerpo típico | Causa probable | Acción |
| --- | --- | --- | --- |
| `400` | `{"message": "Object not found"}` | Estructura del payload o ids incorrectos | Valida en modo estricto; revisa `client_id` y `listing_id` |
| `401` | `{"message": "Invalid authentication credentials"}` | API key inválida, vencida o de otro ambiente | Verifica la key y que la base URL corresponda al mismo ambiente |
| `404` | `{"message": "Integrator id not found"}` | `integrator_id` o `listing_id` inexistente | Confirma el id con Ciencuadras |
| `429` | `{"detail": "Request was throttled. Expected available in 59 seconds."}` | Throttling | Respeta el tiempo indicado; agrupa en lotes; cambia polling por webhooks |
| `500` | `{"error": {"tracking_id": "...", "message": "Internal Error"}}` | Error interno de Ciencuadras | Reintenta una vez; si persiste reporta el `tracking_id` |
| `502` | `{"message": "An invalid response was received from the upstream server"}` | Gateway/upstream caído | Transitorio: reintenta con backoff |

### Reintentos con backoff

```php
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

            // 429 indica el tiempo de espera en el mensaje; si no, backoff exponencial
            sleep(min(60, 2 ** $intento));
        } catch (TransportException $e) {
            if (++$intento >= $maxIntentos) {
                throw $e;
            }
            sleep(min(60, 2 ** $intento));
        }
    }
}

$response = conReintentos(fn () => $sdk->listings()->create($lote));
```

Nunca reintentes un `400`, `401` o `404`: el resultado será idéntico.

> Reintentar una creación es seguro respecto a duplicados: si Ciencuadras recibe la misma
> información el mismo día devuelve `FORWARDED` en vez de publicar dos veces.

## Errores de validación local

El SDK valida antes de gastar un request. Mensajes que puedes encontrar:

| Mensaje | Causa |
| --- | --- |
| `At least one create listing item is required.` | Pasaste un array vacío |
| `Missing required field \`price\` in create listing payload at index 0.` | Falta un campo obligatorio de primer nivel |
| `Payload item at index 2 must be an object/array.` | Un elemento del lote no es un array |
| `Missing required nested field \`locations.location_point\` at index 0.` | Falta un campo obligatorio anidado (modo estricto) |
| `Field \`price\` at index 0 must be of type number, got string.` | Tipo incorrecto (modo estricto) |
| `Field \`offer\` at index 0 has invalid value "venta". Allowed: "sell", "rent", "lease".` | Valor fuera del enum (modo estricto) |
| `Missing required field \`client_id\` for validate-listing payload.` | Falta `client_id` en `listings()->validate()` |
| `API key is required.` | `Config` construido con API key vacío |
| `Timeout must be greater than zero.` | `timeoutSeconds` inválido |
| `Webhook target URL must be a valid absolute URL.` | URL de suscripción relativa o mal formada |

Activa el modo estricto para atrapar enums y tipos antes de enviar:

```php
use Ciencuadras\Sdk\Schema\SchemaCatalog;
use Ciencuadras\Sdk\Schema\ListingPayloadValidator;

$validator = new ListingPayloadValidator(new SchemaCatalog());
$validator->validateCreatePayload([$listing], strict: true);
```

## Errores por inmueble (los más difíciles de ver)

Un `200` de la API **no** significa que el inmueble quedó publicado. Los errores reales llegan
dentro de la tarea o del webhook:

```php
$task = $sdk->tasks()->waitUntilSettled($taskId);

// 1. Estado individual
foreach ($task->contentItems() as $item) {
    if ($item['status'] === 'ERROR') {
        // buscar el detalle en messages
    }
}

// 2. Detalle del error
foreach ($task->raw()['messages']['listings'] ?? [] as $p) {
    echo $p['external_code'] . ': '
        . ($p['error']['message'] ?? '')
        . ' [' . ($p['error']['field']['default_code'] ?? '') . '] '
        . ($p['error']['field']['description'] ?? '') . PHP_EOL;
}

// 3. Imágenes rechazadas
foreach ($task->raw()['messages']['images'] ?? [] as $p) {
    echo 'Imagen ' . $p['url'] . ': ' . ($p['error']['field']['description'] ?? '') . PHP_EOL;
}
```

Estructura del error de campo:

| Campo | Ejemplo | Significado |
| --- | --- | --- |
| `error.message` | `Error Sending Data to Ciencuadras Core` | Descripción general |
| `error.tracking_id` | `a91ce369bb84…` | Código para soporte |
| `error.field.description` | `Invalid url` | Qué está mal |
| `error.field.status_code` | `119` | Código numérico del error de campo |
| `error.field.default_code` | `invalid_value` | Grupo del error |

## Problemas frecuentes

### Las fotos no aparecen

Ciencuadras **descarga** las imágenes desde tu URL. Requisitos:

- Accesibles públicamente, sin autenticación ni cookies.
- Sin redirecciones a dominios que bloqueen agentes desconocidos.
- URL absoluta con `https` y extensión válida.
- Máximo 30 por inmueble.
- `is_main => true` en exactamente una, con `sort_order => 1`.

Verifica el resultado en `task.messages.images` y en `content[].multimedia[].status`.

### `resolveSingleAgentId()` lanza excepción

```
Client "…" has no agents. Assign at least one agent before publishing.
Client "…" has 3 agents (12, 34, 56). Provide the agent id explicitly.
```

Lista los agentes con `clients()->agents($clientId)` y pasa el `client_agent` que corresponda.

### `GET /listing` devuelve 401 aunque el API key funciona en otros endpoints

Ese endpoint exige además el header `Cookie`. Pásalo en `listings()->list(clientCookie: ...)`.

### El webhook responde 401

- `HUB.ID` o `VERIFY-TOKEN` no coinciden (la comparación es exacta y en tiempo constante).
- Tu servidor o proxy elimina headers con punto (`HUB.ID`). Revisa la configuración de nginx/Apache.
- Estás leyendo `php://input` dos veces: usa `fromRequest()` pasando el cuerpo que ya tienes.

### `Webhook body is empty.`

El cuerpo llegó vacío. Suele pasar cuando un middleware ya consumió `php://input`. Solución:

```php
$notification = WebhookNotification::fromRequest($request->getContent(), $request->headers->all());
```

### Timeouts con lotes grandes

Sube el timeout y reduce el tamaño del lote:

```php
new Config(apiKey: $key, timeoutSeconds: 60);
```

### `Task "…" did not settle after 30 attempt(s)`

La tarea sigue en `READY`/`RUNNING`. No es un error: Ciencuadras está tardando. Usa
`throwOnTimeout => false` y reencola la verificación, o mejor, escucha el webhook.

### Publiqué dos veces y solo veo un inmueble

Correcto: la segunda tarea salió `FORWARDED` (duplicado del día). No es un fallo.

## Logging recomendado

```php
logger()->error('ciencuadras.error', [
    'operacion'     => 'listings.create',
    'external_code' => $listing['external_code'],
    'status'        => $e->statusCode(),
    'default_code'  => $e->defaultCode(),
    'message'       => $e->firstErrorMessage(),
    'tracking_id'   => $e->trackingId(),   // lo primero que pide soporte
    'task_id'       => $taskId ?? null,
]);
```

**Nunca** registres el API key ni el `VERIFY-TOKEN`.

## Reportar un problema

- Bugs del SDK: <https://github.com/homlity/sdk-ciencuadras/issues>
- Comunidad y guías: <https://homlity.com/desarrolladores/>
- Errores `500` del API: reporta el `tracking_id` a soporte de Ciencuadras.
- Vulnerabilidades de seguridad: repórtalas de forma privada vía <https://homlity.com/>, no en un
  issue público.
