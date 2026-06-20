# Índice de documentación

Documentación técnica del sistema de rifas Dinámicas APFenix.

## Guías

1. **[Arquitectura](arquitectura.md)** — Capas, carpetas, responsabilidades y diagrama general
2. **[API AJAX](api.md)** — Todos los módulos, acciones, autenticación y ejemplos
3. **[Base de datos](base-de-datos.md)** — Tablas, relaciones, estados de tickets y ventas
4. **[Flujos de usuario](flujos.md)** — Recorridos públicos y administrativos paso a paso
5. **[Funcionalidades](funcionalidades.md)** — Rifas gratis, nombres en grilla, fulfillment modular
6. **[Configuración](configuracion.md)** — Archivo `.env-dinamicas` y ajustes del sitio
7. **[Despliegue](despliegue.md)** — Producción, migraciones, checklist y troubleshooting

## Convenciones

- **Rifa activa** — `status_raffle = 1`
- **Ticket libre** — `status_ticket = 0`
- **Ticket vendido** — `status_ticket = 1`
- **Ticket reservado** — `status_ticket = 2`
- **Rifa gratis** — `is_free_raffle = 1` (precio forzado a 0)

## Actualización

Esta documentación refleja el estado del código con soporte para **rifas gratis** (`is_free_raffle`) y arquitectura modular de ventas (`SaleFulfillmentService`).
