<p align="center">
  <a href="https://homlity.com/"><img src="https://homlity.com/wp-content/uploads/2026/08/Diseno-sin-titulo-1-e1787507729419-1024x338.webp" alt="Homlity" width="240"></a>
</p>

# Primeros pasos · Homlity SDK Ciencuadras

Guía de la primera integración de punta a punta: desde `composer require` hasta un inmueble
publicado y confirmado en Ciencuadras.

- Página principal: <https://homlity.com/>
- Portal de desarrolladores: <https://homlity.com/desarrolladores/>
- Código fuente: <https://github.com/homlity/sdk-ciencuadras>
- Paquete: <https://packagist.org/packages/homlity/sdk-ciencuadras>

---

## Paso 0 · Qué necesitas antes de empezar

Ciencuadras entrega estos datos al completar el proceso de verificación de integrador:

- **API key** — autentica todas las llamadas.
- **`client_id`** (uuid) — la inmobiliaria a la que pertenecen los inmuebles.
- **`client_agent`** (entero, opcional) — la sucursal o agente.
- **Cookie de cliente** — header obligatorio del listado `GET /listing`.
- **`integrator_id`** (uuid) — para suscribir el webhook.
- **`HUB.ID` y `VERIFY-TOKEN`** — para validar los callbacks entrantes.

Mientras los consigues puedes trabajar contra el mock de SwaggerHub
(`Config::BASE_URL_MOCK`), que responde con los ejemplos del OpenAPI.

## Paso 1 · Instalar

```bash
composer require homlity/sdk-ciencuadras:dev-main
```

Requisitos: PHP `^8.1`, extensiones `curl` y `json`.

## Paso 2 · Configurar credenciales

`.env`:

```dotenv
CIENCUADRAS_API_KEY=xxxxxxxxxxxxxxxxxxxx
CIENCUADRAS_CLIENT_ID=df03d199-be5c-4c5c-98f6-849361cb7fae
CIENCUADRAS_CLIENT_COOKIE=78bea79c-1f6b-4e6d-a800-43fb327ed7c5
CIENCUADRAS_INTEGRATOR_ID=696d939e-4cc3-43ac-a312-6bf2e7f15868
CIENCUADRAS_WEBHOOK_HUB_ID=...
CIENCUADRAS_WEBHOOK_VERIFY_TOKEN=...
```

```php
use Ciencuadras\Sdk\Config;
use Ciencuadras\Sdk\CiencuadrasClient;

$sdk = new CiencuadrasClient(new Config(
    apiKey: getenv('CIENCUADRAS_API_KEY'),
    baseUrl: Config::BASE_URL_PRODUCTION,
    timeoutSeconds: 30,
));
```

## Paso 3 · Verificar la conexión y el cupo

`GET /client/` es la llamada más barata para comprobar que el API key funciona:

```php
$clients = $sdk->clients()->all();

foreach ($clients as $client) {
    printf(
        "%s (%s) — cupo usado %s/%s (%s%%)\n",
        $client['name'],
        $client['id'],
        $client['used_quota'],
        $client['initial_quota'],
        $client['percentage_used_quota'],
    );
}
```

Si obtienes `401 Invalid authentication credentials`, el API key no corresponde al ambiente.

## Paso 4 · Resolver los datos del inmueble

### Agente / sucursal

```php
$agentId = $sdk->clients()->resolveSingleAgentId($clientId);
```

Lanza `RuntimeException` si el cliente tiene cero agentes o más de uno; en ese caso listas los
agentes y eliges explícitamente:

```php
$agentes = $sdk->clients()->agents($clientId);
// [['id' => 42, 'email' => 'sucursal.norte@inmobiliaria.com'], ...]
```

### Barrio (`location_main_id`)

```php
$resultados = $sdk->locations()->search('Chapinero');

$barrio = array_values(array_filter(
    $resultados,
    fn ($l) => $l['location_type'] === 'NEIGHBOURHOOD',
))[0] ?? null;

$locationMainId = $barrio['id'];
```

Tipos de ubicación: `COUNTRY`, `STATE`, `CITY`, `NEIGHBOURHOOD`, `ZONE`, `LOCALITY`, `COMMUNE`.

### Características (`categories`)

```php
$categorias = $sdk->categories()->list();
// ['total' => N, 'list' => ['hits' => [...]]]
```

Guarda un mapa local entre tus amenidades y los ids de Ciencuadras; el catálogo cambia poco pero
conviene refrescarlo periódicamente.

## Paso 5 · Publicar el primer inmueble

