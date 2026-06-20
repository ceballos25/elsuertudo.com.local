# Funcionalidades y reglas de negocio

## Modos de rifa

El sistema distingue dos modos mediante `is_free_raffle` en la tabla `raffles`. La lógica centralizada está en `App\Core\RaffleMode`.

| Regla | Rifa paga | Rifa gratis |
|-------|-----------|-------------|
| `is_free_raffle` | `0` | `1` |
| Precio | `price_raffle` > 0 | Forzado a `0` |
| Participación web | Reserva + WhatsApp | Venta inmediata |
| Máx. números por pedido (web) | 50 | 1 |
| 1 participación por teléfono | No | Sí |
| Método de pago en venta | `Página Web` / `Manual` | `Gratis` |
| Código de venta | `SALE-…` / token reserva | `FREE-…` |

### Activar rifa gratis (admin)

1. Rifas → Crear o editar
2. Activar switch **“Rifa gratis”**
3. El precio se bloquea en $0
4. Compartir enlace `/?env=ID`

Las rifas existentes permanecen en modo pago hasta que se edite explícitamente.

---

## Arquitectura modular de ventas

### `SaleFulfillmentService`

Servicio reutilizable que ejecuta en **una transacción**:

1. Insertar registro en `sales`
2. Por cada ticket: `claimForSale()` (libre → vendido)

**Consumidores actuales:**

| Servicio | Uso |
|----------|-----|
| `SaleService::create` | Venta manual admin |
| `FreeParticipationService::register` | Participación gratis web |
| `ReservationService::acceptSale` | Flujo propio similar al convertir reserva |

### `ParticipationRules`

Validaciones compartidas:

- Parseo de IDs de tickets (JSON o array)
- Pertenencia a la rifa
- Disponibilidad (no ocupados)
- Límite de cantidad por pedido

Usado por ventas admin, participación gratis y extensible a nuevos flujos.

### `FreeParticipationService`

Orquesta el flujo gratis:

1. Valida sitio activo y rifa gratis activa
2. `ParticipationRules::assertSelection` (1 ticket)
3. `CustomerService::findOrCreate`
4. `Ticket::findParticipationByCustomerInRaffle` — anti-duplicado
5. `SaleFulfillmentService::fulfill` con total 0

---

## Nombres en grilla pública

Los endpoints públicos `numeros/obtener_inventario` y `numeros/obtener_info_ticket` incluyen información del cliente en números vendidos o reservados.

| Estado | `name_customer` |
|--------|-----------------|
| Libre | `null` |
| Vendido | Nombre desde venta/cliente |
| Reservado | Nombre desde reserva activa |

La landing (`raffle.js`) muestra el primer nombre truncado bajo el número. Al tocar un vendido/reservado se abre modal con detalle.

> **Privacidad:** los nombres son visibles públicamente en la grilla. Es una decisión de producto para transparencia del sorteo.

---

## Permisos por rol

### Vendedor

- Dashboard, vender, reservas, clientes
- Consultar ventas y números vendidos
- Ver grilla de números
- Ver rifas (sin crear/editar/eliminar)
- Cambiar su propia contraseña

### Administrador

Todo lo anterior, más:

| Módulo | Acciones exclusivas |
|--------|---------------------|
| `rifas` | crear, actualizar, eliminar, reutilizar |
| `ventas` | cancelar_venta, cambiar_cliente, liberar_numeros |
| `numeros` | cambiar_estado |
| `settings` | actualizar |
| `reservations` | liberar_reservas_masivo |
| `usuarios` | CRUD completo |

Implementado en `App\Core\Permissions`.

---

## Generación de números

Al crear una rifa con `digits_raffle = D`:

- Se generan `10^D` tickets
- Numeración: `0000` … con padding de D cifras
- Inserción en lotes de 100 (`Ticket::bulkCreate`)

| Cifras | Cantidad |
|--------|----------|
| 2 | 100 |
| 3 | 1.000 |
| 4 | 10.000 |
| 5 | 100.000 |

---

## Reservas web

- Vigencia: **24 horas** (`expires_at_reservation`)
- Token público: `RES-` + hex
- Bloqueadas en rifas gratis (deben usar `participacion/registrar_gratis`)
- Reserva expirada: admin puede cancelar; tickets vuelven a libre

---

## Sitio inactivo

Setting `site_active = 0`:

- Landing muestra modal de mantenimiento
- API pública de reservas y participación gratis responde error
- Panel admin sigue accesible

---

## Recibos

- Template: `includes/template-ticket.php`
- Colores desde `ThemeConfig` / `theme-base.css`
- Generado por `ReceiptService` tras venta o consulta de detalle

---

## Límites y constantes

| Constante | Valor | Ubicación |
|-----------|-------|-----------|
| Máx. tickets por venta/reserva (paga) | 50 | `SaleService::MAX_TICKETS_PER_ORDER` |
| Máx. tickets participación gratis | 1 | `RaffleMode::maxTicketsPerPublicOrder()` |
| Inventario API (límite SQL) | 10.000 | `Ticket::getByRaffle` |
| Paginación landing | 100/página | `raffle.js` |
| Celular Colombia | 10 dígitos | Validación front + `CustomerService` |

---

## Consideraciones de rendimiento

- **Inventario público:** por cada ticket vendido/reservado se consulta nombre de cliente (N+1). Aceptable en rifas pequeñas (2–3 cifras); en rifas de 4+ cifras con alto % vendido, monitorear tiempos de respuesta.
- **Auto-refresh en paginación:** `cambiarPaginaPublica` recarga inventario completo antes de cambiar página (datos frescos, más carga en rifas grandes).

Mejora futura sugerida: endpoint paginado server-side o carga batch de nombres en una sola query.

---

## Pantallas del panel

| Archivo | Descripción |
|---------|-------------|
| `front/dashboard.php` | KPIs y gráficas |
| `front/vender.php` | POS venta manual |
| `front/reservations.php` | Gestión reservas |
| `front/clientes.php` | CRUD clientes |
| `front/ventas.php` | Listado y gestión ventas |
| `front/numeros-vendidos.php` | Reporte detallado |
| `front/numeros.php` | Grilla admin |
| `front/rifas.php` | CRUD rifas |
| `front/settings.php` | Mi negocio |
| `front/usuarios.php` | Usuarios admin |
