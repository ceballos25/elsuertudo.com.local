# Base de datos

> El esquema completo base (`bd.sql`) no está versionado en el repositorio. Esta documentación se infiere de los **Models** y **Services** del código.

## Diagrama relacional (simplificado)

```
raffles ─────┬──── tickets ─────┬──── sales
             │                  │
             │                  └──── customers
             │
             └──── reservations ─── reservation_tickets ─── tickets

admins ──── sales (id_admin_sale)
settings (configuración global)
```

## Tablas

### `raffles`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_raffle` | INT PK | |
| `title_raffle` | VARCHAR | Nombre de la rifa |
| `description_raffle` | TEXT | Lotería asociada |
| `promotions_raffle` | TEXT | Premios (CSV) |
| `price_raffle` | DECIMAL | Precio unitario; `0` si es gratis |
| `is_free_raffle` | TINYINT(1) | `1` = rifa gratis (**migración 001**) |
| `digits_raffle` | INT | 1–5 → genera `10^digits` tickets |
| `date_raffle` | DATETIME | Fecha del sorteo |
| `status_raffle` | TINYINT | `1` activa, `0` inactiva |
| `date_created_raffle` | DATETIME | |
| `date_updated_raffle` | DATETIME | |

**Migración requerida:**

```sql
-- database/migrations/001_add_is_free_raffle.sql
ALTER TABLE raffles
    ADD COLUMN is_free_raffle TINYINT(1) NOT NULL DEFAULT 0
    AFTER price_raffle;
```

---

### `tickets`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_ticket` | INT PK | |
| `number_ticket` | VARCHAR | Número con padding (ej. `0042`) |
| `id_raffle_ticket` | INT FK | Rifa |
| `status_ticket` | TINYINT | Ver estados abajo |
| `id_customer_ticket` | INT NULL | Cliente asignado |
| `id_sale_ticket` | INT NULL | Venta asociada |

#### Estados `status_ticket`

| Valor | Nombre | Descripción |
|-------|--------|-------------|
| `0` | Libre | Disponible para selección |
| `1` | Vendido | Asignado a venta confirmada |
| `2` | Reservado | Bloqueado por reserva activa |

---

### `customers`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_customer` | INT PK | |
| `name_customer` | VARCHAR | |
| `phone_customer` | VARCHAR | 10 dígitos (Colombia) |
| `status_customer` | TINYINT | `1` activo |

El teléfono se normaliza en `CustomerService` (quita prefijo `57` si aplica).

---

### `sales`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_sale` | INT PK | |
| `code_sale` | VARCHAR | `SALE-…`, `FREE-…`, o token reserva |
| `id_customer_sale` | INT FK | |
| `id_raffle_sale` | INT FK | |
| `id_admin_sale` | INT NULL | Vendedor; NULL en ventas gratis web |
| `quantity_sale` | INT | Cantidad de números |
| `total_sale` | DECIMAL | `0` en rifas gratis |
| `payment_method_sale` | VARCHAR | `Manual`, `Página Web`, `Gratis` |
| `status_sale` | TINYINT | `1` activa, `0` anulada |
| `date_created_sale` | DATETIME | |

---

### `reservations`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_reservation` | INT PK | |
| `id_raffle_reservation` | INT FK | |
| `id_customer_reservation` | INT FK | |
| `token_reservation` | VARCHAR | `RES-…` (referencia pública) |
| `expires_at_reservation` | DATETIME | +24 h desde creación |
| `status_reservation` | TINYINT | Ver estados |
| `date_created_reservation` | DATETIME | |

#### Estados `status_reservation`

| Valor | Descripción |
|-------|-------------|
| `1` | Pendiente / activa |
| `2` | Cancelada |
| `3` | Completada (convertida en venta) |

---

### `reservation_tickets`

Tabla pivote reserva ↔ ticket.

| Campo | Tipo |
|-------|------|
| `id_reservation_ticket` | INT PK |
| `id_reservation_reservation_ticket` | INT FK |
| `id_ticket_reservation_ticket` | INT FK |
| `date_created_reservation_ticket` | DATETIME |

---

### `admins`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_admin` | INT PK | |
| `email_admin` | VARCHAR | Login |
| `password_admin` | VARCHAR | bcrypt |
| `rol_admin` | ENUM/VARCHAR | `administrador` \| `vendedor` |
| `status_admin` | TINYINT | |
| `date_created_admin` | DATETIME | |
| `date_updated_admin` | DATETIME | |

---

### `settings`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_setting` | INT PK | |
| `key_setting` | VARCHAR | Clave única |
| `value_setting` | TEXT | Valor o ruta de archivo |
| `extra_setting` | VARCHAR | Extensión (imágenes) |
| `type_setting` | VARCHAR | `url`, `toggle`, etc. |
| `description_setting` | VARCHAR | Etiqueta admin |

---

## Reglas de integridad (aplicadas en código)

1. Un ticket solo puede pasar de libre (`0`) a reservado (`2`) o vendido (`1`) con transacción.
2. `claimForSale` exige `status_ticket = 0` (condición optimista).
3. Rifa gratis: máximo **1 ticket activo por cliente por rifa** (vendido o reservado).
4. Rifa de pago web: máximo **50 tickets por reserva**.
5. Anular venta libera tickets y pone `status_sale = 0`.
6. Reutilizar rifa: cancela reservas, borra ventas, resetea todos los tickets a libre.

## Consultas útiles

**Participación de un cliente en rifa:**

Implementada en `Ticket::findParticipationByCustomerInRaffle()`.

**Inventario completo de rifa:**

```sql
SELECT id_ticket, number_ticket, status_ticket
FROM tickets
WHERE id_raffle_ticket = ?
ORDER BY number_ticket ASC
LIMIT 10000;
```

**Ventas activas de una rifa:**

```sql
SELECT s.*, c.name_customer, c.phone_customer
FROM sales s
JOIN customers c ON c.id_customer = s.id_customer_sale
WHERE s.id_raffle_sale = ? AND s.status_sale = 1
ORDER BY s.id_sale DESC;
```

## Índices recomendados (producción)

```sql
-- Si no existen ya en bd.sql
CREATE INDEX idx_tickets_raffle_status ON tickets (id_raffle_ticket, status_ticket);
CREATE INDEX idx_tickets_sale ON tickets (id_sale_ticket);
CREATE INDEX idx_sales_raffle ON sales (id_raffle_sale, status_sale);
CREATE INDEX idx_sales_customer ON sales (id_customer_sale);
CREATE INDEX idx_customers_phone ON customers (phone_customer);
CREATE INDEX idx_reservations_raffle_status ON reservations (id_raffle_reservation, status_reservation);
```
