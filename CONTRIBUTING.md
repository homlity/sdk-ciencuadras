<p align="center">
  <a href="https://homlity.com/"><img src="https://homlity.com/wp-content/uploads/2026/08/Diseno-sin-titulo-1-e1787507729419-1024x338.webp" alt="Homlity" width="240"></a>
</p>

# Contribuir al Homlity SDK · Ciencuadras

Gracias por querer mejorar el ecosistema abierto de [Homlity](https://homlity.com/).
El desarrollo se centraliza en <https://github.com/homlity>.

---

## Antes de escribir código

1. Revisa los [issues abiertos](https://github.com/homlity/sdk-ciencuadras/issues) por si ya existe.
2. Abre un issue describiendo el bug (con pasos para reproducirlo) o la propuesta.
3. Para cambios grandes, comenta el enfoque antes de implementarlo.

## Entorno de desarrollo

```bash
git clone https://github.com/homlity/sdk-ciencuadras.git
cd sdk-ciencuadras
composer install
composer test
```

Requisitos: PHP `^8.1` con las extensiones `curl` y `json`.

## Convenciones del código

- `declare(strict_types=1);` en todos los archivos.
- PSR-4 bajo el namespace `Ciencuadras\Sdk\` y PSR-12 de estilo.
- Clases `final` y propiedades `readonly` salvo que exista una razón para extender.
- Tipos explícitos en firmas y anotaciones `@param` / `@return` para arrays.
- Comentarios y mensajes de excepción en español; nombres de símbolos en inglés, como el resto
  del código.

## Estructura del proyecto

```
src/
├── Config.php                  Configuración: API key, base URL, timeout, endpoints por ambiente
├── CiencuadrasClient.php       Punto de entrada; instancia perezosa de cada módulo
├── Api/                        Un módulo por recurso (listings, clients, tasks, webhooks…)
├── Http/                       HttpClientInterface, CurlHttpClient, ApiResponse
├── Schema/                     SchemaCatalog (OpenAPI) y ListingPayloadValidator
├── Data/                       Enums y snapshots tipados
├── Webhook/                    Suscripción y procesamiento de callbacks
└── Exception/                  ApiException, TransportException, WebhookException

resources/openapi/              Snapshot del contrato oficial usado por el SDK
docs/                           Documentación para desarrolladores
examples/                       Scripts ejecutables
tests/                          Suite de PHPUnit
```

### Agregar un endpoint nuevo

1. Añade el método en la clase `Api/*Api.php` correspondiente, con un docblock que indique el
   verbo y la ruta (`GET /listing/{listing_id}`).
2. Usa `send()` de `BaseApi`: centraliza el manejo de errores y la decodificación JSON.
3. Si el endpoint recibe una lista, usa `normalizeBatchPayload()` para aceptar objeto o lista.
4. Si tiene campos requeridos, valídalos contra `SchemaCatalog` antes de enviar.
5. Agrega pruebas con un `HttpClientInterface` falso.
6. Documenta el método en [`docs/api-reference.md`](docs/api-reference.md).

### Actualizar el snapshot OpenAPI

`resources/openapi/ciencuadras-integradores-1.0.0.json` es la fuente de la validación y de
`SchemaCatalog`. Si Ciencuadras publica una versión nueva:

1. Reemplaza el archivo.
2. Ejecuta `composer test`: `SchemaCatalogTest` y `ListingPayloadValidatorTest` detectan cambios
   en los campos requeridos.
3. Actualiza [`docs/listing-parameters.md`](docs/listing-parameters.md) con los enums nuevos.

## Pruebas

```bash
composer test
./vendor/bin/phpunit --filter WebhookNotificationTest
```

- No hagas llamadas de red en los tests: inyecta un `HttpClientInterface` falso.
- Para `waitUntilSettled()` pasa `sleepFn` para evitar esperas reales.
- Toda corrección de bug debe venir con una prueba que falle sin el arreglo.

## Pull requests

1. Rama descriptiva: `fix/listing-status-enum`, `feat/validate-listing-strict`.
2. Commits claros y en imperativo.
3. `composer test` en verde.
4. Describe en el PR qué cambia, por qué y si rompe compatibilidad.
5. Actualiza la documentación afectada en el mismo PR.

## Versionado

El paquete sigue [SemVer](https://semver.org/lang/es/). Marca explícitamente en el PR cualquier
cambio que rompa compatibilidad.

## Seguridad

**No abras un issue público** para vulnerabilidades. Repórtalas de forma privada a través de
<https://homlity.com/>.

Nunca incluyas API keys, cookies de cliente ni tokens de webhook en issues, PRs, logs o pruebas.

## Licencia

Al contribuir aceptas que tu aporte se distribuya bajo la licencia [MIT](LICENSE) del proyecto.
