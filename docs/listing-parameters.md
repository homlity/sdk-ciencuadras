<p align="center">
  <a href="https://homlity.com/"><img src="https://homlity.com/wp-content/uploads/2026/08/Diseno-sin-titulo-1-e1787507729419-1024x338.webp" alt="Homlity" width="240"></a>
</p>

# Parámetros de inmuebles

Referencia completa de los campos que acepta un inmueble, con sus tipos y valores permitidos.

Basado en `components.schemas.ListingPOST`, `ListingPATCH`, `ListingStatus` y `ValidateListig`
del OpenAPI oficial (`resources/openapi/ciencuadras-integradores-1.0.0.json`).

Portal de desarrolladores: <https://homlity.com/desarrolladores/> · Página principal: <https://homlity.com/>

---

## Crear inmueble — `POST /listing`

Body: arreglo de objetos (`ListingPOST[]`). Método SDK: `listings()->create()`.

### Campos requeridos

`description` · `external_code` · `client_id` · `offer` · `property_type` · `price` ·
`address` · `locations` · `area` · `listing_contact`

### Campos de primer nivel

| Campo | Tipo | Req. | Notas |
| --- | --- | :---: | --- |
| `external_code` | `string` | ✅ | Código interno del integrador con el que relacionas la publicación |
| `client_id` | `string(uuid)` | ✅ | Cliente (inmobiliaria) al que se asocia el inmueble |
| `client_agent` | `integer` | — | Código de agente/sucursal. Se solicita a Ciencuadras en la verificación |
| `client` | `object` | — | Datos de contacto del cliente (ver abajo) |
| `offer` | `string` | ✅ | `sell`, `rent`, `lease` |
| `property_type` | `string` | ✅ | Ver tabla de tipos |
| `description` | `string` | ✅ | Descripción del inmueble |
| `price` | `number` | ✅ | Valor del inmueble. Debe ser positivo |
| `administration` | `object` | — | Relevante en arriendo (ver abajo) |
| `negotiable` | `boolean` | — | Indica si el precio es negociable |
| `condition` | `integer` | — | Estado del inmueble (enum) |
| `stratum` | `integer` | — | Estrato (enum) |
| `area` | `number` | ✅ | Área construida en m². Debe ser positiva |
| `living_area` | `number` | — | Área privada en m² (casa lote o lote) |
| `age` | `integer` | — | Antigüedad (enum) |
| `address` | `object` | ✅ | Ver estructura |
| `locations` | `object` | ✅ | Ver estructura |
| `postal_code` | `string` | — | Código postal |
| `categories` | `integer[]` | — | Ids de características. Consúltalos con `GET /category` |
| `capacity` | `integer` | — | Capacidad de personas para alquiler vacacional (`0`–`20`) |
| `rooms` | `integer` | — | Habitaciones (`0`–`20`; `0` = sin especificar) |
| `baths` | `integer` | — | Baños (`0`–`10`; `0` = sin especificar) |
| `floor` | `integer` | — | Piso (`0`–`16`, `18`; `0` = sin especificar) |
| `garages` | `integer` | — | Garajes (`0`–`11`; `0` = sin especificar) |
| `parking_size` | `integer` | — | Tamaño de parqueadero (enum) |
| `total_environment` | `integer` | — | Ambientes (enum) |
| `interior_floors` | `integer` | — | Pisos interiores (`0`–`16`, `18`) |
| `parking_price_type` | `integer` | — | Forma de cobro del parqueadero (enum) |
| `parking_availability` | `array` | — | Disponibilidad del parqueadero (ver estructura) |
| `listing_contact` | `object` | ✅ | Ver estructura |
| `video` | `string(url)` | — | URL de YouTube. **No disponible para inmobiliarias** |
| `photos` | `array` | — | Máximo 30 imágenes (ver estructura) |

---

## Enums

### `offer` — Tipo de oferta

| Valor | Español |
| --- | --- |
| `sell` | Venta |
| `rent` | Arriendo |
| `lease` | Alquiler vacacional |

