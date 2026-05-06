# Webhooks

El flujo real de Ciencuadras para tareas asincrónicas es este:

1. Tu integrador publica o actualiza inmuebles.
2. Ciencuadras procesa la tarea y genera un `task_id`.
3. Ciencuadras envía la notificación al endpoint que tengas suscrito.
4. El integrador valida los headers `HUB.ID` y `VERIFY-TOKEN`.
5. El cuerpo del webhook contiene el detalle de la tarea procesada.

## Suscribir la URL del integrador

```php
<?php

use Ciencuadras\Sdk\Config;
use Ciencuadras\Sdk\CiencuadrasClient;

$sdk = new CiencuadrasClient(new Config('TU_API_KEY'));

$sdk->webhooks()->subscribeTarget(
    integratorId: '696d939e-4cc3-43ac-a312-6bf2e7f15868',
    targetUrl: 'https://midominio.com/webhooks/ciencuadras'
);
```

Si prefieres construir el payload manualmente:

```php
<?php

use Ciencuadras\Sdk\Webhook\WebhookSubscription;

$payload = WebhookSubscription::target('https://midominio.com/webhooks/ciencuadras');
```

## Recibir y validar el callback

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

## Qué devuelve `listingStatusUpdates()`

Cada elemento incluye:

- `listing_id`
- `external_code`
- `fr_property_id`
- `processing_status`
- `error`

`processing_status` es el estado del procesamiento de la tarea (`ERROR`, `COMPLETED`, `FORWARDED`).

Importante: según el OpenAPI local de Ciencuadras, el webhook de tarea `LISTING_STATUS` no expone explícitamente el estado final del inmueble (`ACTIVE` o `DELETED`); expone el resultado del procesamiento de la operación.
