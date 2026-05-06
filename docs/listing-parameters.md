# Parametros de Inmuebles

Basado en `components.schemas.ListingPOST`, `ListingPATCH`, `ListingStatus`, `ValidateListig` y operaciones `/listing*` del OpenAPI oficial.

## Crear inmueble

Endpoint: `POST /listing`  
Body: arreglo de objetos (`ListingPOST[]`).

### Campos requeridos por inmueble

- `description`
- `external_code`
- `client_id`
- `offer`
- `property_type`
- `price`
- `address`
- `locations`
- `area`
- `listing_contact`

### Campos top-level soportados (`ListingPOST.items.properties`)

| Campo | Tipo | Requerido | Notas |
| --- | --- | --- | --- |
| `external_code` | `string` | Si | Codigo interno del integrador |
| `client_id` | `string(uuid)` | Si | Cliente al que se asocia el inmueble |
| `client_agent` | `integer` | No | Codigo de agente/sucursal |
| `client` | `object` | No | Datos de contacto del cliente |
| `offer` | `string` | Si | `sell`, `rent`, `lease` |
| `property_type` | `string` | Si | `lot`, `commercial`, `office`, `warehouse`, `farm`, `apartment`, `house`, `room`, `consulting-room`, `building`, `cabin`, `country-house`, `studio`, `house-lot`, `parking` |
| `description` | `string` | Si | Descripcion del inmueble |
| `price` | `number` | Si | Valor del inmueble |
| `administration` | `object` | No | Relevante en arriendo |
| `negotiable` | `boolean` | No | Precio negociable |
| `condition` | `integer` | No | Estado del inmueble (enum en OpenAPI) |
| `stratum` | `integer` | No | Estrato (enum en OpenAPI) |
| `area` | `number` | Si | Area construida |
| `living_area` | `number` | No | Area privada |
| `age` | `integer` | No | Antiguedad (enum en OpenAPI) |
| `address` | `object` | Si | Ver estructura abajo |
| `locations` | `object` | Si | Ver estructura abajo |
| `postal_code` | `string` | No | Codigo postal |
| `categories` | `integer[]` | No | IDs de caracteristicas (`GET /category`) |
| `capacity` | `integer` | No | Capacidad para oferta vacacional |
| `rooms` | `integer` | No | Habitaciones |
| `baths` | `integer` | No | Banos |
| `floor` | `integer` | No | Piso |
| `garages` | `integer` | No | Garajes |
| `parking_size` | `integer` | No | Tamano de parqueadero |
| `total_environment` | `integer` | No | Ambientes |
| `interior_floors` | `integer` | No | Pisos interiores |
| `parking_price_type` | `integer` | No | Tipo de cobro parqueadero |
| `parking_availability` | `array` | No | Disponibilidad de parqueadero |
| `listing_contact` | `object` | Si | Ver estructura abajo |
| `video` | `string(url)` | No | URL de video |
| `photos` | `array` | No | Maximo 30 imagenes |

### Estructuras requeridas mas importantes

- `address`
  - Requerido: `address` (`string`)
- `locations`
  - Requerido: `location_point`
  - `location_point` requiere: `latitude` (`number`), `longitude` (`number`)
  - Opcionales: `view_map`, `location_main_id`
- `listing_contact`
  - Requeridos: `emails` (array), `phones` (array)
  - `emails[]` requiere: `email`, `is_main`, `sort_order`
  - `phones[]` requiere: `phone`, `sort_order`

## Actualizar inmueble

Endpoint: `PATCH /listing`  
Body: arreglo de objetos (`ListingPATCH[]`).

Campos requeridos por item:
- `listing_id`
- `description`
- `external_code`
- `client_id`
- `offer`
- `property_type`
- `price`
- `address`
- `locations`
- `area`
- `listing_contact`

## Cambiar estado de inmueble

Endpoint: `PATCH /listing/status`  
Body: arreglo de objetos (`ListingStatus[]`).

Campos requeridos por item:
- `listing_id`
- `client_id`
- `status` (`ACTIVE` o `DELETED`)

## Validar inmuebles

Endpoint: `POST /validate-listing`

Campos (`ValidateListig`):
- Requerido: `client_id`
- Opcionales: `fr_property_id[]`, `listing_id[]`, `integrator_code[]`

## Consultar inmuebles

### Listado

Endpoint: `GET /listing`

Parametros:
- Header requerido: `apikey`
- Header requerido: `Cookie`
- Query opcionales:
  - `search`
  - `ordering`: `property_detail__fr_property_id`, `-property_detail__fr_property_id`, `integrator_code`, `-integrator_code`, `created`, `-created`, `updated`, `-updated`, `status`, `-status`
  - `page`
  - `page_size`

### Detalle

Endpoint: `GET /listing/{listing_id}`

Parametros:
- Header requerido: `apikey`
- Path requerido: `listing_id` (`uuid`)
