# API AJAX — Referencia

## Endpoint

```
POST /front/ajax/api.php
Content-Type: multipart/form-data (FormData)
```

### Campos base

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `module` | string | Módulo destino (lo añade `api.js`) |
| `action` | string | Acción dentro del módulo |

### Respuesta estándar

```json
{
  "success": true,
  "message": "Texto opcional",
  "data": {}
}
```

En error: `"success": false`, HTTP 4xx/5xx según caso. Con `DEBUG_MODE=true` los 500 pueden incluir el mensaje de excepción.

### Cliente JavaScript

```javascript
const res = await API.post('rifas', {
  action: 'obtener_por_id',
  id_raffle: 1
});
```

---

## Módulo: `rifas`

| Acción | Auth | Descripción |
|--------|------|-------------|
| `obtener_activas` | Pública | Rifas con `status_raffle = 1` |
| `obtener_por_id` | Pública | Detalle de una rifa (landing) |
| `obtener_rifas` | Pública | Lista id + título |
| `obtener` | Privada | Listado con filtros búsqueda/estado |
| `crear` | Admin | Crea rifa + tickets (`10^digits`) |
| `actualizar` | Admin | Actualiza campos incl. `is_free_raffle` |
| `eliminar` | Admin | Elimina rifa |
| `reutilizar` | Admin | Resetea números, cancela reservas, borra ventas |

**Crear rifa — campos:**

| Campo | Notas |
|-------|-------|
| `title_raffle` | Obligatorio |
| `description_raffle` | Lotería / premio mayor |
| `promotions_raffle` | Premios separados por coma |
| `price_raffle` | Ignorado si `is_free_raffle=1` |
| `is_free_raffle` | `1` = rifa gratis |
| `digits_raffle` | 1–5 (100 a 100.000 números) |
| `date_raffle` | Fecha sorteo |
| `status_raffle` | `1` activa, `0` inactiva |

---

## Módulo: `numeros`

| Acción | Auth | Descripción |
|--------|------|-------------|
| `obtener_inventario` | Pública | Tickets de una rifa + `name_customer` |
| `obtener_info_ticket` | Pública | Detalle vendido/reservado (modal) |
| `cambiar_estado` | Admin | Cambia estado 0 ↔ 2 (no vendidos) |

**`obtener_inventario` — parámetros:**

| Campo | Descripción |
|-------|-------------|
| `id_raffle` | ID de la rifa |
| `status` | Opcional: filtrar por estado |
| `search` | Opcional: buscar número |

**Respuesta ticket:**

```json
{
  "id_ticket": 1,
  "number_ticket": "00",
  "status_ticket": 0,
  "id_raffle_ticket": 1,
  "name_customer": null
}
```

---

## Módulo: `participacion`

| Acción | Auth | Descripción |
|--------|------|-------------|
| `registrar_gratis` | Pública | Solo rifas con `is_free_raffle=1` |

**Parámetros:**

| Campo | Descripción |
|-------|-------------|
| `id_raffle` | ID rifa gratis |
| `name_customer` | Nombre completo |
| `phone_customer` | Celular 10 dígitos |
| `tickets` | JSON array con **un** `id_ticket` |

**Respuesta exitosa:**

```json
{
  "success": true,
  "message": "¡Tu número quedó registrado!",
  "code_sale": "FREE-A1B2C3D4E5F6",
  "numbers": ["42"],
  "id_sale": 15
}
```

**Ya participó:**

```json
{
  "success": false,
  "existing": true,
  "message": "Ya participaste en esta rifa con el número 42"
}
```

---

## Módulo: `reservations`

| Acción | Auth | Descripción |
|--------|------|-------------|
| `crear_reserva` | Pública | Solo rifas **de pago** |
| `detalle` | Pública | Por `token` |
| `obtener` | Privada | Listado paginado |
| `cancelar` | Privada | Libera tickets |
| `aceptar_venta` | Privada | Convierte reserva en venta |
| `liberar_reservas_masivo` | Admin | Libera todas (opcional por rifa) |

