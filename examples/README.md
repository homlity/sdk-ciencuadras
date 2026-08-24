<p align="center">
  <a href="https://homlity.com/"><img src="https://homlity.com/wp-content/uploads/2026/08/Diseno-sin-titulo-1-e1787507729419-1024x338.webp" alt="Homlity" width="240"></a>
</p>

# Ejemplos ejecutables

Scripts listos para copiar y adaptar. Todos usan variables de entorno para las credenciales.

Portal de desarrolladores: <https://homlity.com/desarrolladores/> · Página principal: <https://homlity.com/>

---

## Preparación

```bash
composer install

export CIENCUADRAS_API_KEY=xxxxxxxxxxxx
export CIENCUADRAS_CLIENT_ID=df03d199-be5c-4c5c-98f6-849361cb7fae
export CIENCUADRAS_CLIENT_COOKIE=78bea79c-1f6b-4e6d-a800-43fb327ed7c5
export CIENCUADRAS_WEBHOOK_ID=696d939e-4cc3-43ac-a312-6bf2e7f15868
export CIENCUADRAS_WEBHOOK_TARGET=https://midominio.com/webhooks/ciencuadras
export CIENCUADRAS_WEBHOOK_HUB_ID=...
export CIENCUADRAS_WEBHOOK_VERIFY_TOKEN=...
```

## Índice

| Script | Qué hace | Requiere credenciales |
| --- | --- | :---: |
| [`explore-catalogs.php`](explore-catalogs.php) | Verifica el API key y resuelve clientes, agentes, ubicaciones y categorías. También inspecciona el OpenAPI embebido | Parcial |
| [`homlity-env.php`](homlity-env.php) | Muestra cómo `Config::fromHomlityEnv()` resuelve base URL y rutas por ambiente | No |
| [`publish-listing.php`](publish-listing.php) | Publica un inmueble (`POST /listing`) | Sí |
| [`update-listing.php`](update-listing.php) | Actualiza un inmueble existente, incluyendo fotos | Sí |
| [`update-status.php`](update-status.php) | Activa o elimina un inmueble y consulta su estado real | Sí |
| [`list-listings.php`](list-listings.php) | Lista inmuebles con paginación y ordenamiento | Sí |
| [`track-task.php`](track-task.php) | Sigue una tarea hasta su estado terminal y muestra los errores por inmueble e imagen | Sí |
| [`sync-inventory.php`](sync-inventory.php) | Sincronización idempotente: crea lo que falta, actualiza lo que existe, con reintentos y backoff | Sí |
| [`subscribe-webhook.php`](subscribe-webhook.php) | Registra la URL del integrador | Sí |
| [`receive-webhook.php`](receive-webhook.php) | Endpoint que valida `HUB.ID` / `VERIFY-TOKEN` y procesa el callback | Sí |

## Uso

```bash
# Sin credenciales: solo lee el OpenAPI embebido
php examples/homlity-env.php

# Explorar catálogos
php examples/explore-catalogs.php "Chapinero"

# Publicar y seguir el resultado
php examples/publish-listing.php
php examples/track-task.php <task_id>

# Actualizar y cambiar estado
php examples/update-listing.php <listing_id>
php examples/update-status.php <listing_id> DELETED

# Sincronizar el inventario completo
php examples/sync-inventory.php

# Webhooks
php examples/subscribe-webhook.php
php -S 0.0.0.0:8080 examples/receive-webhook.php   # servidor local de pruebas
```

## Probar sin credenciales

El OpenAPI expone un mock en SwaggerHub que responde con los ejemplos del contrato:

```php
$sdk = new CiencuadrasClient(new Config(
    apiKey: 'cualquier-valor',
    baseUrl: Config::BASE_URL_MOCK,
));
```

También puedes inyectar un `HttpClientInterface` falso para trabajar totalmente offline; ver la
sección *Testing* del [README](../README.md) y la carpeta [`tests/`](../tests/).

## Advertencias

- Estos scripts **escriben en Ciencuadras**. Prueba primero contra `Config::BASE_URL_QA` o el mock.
- Reemplaza los uuids de ejemplo por los tuyos: no corresponden a datos reales.
- Las URLs de fotos deben ser públicas y descargables desde internet.
- Nunca subas tus credenciales al repositorio.
