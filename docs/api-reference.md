<p align="center">
  <a href="https://homlity.com/"><img src="https://homlity.com/wp-content/uploads/2026/08/Diseno-sin-titulo-1-e1787507729419-1024x338.webp" alt="Homlity" width="240"></a>
</p>

# Referencia de API

Mapa completo entre el API de Integradores de Ciencuadras y los métodos del SDK.

Fuente: `resources/openapi/ciencuadras-integradores-1.0.0.json` (SwaggerHub *Integradores* v1.0.0).

Portal de desarrolladores: <https://homlity.com/desarrolladores/> · Página principal: <https://homlity.com/>

---

## Autenticación

| Header | Valor | Aplica a |
| --- | --- | --- |
| `apikey` | Tu API key | Prácticamente todos los endpoints |
| `X-API-KEY` | Tu API key | Alias usado por algunas secciones del OpenAPI |
| `Cookie` | Cookie de cliente | Solo `GET /listing` |
| `HUB.ID` | Id del hub | Solo `POST /api/ciencuadras/` (entrega de eventos) |
| `VERIFY-TOKEN` | Token de verificación | Solo `POST /api/ciencuadras/` |

El OpenAPI mezcla `apikey` y `X-API-KEY` en distintas secciones, así que **el SDK envía ambos
headers automáticamente con el mismo token** para maximizar compatibilidad. También añade
`Accept: application/json` y, cuando hay cuerpo, `Content-Type: application/json`.

## Tabla de endpoints

| Método | Path | OperationId | Método del SDK |
| --- | --- | --- | --- |
| `GET` | `/location/{name}` | `getLocation` | `locations()->search(string $name)` |
| `GET` | `/category` | `getCategory` | `categories()->list(array $query = [])` |
| `GET` | `/client/` | `getClients` | `clients()->all()` |
| `GET` | `/client/{client_id}` | `getClientId` | `clients()->get(string $clientId)` |
| `GET` | `/client/{client_id}/agent` | `getAgent` | `clients()->agents(string $clientId)` |
| `GET` | `/listing` | `getListingClient` | `listings()->list(string $clientCookie, array $query = [])` |
| `GET` | `/listing/{listing_id}` | `getListing` | `listings()->get()` · `listings()->getSnapshot()` |
| `POST` | `/listing` | `listing_post` | `listings()->create(array $listings)` |
| `PATCH` | `/listing` | `listing_patch` | `listings()->update(array $listings)` |
| `PATCH` | `/listing/status` | `listing_status_patch` | `listings()->updateStatus(array $statuses)` |
| `POST` | `/validate-listing` | `listing_valdiate_post` | `listings()->validate(array $payload)` |
| `GET` | `/task/{task_id}` | `getTask` | `tasks()->get()` · `getSnapshot()` · `waitUntilSettled()` |
| `POST` | `/webhook/{id}/subscribe` | `postWebhookSubscribe` | `webhooks()->subscribe()` · `subscribeTarget()` · `subscribeTargetIfChanged()` |
| `POST` | `/webhook/{id}/unsubscribe` | `postWebhookUnsubscribe` | `webhooks()->unsubscribe(string $integratorId)` |
| `POST` | `/api/ciencuadras/` | `postWebhook` | `webhooks()->postEvent(string $hubId, string $verifyToken, array $payload)` |

---

## `listings()` — Inmuebles

### `create(array $listings): mixed`

`POST /listing`. Acepta un inmueble (array asociativo) o una lista de inmuebles; el SDK normaliza
ambas formas. Valida los campos requeridos antes de enviar y lanza `InvalidArgumentException` si
falta alguno.

Respuesta: `['task' => ['id' => uuid, 'status' => 'READY', 'created' => ..., 'integrator' => ...]]`

### `update(array $listings): mixed`

`PATCH /listing`. Mismo contrato que `create()` más el campo obligatorio `listing_id`.

Con `Config::fromHomlityEnv()` la ruta se toma de `CIENCUADRAS_{ENV}_UPDATE_ENDPOINT` y el verbo
cambia a `POST` (las rutas tipo portal no usan `PATCH`).

### `updateStatus(array $statuses): mixed`

`PATCH /listing/status`. Campos requeridos por item: `listing_id`, `client_id`, `status`
(`ACTIVE` o `DELETED`).

### `list(string $clientCookie, array $query = []): mixed`

`GET /listing`. **Requiere el header `Cookie`**, que se pasa como primer argumento.

| Query param | Valores |
| --- | --- |
| `search` | Texto libre |
| `ordering` | `property_detail__fr_property_id`, `-property_detail__fr_property_id`, `integrator_code`, `-integrator_code`, `created`, `-created`, `updated`, `-updated`, `status`, `-status` |
| `page` | Número de página |
| `page_size` | Tamaño de página |

Respuesta paginada: `['count' => int, 'next' => url|null, 'previous' => url|null, 'results' => [...]]`

### `get(string $listingId): mixed` · `getSnapshot(string $listingId): ListingSnapshot`

`GET /listing/{listing_id}`. `getSnapshot()` envuelve la respuesta con helpers de estado.

### `findByExternalCode(string $clientCookie, string $externalCode, array $extraQuery = []): ?ListingSnapshot`

Azúcar sobre `list()` para reconciliar por tu código interno. Devuelve `null` si no hay resultados.
Solo revisa la primera página; pasa `['page' => N]` en `$extraQuery` para colecciones grandes.

### `validate(array $payload): mixed`

`POST /validate-listing`. Requiere `client_id`. Opcionales: `fr_property_id[]`, `listing_id[]`,
`integrator_code[]`.

---

## `clients()` — Clientes y agentes

### `all(): mixed`

`GET /client/`. Devuelve las inmobiliarias asociadas al integrador con su cupo:

| Campo | Descripción |
| --- | --- |
| `id` | uuid del cliente |
| `name` | Nombre |
| `initial_quota` | Cupo inicial |
| `remained_quota` | Cupo disponible |
| `used_quota` | Cupo usado |
| `percentage_used_quota` | Porcentaje usado |

### `get(string $clientId): mixed`

`GET /client/{client_id}`.

### `agents(string $clientId): mixed`

`GET /client/{client_id}/agent`. Devuelve `[['id' => int, 'email' => string], ...]`.

### `resolveSingleAgentId(string $clientId): int`

Atajo para clientes con un único agente. Lanza `RuntimeException` si hay cero o más de uno.

---

## `locations()` — Ubicaciones

### `search(string $name): mixed`

`GET /location/{name}`. Devuelve una lista de coincidencias:

| Campo | Descripción |
| --- | --- |
| `id` | uuid — el valor que va en `locations.location_main_id` |
| `name` | Nombre de la ubicación |
| `location_type` | `COUNTRY`, `STATE`, `CITY`, `NEIGHBOURHOOD`, `ZONE`, `LOCALITY`, `COMMUNE` |
| `country` / `state` / `city` | Jerarquía; `null` si no aplica |

---

## `categories()` — Características

### `list(array $query = []): mixed`

`GET /category`. Devuelve `['total' => int, 'list' => ['hits' => [...]]]`. Los ids obtenidos aquí
son los que van en el campo `categories[]` del inmueble.

---

## `tasks()` — Tareas

### `get(string $taskId): mixed` · `getSnapshot(string $taskId): TaskSnapshot`

`GET /task/{task_id}`.

### `waitUntilSettled(string $taskId, array $options = []): TaskSnapshot`

Polling hasta estado terminal. Opciones: `maxAttempts` (30), `intervalSeconds` (3),
`throwOnTimeout` (true), `sleepFn`. Ver [`tasks.md`](tasks.md).

---

## `webhooks()` — Webhooks

### `subscribe(string $integratorId, array $payload): mixed`

`POST /webhook/{id}/subscribe`. Payload: `['target' => 'https://...']`.

### `subscribeTarget(string $integratorId, string $targetUrl): mixed`

Construye el payload y valida que la URL sea absoluta y bien formada.

### `subscribeTargetIfChanged(string $integratorId, string $targetUrl, ?string $knownUrl = null): SubscriptionResult`

Suscripción idempotente: omite el request si `$knownUrl` coincide con `$targetUrl`.

### `unsubscribe(string $integratorId): mixed`

`POST /webhook/{id}/unsubscribe`.

### `postEvent(string $hubId, string $verifyToken, array $payload): mixed`

`POST /api/ciencuadras/` con headers `HUB.ID` y `VERIFY-TOKEN`. Es el endpoint de **entrega** de
eventos definido en el OpenAPI; sirve para simular callbacks en pruebas.

---

## `schemaCatalog()` — Introspección del OpenAPI

```php
$catalog = $sdk->schemaCatalog();

$catalog->title();                        // "Ciencuadras API: Integration Partners"
$catalog->version();                      // "1.0.0"
$catalog->operations();                   // método, path, operationId y parámetros de cada operación
$catalog->schema('ListingPOST');          // schema crudo
$catalog->listingCreateRequiredFields();  // list<string>
$catalog->listingUpdateRequiredFields();
$catalog->listingStatusRequiredFields();
$catalog->listingCreateItemSchema();      // properties + enums + required anidados
$catalog->listingUpdateItemSchema();
$catalog->listingStatusItemSchema();
```

Esquemas disponibles en el snapshot: `ListingPOST`, `ListingPATCH`, `ListingStatus`,
`ValidateListig`, `ListingAddress`, `ListingLocations`, `ListingLocationsLocationPoint`,
`ListingContact`, `ListingContactEmails`, `ListingContactPhones`, `ListingClient`,
`ListingAdministration`, `ListingParkingAvailability`, `ListingSchedule`, `Multimedia`,
`MultimediaResponse`, `TaskResponse200`, `TaskGetResponse200`, `ClientResponse200`,
`AgentResponse200`, `CategoryResponse200`, `LocationResponse200`, `WebhookSubscribePOST`
y las variantes de error (`*Response400/401/404/429/500/502`).

---

## Capa HTTP

`CiencuadrasClient` acepta cualquier `HttpClientInterface`, por lo que puedes reemplazar cURL:

```php
interface HttpClientInterface
{
    public function request(string $method, string $path, array $options = []): ApiResponse;
}
```

Opciones soportadas: `query`, `headers`, `json`, `body`.

`ApiResponse` expone `statusCode()`, `headers()`, `body()`, `json()` (decodificación perezosa) e
`isSuccessful()`.

Rutas absolutas: si el `path` empieza por `http://` o `https://` se usa tal cual, ignorando la
base URL. Esto permite apuntar un endpoint concreto a otro host vía `Config::fromHomlityEnv()`.

---

## Códigos de respuesta

| Status | Cuerpo | Significado |
| --- | --- | --- |
| `200` | Recurso o `{"task": {...}}` | Éxito |
| `400` | `{"message": "Object not found"}` | Petición mal formada |
| `401` | `{"message": "Invalid authentication credentials"}` | Autenticación inválida |
| `404` | `{"message": "Integrator id not found"}` | Recurso inexistente |
| `429` | `{"detail": "Request was throttled. Expected available in 59 seconds."}` | Throttling |
| `500` | `{"error": {"tracking_id": "...", "message": "Internal Error"}}` | Error interno |
| `502` | `{"message": "An invalid response was received from the upstream server"}` | Upstream inválido |

Detalle y estrategias de reintento en [`errors.md`](errors.md).
