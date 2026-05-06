# API Reference

Fuente: `resources/openapi/ciencuadras-integradores-1.0.0.json` (SwaggerHub Integradores v1.0.0).

## Endpoints cubiertos

| Metodo | Path | OperationId | Metodo SDK |
| --- | --- | --- | --- |
| GET | `/location/{name}` | `getLocation` | `locations()->search(string $name)` |
| GET | `/category` | `getCategory` | `categories()->list(array $query = [])` |
| GET | `/client/{client_id}/agent` | `getAgent` | `clients()->agents(string $clientId)` |
| GET | `/client/` | `getClients` | `clients()->all()` |
| GET | `/client/{client_id}` | `getClientId` | `clients()->get(string $clientId)` |
| GET | `/listing/{listing_id}` | `getListing` | `listings()->get(string $listingId)` |
| GET | `/listing` | `getListingClient` | `listings()->list(string $clientCookie, array $query = [])` |
| POST | `/listing` | `listing_post` | `listings()->create(array $listings)` |
| PATCH | `/listing` | `listing_patch` | `listings()->update(array $listings)` |
| PATCH | `/listing/status` | `listing_status_patch` | `listings()->updateStatus(array $statuses)` |
| POST | `/validate-listing` | `listing_valdiate_post` | `listings()->validate(array $payload)` |
| GET | `/task/{task_id}` | `getTask` | `tasks()->get(string $taskId)` |
| POST | `/api/ciencuadras/` | `postWebhook` | `webhooks()->postEvent(string $hubId, string $verifyToken, array $payload)` |
| POST | `/webhook/{id}/subscribe` | `postWebhookSubscribe` | `webhooks()->subscribe(string $integratorId, array $payload)` |
| POST | `/webhook/{id}/unsubscribe` | `postWebhookUnsubscribe` | `webhooks()->unsubscribe(string $integratorId)` |

## Parametros generales

- Header obligatorio de autenticacion para casi todos los endpoints: `apikey`.
- El SDK envia automaticamente `apikey` y `X-API-KEY` con el token configurado.
- Endpoint `GET /listing` exige adicionalmente header `Cookie`.
- Endpoint `POST /api/ciencuadras/` usa headers `HUB.ID` y `VERIFY-TOKEN`.