### `property_type` — Tipo de propiedad

| Valor | Español | Valor | Español |
| --- | --- | --- | --- |
| `lot` | Lote | `building` | Edificio |
| `commercial` | Local | `cabin` | Cabaña |
| `office` | Oficina | `country-house` | Casa campestre |
| `warehouse` | Bodega | `studio` | Apartaestudio |
| `farm` | Finca | `house-lot` | Casa lote |
| `apartment` | Apartamento | `parking` | Parqueadero |
| `house` | Casa | `room` | Habitación |
| `consulting-room` | Consultorio | | |

### `condition` — Condición del inmueble

| Valor | Significado | Valor | Significado |
| --- | --- | --- | --- |
| `0` | Sin especificar | `5` | Deuda sin recurso |
| `1` | Nueva marca | `6` | Desarrollo |
| `2` | Excelente | `7` | En plano |
| `3` | Bueno | `8` | Entrega inmediata |
| `4` | Remodelado | | |

### `stratum` — Estrato

| Valor | Significado |
| --- | --- |
| `0`–`6` | Estrato 0 a 6 |
| `100` | Campestre |
| `110` | Sin especificar |

### `age` — Antigüedad

| Valor | Significado |
| --- | --- |
| `0` | Indefinido |
| `1` | Menor a un año |
| `2` | De 1 a 8 años |
| `3` | De 9 a 15 años |
| `4` | De 16 a 30 años |
| `5` | Más de 30 años |

### `total_environment` — Ambientes

| Valor | Significado |
| --- | --- |
| `0` | No especificado |
| `1`–`4` | 1 a 4 ambientes |
| `5` | Más de 4 ambientes |

### `parking_size` — Tamaño de parqueadero

| Valor | Significado |
| --- | --- |
| `0` | No especificado |
| `278` | Pequeño |
| `279` | Mediano |
| `280` | Grande |

### `parking_price_type` — Forma de cobro del parqueadero

| Valor | Significado |
| --- | --- |
| `0` | Sin especificar |
| `1` | Mensualmente |
| `2` | Diariamente |

### `view_map` — Visualización de la ubicación

| Valor | Significado |
| --- | --- |
| `0` | Punto geográfico exacto |
| `1` | Oculto |
| `2` | Mostrar solo zona |

### `rooms`, `baths`, `garages`, `floor`, `capacity`, `interior_floors`

Enteros donde `0` siempre significa «sin especificar» y el resto es el número literal.

| Campo | Rango permitido |
| --- | --- |
| `rooms` | `0`–`20` |
| `capacity` | `0`–`20` |
| `baths` | `0`–`10` |
| `garages` | `0`–`11` |
| `floor` | `0`–`16`, `18` |
| `interior_floors` | `0`–`16`, `18` |

---

## Estructuras anidadas

### `address` (`ListingAddress`)

| Campo | Tipo | Req. | Descripción |
| --- | --- | :---: | --- |
| `address` | `string` | ✅ | Dirección del inmueble |

```php
'address' => ['address' => 'Calle 12 # 34-56'],
```

### `locations` (`ListingLocations`)

| Campo | Tipo | Req. | Descripción |
| --- | --- | :---: | --- |
| `location_point` | `object` | ✅ | Coordenadas |
| `location_point.latitude` | `number` | ✅ | Latitud |
| `location_point.longitude` | `number` | ✅ | Longitud |
| `view_map` | `integer` | — | `0`, `1` o `2` |
| `location_main_id` | `string` | — | Id del barrio (de `GET /location/{name}`) |

```php
'locations' => [
    'location_point'   => ['latitude' => 4.729795079, 'longitude' => -74.044724493],
    'location_main_id' => '1895e0a3-60b8-4a9d-858d-f2c7297b48b2',
    'view_map'         => 2,
],
```

### `listing_contact` (`ListingContact`)

| Campo | Tipo | Req. |
| --- | --- | :---: |
| `emails` | `array` | ✅ |
| `phones` | `array` | ✅ |

**`emails[]`** (`ListingContactEmails`)

