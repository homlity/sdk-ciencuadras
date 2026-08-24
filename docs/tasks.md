<p align="center">
  <a href="https://homlity.com/"><img src="https://homlity.com/wp-content/uploads/2026/08/Diseno-sin-titulo-1-e1787507729419-1024x338.webp" alt="Homlity" width="240"></a>
</p>

# Tareas asincrónicas

Toda escritura en Ciencuadras (`create`, `update`, `updateStatus`) es **asincrónica**: la API
encola el trabajo y responde con un `task_id`. Esta guía explica cómo seguir esa tarea hasta
conocer el resultado real inmueble por inmueble.

Portal de desarrolladores: <https://homlity.com/desarrolladores/> · Página principal: <https://homlity.com/>

---

## Ciclo de vida

```
        ┌────────┐     ┌─────────┐     ┌───────────┐
POST →  │ READY  │ ──▶ │ RUNNING │ ──▶ │ COMPLETED │  éxito
        └────────┘     └─────────┘     ├───────────┤
                                       │ FORWARDED │  duplicado del día (también éxito)
                                       ├───────────┤
                                       │   ERROR   │  falló
                                       └───────────┘
```

| Estado | `TaskStatus` | Significado | ¿Seguir consultando? |
| --- | --- | --- | --- |
| `READY` | `TaskStatus::READY` | Tarea creada, aún no iniciada | Sí |
| `RUNNING` | `TaskStatus::RUNNING` | En ejecución | Sí |
| `COMPLETED` | `TaskStatus::COMPLETED` | Terminó sin errores | No |
| `FORWARDED` | `TaskStatus::FORWARDED` | Se recibió pero no se procesa: ya llegó una petición idéntica el mismo día | No |
| `ERROR` | `TaskStatus::ERROR` | Error al crear o ejecutar la tarea | No |

**`FORWARDED` no es un fallo.** Significa que enviaste la misma información dos veces en el mismo
día y Ciencuadras la descartó por duplicada. Por eso `isSuccessful()` cubre `COMPLETED` **y**
`FORWARDED`.

Campos adicionales del objeto `task`:

| Campo | Valores | Descripción |
| --- | --- | --- |
| `id` | uuid | Identificador de la tarea |
| `status` | ver tabla arriba | Estado |
| `event` | `LISTING_CORE`, `LISTING_STATUS` | Creación/actualización de inmuebles vs. cambio de estado |
| `exec` | `AUTOMATIC`, `MANUAL` | `MANUAL` indica un reintento explícito desde el core de Ciencuadras |
| `created` / `updated` | timestamp | Fechas de la tarea |
| `integrator` | string | Nombre del integrador |
| `content` | array | Resultado por inmueble |
| `messages` | object | Errores detallados (`listings[]`, `images[]`) |

## Obtener el `task_id`

```php
$response = $sdk->listings()->create($listing);
$taskId   = $response['task']['id'];
```

Guárdalo en tu base de datos junto al inmueble: es el hilo que te permite reconstruir qué pasó si
tu proceso se cae.

## Esperar el resultado con `waitUntilSettled()`

```php
$task = $sdk->tasks()->waitUntilSettled($taskId, [
    'maxAttempts'     => 30,    // default 30
    'intervalSeconds' => 3,     // default 3
    'throwOnTimeout'  => true,  // default true
]);
```

| Opción | Tipo | Default | Descripción |
| --- | --- | --- | --- |
| `maxAttempts` | `int` | `30` | Máximo de consultas antes de rendirse. Mínimo efectivo: 1 |
| `intervalSeconds` | `int` | `3` | Segundos de espera entre intentos |
| `throwOnTimeout` | `bool` | `true` | Si es `false` devuelve el último snapshot en vez de lanzar `RuntimeException` |
| `sleepFn` | `callable(int): void` | `sleep()` | Función de espera; sustitúyela en tests |

Comportamiento:

- Devuelve en cuanto la tarea llega a un estado terminal.
- **Tolera errores transitorios**: si una consulta falla (red, `502`, `429`), espera y reintenta
  sin abortar.
- Con la configuración por defecto espera como máximo ~90 segundos.
- Si se agotan los intentos y `throwOnTimeout` es `true`, lanza
  `RuntimeException: Task "…" did not settle after N attempt(s) (last status: …)`.

```php
// Variante no bloqueante para un worker que reencola
$task = $sdk->tasks()->waitUntilSettled($taskId, [
    'maxAttempts'    => 1,
    'throwOnTimeout' => false,
]);

if ($task->isPending()) {
    $this->release(60);   // Laravel: reintenta este job en 60s
    return;
}
```

## Consulta puntual

