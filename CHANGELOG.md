# Changelog

Registro de cambios relevantes del proyecto.

## [Unreleased] — 2026-05-26

### Añadido

- **Rifas gratis** (`is_free_raffle`): switch en admin, participación web directa a venta
- Módulo API `participacion/registrar_gratis`
- `FreeParticipationService` — 1 número por teléfono por rifa
- `SaleFulfillmentService` — fulfillment atómico reutilizable
- `RaffleMode` y `ParticipationRules` — reglas centralizadas
- `ParticipationController` y columna BD `is_free_raffle`
- Documentación completa en `/docs`

### Cambiado

- Landing: UI adaptativa para rifas gratis (sin totales, sin WhatsApp)
- `SaleService` refactorizado para usar `SaleFulfillmentService`
- `ReservationService` bloquea reservas en rifas gratis
- Grilla pública: nombres de cliente en números vendidos/reservados
- `Ticket::getSoldCustomerInfo` — LEFT JOIN para clientes sin venta directa
- CSS grilla: altura uniforme libre/vendido/reservado (`front.css v43`)

### Documentación

- `README.md` principal
- Guías en `docs/`: arquitectura, API, BD, flujos, funcionalidades, config, despliegue

---

## Versiones anteriores

Historial previo no documentado en este archivo. Consultar git log para cambios anteriores.