| Campo | Tipo | Req. | Descripción |
| --- | --- | :---: | --- |
| `email` | `string` | ✅ | Dirección electrónica del contacto |
| `is_main` | `boolean` | ✅ | Email principal para redirigir el click-to-call |
| `sort_order` | `integer` | ✅ | Orden de visualización. Si `is_main` es `true`, usa `0` |

**`phones[]`** (`ListingContactPhones`)

| Campo | Tipo | Req. | Descripción |
| --- | --- | :---: | --- |
| `phone` | `string` | ✅ | Número telefónico |
| `sort_order` | `integer` | ✅ | Orden de visualización |
| `is_whatsapp_number` | `boolean` | — | Habilita el botón de WhatsApp. **Solo un número puede tenerlo** |
| `is_click_to_call` | `boolean` | — | Habilita el botón de llamada con un clic |

```php
'listing_contact' => [
    'emails' => [
        ['email' => 'ventas@midominio.com', 'is_main' => true, 'sort_order' => 0],
    ],
    'phones' => [
        ['phone' => '+573001112233', 'is_whatsapp_number' => true, 'is_click_to_call' => true, 'sort_order' => 0],
    ],
],
```

### `photos` (`Multimedia`)

Máximo 30 elementos.

| Campo | Tipo | Descripción |
| --- | --- | --- |
| `image` | `string(url)` | URL pública de la imagen |
| `is_main` | `boolean` | Imagen principal. La principal queda en posición 1 |
| `sort_order` | `integer` | Posición. Valor positivo; ten en cuenta que la principal ocupa la 1 |
| `id` | `string` | Id de la imagen en Ciencuadras. Envíalo para **actualizar**; omítelo para crear una nueva |
| `status` | `integer` | Solo `3` = eliminar la imagen |

```php
'photos' => [
    ['sort_order' => 1, 'is_main' => true,  'image' => 'https://cdn.midominio.com/1.jpg'],
    ['sort_order' => 2, 'is_main' => false, 'image' => 'https://cdn.midominio.com/2.jpg'],
    ['id' => 'bd08d894-a117-46f2-a7a5-c6c02e5764b3', 'status' => 3],  // eliminar
],
```

En las respuestas (`MultimediaResponse`) el `status` es más amplio: `0` pendiente, `1` en proceso,
`2` generado, `3` eliminado, `4` error.

> Ciencuadras **descarga** cada imagen desde tu URL. Deben ser públicas, sin autenticación y
> accesibles desde internet. Los rechazos aparecen en `task.messages.images`.

### `administration` (`ListingAdministration`)

| Campo | Tipo | Req. | Descripción |
| --- | --- | :---: | --- |
| `is_included` | `boolean` | ✅ | Indica si la administración está incluida |
| `price` | `number` | — | Valor de la administración |

### `client` (`ListingClient`)

Datos de contacto del cliente que sobreescriben los del inmueble.

| Campo | Tipo | Descripción |
| --- | --- | --- |
| `phone` | `string` | Número telefónico del cliente |
| `is_whatsapp_number` | `boolean` | Habilita WhatsApp (solo un número) |
| `is_click_to_call` | `boolean` | Habilita llamada con un clic |
| `sort_order` | `integer` | Orden |

### `parking_availability[]` (`ListingParkingAvailability`)

| Campo | Tipo | Req. | Descripción |
| --- | --- | :---: | --- |
| `slug` | `string` | ✅ | `Monday`, `Tuesday`, `Wednesday`, `Thursday`, `Friday`, `Saturday`, `Sunday` |
| `schedule` | `object` | ✅ | Ver `ListingSchedule` |

**`schedule`** (`ListingSchedule`)

| Campo | Tipo | Req. | Descripción |
| --- | --- | :---: | --- |
| `all_day` | `boolean` | ✅ | El horario cubre todo el día |
| `start_hour` | `string` | — | Hora de inicio |
| `end_hour` | `string` | — | Hora de finalización |
| `sort_order` | `integer` | — | Orden del horario |