```php
$task = $sdk->tasks()->getSnapshot($taskId);

$task->id();            // string
$task->status();        // TaskStatus
$task->isPending();     // READY | RUNNING
$task->isSettled();     // COMPLETED | FORWARDED | ERROR
$task->isSuccessful();  // COMPLETED | FORWARDED
$task->isFailed();      // ERROR
$task->contentItems();  // list<array> — resultado por inmueble
$task->raw();           // objeto `task` completo
```

`tasks()->get()` devuelve el array crudo si no necesitas el snapshot. `TaskSnapshot` acepta tanto
la respuesta con sobre (`['task' => [...]]`) como el objeto interno directo.

Si la API devuelve un `status` desconocido, `TaskSnapshot::fromApiResponse()` lanza
`InvalidArgumentException` con la lista de valores esperados — es una señal de que el API cambió.

## Leer el resultado por inmueble

`content[]` trae una entrada por cada inmueble incluido en el lote:

```php
foreach ($task->contentItems() as $item) {
    $item['status'];          // ERROR | COMPLETED | FORWARDED (por inmueble)
    $item['listing_id'];      // uuid interno → guárdalo para actualizar después
    $item['fr_property_id'];  // código numérico visible al usuario en el portal
    $item['external_code'];   // tu código interno
    $item['multimedia'];      // [['id' => ..., 'url' => ..., 'status' => ...], ...]
}
```

> **Importante:** el estado de la tarea puede ser `COMPLETED` y aun así traer inmuebles
> individuales en `ERROR`. Siempre recorre `content[]`; no te quedes solo con `isSuccessful()`.

## Diagnosticar fallos

Los detalles viven en `task.messages`:

```php
$messages = $task->raw()['messages'] ?? [];

// Errores a nivel de inmueble
foreach ($messages['listings'] ?? [] as $problema) {
    $problema['listing_id'];
    $problema['external_code'];
    $problema['error']['message'];                  // "Error Sending Data to Ciencuadras Core"
    $problema['error']['tracking_id'];              // repórtalo a soporte
    $problema['error']['field']['description'];     // qué campo falló
    $problema['error']['field']['status_code'];     // p. ej. 119
    $problema['error']['field']['default_code'];    // p. ej. "invalid_value"
}

// Errores a nivel de imagen
foreach ($messages['images'] ?? [] as $problema) {
    $problema['id'];
    $problema['url'];                               // la URL que no se pudo procesar
    $problema['listing_id'];
    $problema['error']['field']['description'];     // p. ej. "Invalid url"
}
```

Las imágenes son la causa de fallo más frecuente: URLs privadas, con redirecciones, expiradas o
detrás de un CDN que bloquea agentes desconocidos.

## Polling vs. webhooks

| | Polling (`waitUntilSettled`) | Webhooks |
| --- | --- | --- |
| Latencia | Depende del intervalo | Inmediata |
| Consumo de requests | Alto | Mínimo |
| Riesgo de `429` | Alto si abusas | Bajo |
| Requiere endpoint público | No | Sí |
| Ideal para | Scripts, pruebas, lotes pequeños, entornos sin URL pública | Producción |

**Recomendación:** usa webhooks como mecanismo principal y deja el polling como red de seguridad
para tareas de las que no recibiste callback en, digamos, 15 minutos.

## Patrón recomendado en producción

```php
// 1. Publicar y registrar
$response = $sdk->listings()->create($lote);
$taskId   = $response['task']['id'];

Tarea::create([
    'task_id'    => $taskId,
    'estado'     => 'PENDIENTE',
    'enviado_en' => now(),
    'codigos'    => array_column($lote, 'external_code'),
]);

// 2. El webhook resuelve la mayoría de las tareas (ver docs/webhooks.md)

// 3. Barrido de seguridad cada 15 minutos
foreach (Tarea::pendientesDesdeHaceMasDe(15)->get() as $tarea) {
    $snap = $sdk->tasks()->waitUntilSettled($tarea->task_id, [
        'maxAttempts'    => 1,
        'throwOnTimeout' => false,
    ]);

    if ($snap->isSettled()) {
        $tarea->resolverCon($snap);
    }
}
```

## Testing sin esperas reales

```php
$sdk->tasks()->waitUntilSettled('task-1', [
    'intervalSeconds' => 0,
    'sleepFn'         => static fn (int $s) => null,
]);
```

Ver `tests/TasksApiWaitUntilSettledTest.php` para ejemplos completos con un `HttpClientInterface`
falso.

## Endpoints relacionados

| Endpoint | Método SDK |
| --- | --- |
| `GET /task/{task_id}` | `tasks()->get()` · `tasks()->getSnapshot()` · `tasks()->waitUntilSettled()` |

Con `Config::fromHomlityEnv()` la ruta se toma de `CIENCUADRAS_{ENV}_CONSULT_STATUS_ENDPOINT`
(por defecto `/api/consult-status`) y el `task_id` viaja como query param en lugar de en la ruta.