```php
use Ciencuadras\Sdk\Exception\ApiException;

$listing = [
    'external_code'  => 'INT-1001',
    'client_id'      => getenv('CIENCUADRAS_CLIENT_ID'),
    'client_agent'   => $agentId,
    'offer'          => 'sell',
    'property_type'  => 'apartment',
    'description'    => 'Apartamento remodelado con vista abierta, cerca del parque.',
    'price'          => 450000000,
    'area'           => 78,
    'rooms'          => 3,
    'baths'          => 2,
    'stratum'        => 4,
    'address'        => ['address' => 'Calle 12 # 34-56'],
    'locations'      => [
        'location_point'   => ['latitude' => 4.729795079, 'longitude' => -74.044724493],
        'location_main_id' => $locationMainId,
        'view_map'         => 2,
    ],
    'listing_contact' => [
        'emails' => [['email' => 'ventas@midominio.com', 'is_main' => true, 'sort_order' => 0]],
        'phones' => [['phone' => '+573001112233', 'is_whatsapp_number' => true, 'sort_order' => 0]],
    ],
    'photos' => [
        ['sort_order' => 1, 'is_main' => true, 'image' => 'https://cdn.midominio.com/1.jpg'],
    ],
];

try {
    $response = $sdk->listings()->create($listing);
    $taskId   = $response['task']['id'];
} catch (InvalidArgumentException $e) {
    // El SDK detectó el problema ANTES de enviar nada
    echo 'Payload inválido: ' . $e->getMessage();
} catch (ApiException $e) {
    echo $e->statusCode() . ' → ' . ($e->firstErrorMessage() ?? $e->getMessage());
}
```

### Buenas prácticas del payload

- **`external_code` estable y único.** Es tu llave de reconciliación; no la cambies entre envíos.
- **Fotos accesibles públicamente.** Ciencuadras las descarga desde tu URL: sin autenticación,
  sin redirecciones raras y con extensión válida. Máximo 30.
- **Coordenadas reales.** `latitude`/`longitude` son obligatorias dentro de `location_point`.
- **`view_map`.** `0` punto exacto, `1` oculto, `2` solo zona.
- **Envía por lotes.** Un request con 50 inmuebles consume mucha menos cuota que 50 requests.

## Paso 6 · Confirmar que quedó publicado

La respuesta de creación solo confirma que la tarea se encoló. Para saber el resultado real:

```php
$task = $sdk->tasks()->waitUntilSettled($taskId, [
    'maxAttempts'     => 20,
    'intervalSeconds' => 3,
]);

if ($task->isSuccessful()) {
    foreach ($task->contentItems() as $item) {
        // Guarda estos dos ids en tu base de datos:
        $listingId    = $item['listing_id'];      // uuid interno, necesario para actualizar
        $frPropertyId = $item['fr_property_id'];  // código visible al usuario final
    }
} else {
    foreach ($task->raw()['messages']['listings'] ?? [] as $problema) {
        echo $problema['external_code'] . ': ' . ($problema['error']['message'] ?? 'error') . PHP_EOL;
    }
}
```

Detalles en [`tasks.md`](tasks.md).

## Paso 7 · Suscribir el webhook (recomendado)

En producción los webhooks reemplazan al polling: menos requests, menos throttling y resultados
en tiempo real.

```php
$result = $sdk->webhooks()->subscribeTargetIfChanged(
    integratorId: getenv('CIENCUADRAS_INTEGRATOR_ID'),
    targetUrl: 'https://midominio.com/webhooks/ciencuadras',
    knownUrl: $urlGuardadaEnTuBaseDeDatos,
);

if ($result->subscribed) {
    guardarUrl($result->url);
}
```

Detalles en [`webhooks.md`](webhooks.md).

## Paso 8 · Mantener sincronizado el inventario

Un flujo de sincronización sano se ve así:

```
Cambio en tu sistema
        │
        ├─ ¿tengo listing_id guardado?
        │       sí → listings()->update([...])
        │       no → listings()->findByExternalCode(...)
        │                  encontrado → update
        │                  null       → listings()->create([...])
        │
        ├─ guardar task_id
        │
        └─ webhook LISTING_CORE → guardar listing_id + fr_property_id, o registrar el error
```

Para retirar un inmueble:

```php
$sdk->listings()->updateStatus([
    'listing_id' => $listingId,
    'client_id'  => $clientId,
    'status'     => 'DELETED',
]);
```

## Checklist antes de pasar a producción

- [ ] El API key vive en variables de entorno, no en el código ni en el repositorio.
- [ ] Los inmuebles se envían **por lotes**, no de uno en uno.
- [ ] Guardas `listing_id` y `fr_property_id` de cada inmueble publicado.
- [ ] Manejas `ApiException` **y** `TransportException` con reintentos y backoff.
- [ ] Registras `trackingId()` en los logs de todos los errores.
- [ ] Usas webhooks en vez de polling agresivo (`429` es throttling real).
- [ ] Revisas `task.messages.images` — una URL de foto rota no falla el request pero sí la publicación.
- [ ] El endpoint del webhook valida `HUB.ID` y `VERIFY-TOKEN` y responde `200` rápido.
- [ ] Probaste primero contra `Config::BASE_URL_QA` o `Config::BASE_URL_MOCK`.

## Siguientes lecturas

| Documento | Contenido |
| --- | --- |
| [`api-reference.md`](api-reference.md) | Todos los endpoints y su método equivalente en el SDK |
| [`listing-parameters.md`](listing-parameters.md) | Todos los campos del inmueble con sus enums |
| [`tasks.md`](tasks.md) | Tareas asincrónicas y estrategias de polling |
| [`webhooks.md`](webhooks.md) | Suscripción, validación y procesamiento de callbacks |
| [`errors.md`](errors.md) | Catálogo de errores y solución de problemas |