```php
'parking_availability' => [
    ['slug' => 'Monday', 'schedule' => ['all_day' => false, 'start_hour' => '08:00', 'end_hour' => '18:00', 'sort_order' => 0]],
    ['slug' => 'Sunday', 'schedule' => ['all_day' => true]],
],
```

---

## Actualizar inmueble — `PATCH /listing`

Body: arreglo de objetos (`ListingPATCH[]`). Método SDK: `listings()->update()`.

Campos requeridos por item: los mismos de la creación **más `listing_id`**.

`listing_id` · `description` · `external_code` · `client_id` · `offer` · `property_type` ·
`price` · `address` · `locations` · `area` · `listing_contact`

---

## Cambiar estado — `PATCH /listing/status`

Body: arreglo de objetos (`ListingStatus[]`). Método SDK: `listings()->updateStatus()`.

| Campo | Tipo | Req. | Descripción |
| --- | --- | :---: | --- |
| `listing_id` | `string(uuid)` | ✅ | Identificador único del inmueble |
| `client_id` | `string(uuid)` | ✅ | Identificador único del cliente |
| `status` | `string` | ✅ | `ACTIVE` (activo) o `DELETED` (eliminado) |

### Estados de lectura (enum `ListingStatus` del SDK)

Las **respuestas de consulta** devuelven un `status` entero distinto del string que recibe este
endpoint:

| Código | Caso | Significado |
| --- | --- | --- |
| `0` | `INCOMPLETE` | Estado inicial al publicar, aún incompleto |
| `1` | `DISABLED` | Desactivado desde la OV |
| `2` | `NO_QUOTA` | Sin cupo disponible |
| `4` | `ACTIVE` | Publicado y visible |
| `5` | `EXPIRED` | Producto de cuota expirado |
| `7` | `DELETED` | Eliminado |
| `9` | `SYSTEM_ERROR` | Error interno en el proceso de publicación |
| `10` | `PUBLISHING` | En proceso de publicación |
| `11` | `REJECTED` | Moderado y rechazado por contenido fraudulento |

---

## Validar inmuebles — `POST /validate-listing`

Método SDK: `listings()->validate()`. Schema `ValidateListig`.

| Campo | Tipo | Req. | Descripción |
| --- | --- | :---: | --- |
| `client_id` | `string(uuid)` | ✅ | Identificador único del cliente |
| `fr_property_id` | `array` | — | Identificadores enteros de inmuebles |
| `listing_id` | `array` | — | Identificadores uuid de inmuebles |
| `integrator_code` | `array` | — | Códigos de integrador |

---

## Consultar inmuebles

### Listado — `GET /listing`

| Tipo | Parámetro | Req. |
| --- | --- | :---: |
| Header | `apikey` | ✅ |
| Header | `Cookie` | ✅ |
| Query | `search` | — |
| Query | `ordering` | — |
| Query | `page` | — |
| Query | `page_size` | — |

Valores de `ordering`: `property_detail__fr_property_id`, `-property_detail__fr_property_id`,
`integrator_code`, `-integrator_code`, `created`, `-created`, `updated`, `-updated`,
`status`, `-status`.

### Detalle — `GET /listing/{listing_id}`

| Tipo | Parámetro | Req. |
| --- | --- | :---: |
| Header | `apikey` | ✅ |
| Path | `listing_id` (`uuid`) | ✅ |

---

## Validación local con el SDK

```php
use Ciencuadras\Sdk\Schema\SchemaCatalog;
use Ciencuadras\Sdk\Schema\ListingPayloadValidator;

$catalog   = new SchemaCatalog();
$validator = new ListingPayloadValidator($catalog);

// Solo requeridos de primer nivel (lo que hace el SDK por defecto)
$validator->validateCreatePayload([$listing]);

// Estricto: además tipos, enums y requeridos anidados
$validator->validateCreatePayload([$listing], strict: true);

// Consultar los requeridos vigentes según el OpenAPI
$catalog->listingCreateRequiredFields();
$catalog->listingCreateItemSchema()['properties']['property_type']['enum'];
```