**`crear_reserva` — parámetros:**

| Campo | Descripción |
|-------|-------------|
| `id_raffle` | ID rifa |
| `name_customer`, `phone_customer` | Cliente |
| `tickets` | JSON array de `id_ticket` (máx. 50) |

**Respuesta:** `token` tipo `RES-XXXXXXXX`.

---

## Módulo: `ventas`

| Acción | Auth | Descripción |
|--------|------|-------------|
| `obtener` | Privada | Listado paginado |
| `crear_venta` | Privada | Venta manual (vender.php) |
| `detalle_venta` | Privada | HTML recibo |
| `gestion_venta` | Privada | Datos para gestionar venta |
| `numeros_vendidos` | Privada | Reporte ticket a ticket |
| `listar_vendedores` | Privada | Admins activos |
| `obtener_por_codigo` | Privada | Buscar por `code_sale` |
| `cambiar_cliente` | Admin | Reasignar cliente |
| `liberar_numeros` | Admin | Liberar números parciales |
| `cancelar_venta` | Admin | Anular venta completa |

---

## Módulo: `clientes`

| Acción | Auth | Descripción |
|--------|------|-------------|
| `buscar_por_celular` | Pública | Autocompletar en landing |
| `obtener` | Privada | Listado |
| `crear` | Privada | Alta |
| `actualizar` | Privada | Edición |
| `eliminar` | Privada | Baja lógica |

---

## Módulo: `settings`

| Acción | Auth | Descripción |
|--------|------|-------------|
| `obtener` | Pública* | *Sin login: solo claves públicas |
| `actualizar` | Admin | Texto, toggle o imagen |

**Claves públicas:** `site_name`, `site_logo`, `site_favicon`, `whatsapp_line_main`, `whatsapp_line_support`, `facebook_url`, `instagram_url`, `site_active`.

---

## Módulo: `dashboard`

| Acción | Auth | Descripción |
|--------|------|-------------|
| `obtener_dashboard` | Privada | KPIs y gráficas |

---

## Módulo: `usuarios`

| Acción | Auth | Descripción |
|--------|------|-------------|
| `cambiar_mi_password` | Privada | Cualquier usuario logueado |
| `obtener` | Admin | Listado admins |
| `crear` | Admin | Nuevo usuario |
| `actualizar` | Admin | Editar |
| `cambiar_password` | Admin | Reset contraseña |
| `desactivar` | Admin | Desactivar cuenta |

---

## Códigos HTTP

| Código | Situación |
|--------|-----------|
| 200 | OK (revisar `success` en JSON) |
| 400 | Módulo no especificado |
| 401 | No autenticado (admin) |
| 403 | Sin permiso (acción admin) |
| 404 | Módulo no válido |
| 500 | Error interno |

El cliente redirige a `dash.php` si recibe 401 con mensaje `No autenticado`.

---

## Ejemplos cURL

**Inventario público:**

```bash
curl -X POST "https://tudominio.com/front/ajax/api.php" \
  -F "module=numeros" \
  -F "action=obtener_inventario" \
  -F "id_raffle=1"
```

**Participación gratis:**

```bash
curl -X POST "https://tudominio.com/front/ajax/api.php" \
  -F "module=participacion" \
  -F "action=registrar_gratis" \
  -F "id_raffle=1" \
  -F "name_customer=María López" \
  -F "phone_customer=3001234567" \
  -F "tickets=[5]"
```

**Crear reserva (rifa de pago):**

```bash
curl -X POST "https://tudominio.com/front/ajax/api.php" \
  -F "module=reservations" \
  -F "action=crear_reserva" \
  -F "id_raffle=2" \
  -F "name_customer=Juan Pérez" \
  -F "phone_customer=3009876543" \
  -F "tickets=[10,11,12]"
```
