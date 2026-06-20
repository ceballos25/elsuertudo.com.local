# Dinámicas APFenix — Sistema de Rifas

Plataforma web para gestionar rifas numéricas: venta de boletos, reservas, participación gratuita y panel administrativo para vendedores y administradores.

## Características principales

- **Landing pública** — Grilla de números, selección, checkout y enlace por rifa (`/?env=ID`)
- **Rifas de pago** — Reserva vía web + confirmación por WhatsApp (24 h de vigencia)
- **Rifas gratis** — 1 número por persona, venta directa sin confirmación manual
- **Panel admin** — Vender, reservas, clientes, ventas, reportes, configuración del sitio
- **Roles** — `administrador` (acceso total) y `vendedor` (operación diaria)

## Stack tecnológico

| Capa | Tecnología |
|------|------------|
| Backend | PHP 8+ (MVC propio, PSR-4) |
| Base de datos | MySQL / MariaDB (PDO) |
| Frontend público | Bootstrap 5.3, JavaScript vanilla |
| Frontend admin | Bootstrap, jQuery, ApexCharts, Select2 |
| API | Un solo endpoint AJAX POST |

## Inicio rápido

```bash
# 1. Clonar / copiar el proyecto
# 2. Instalar autoload
composer install

# 3. Crear .env-dinamicas (un nivel arriba del proyecto)
# Ver: docs/configuracion.md

# 4. Importar esquema base + migraciones
mysql -u usuario -p nombre_bd < bd.sql
mysql -u usuario -p nombre_bd < database/migrations/001_add_is_free_raffle.sql

# 5. Acceder
# Público:  {SITE_URL}/?env=1
# Admin:    {SITE_URL}/dash.php
```

## Documentación

| Documento | Contenido |
|-----------|-----------|
| [docs/arquitectura.md](docs/arquitectura.md) | Estructura del proyecto, capas MVC, módulos |
| [docs/api.md](docs/api.md) | Referencia completa del API AJAX |
| [docs/base-de-datos.md](docs/base-de-datos.md) | Tablas, campos y estados |
| [docs/flujos.md](docs/flujos.md) | Flujos de usuario (público y admin) |
| [docs/funcionalidades.md](docs/funcionalidades.md) | Rifas gratis, permisos, reglas de negocio |
| [docs/configuracion.md](docs/configuracion.md) | Variables de entorno |
| [docs/despliegue.md](docs/despliegue.md) | Guía de producción y checklist |

## Estructura del proyecto

```
├── index.php              # Landing pública
├── dash.php               # Login admin
├── config/                # Bootstrap y carga de .env
├── app/
│   ├── Controllers/       # Entrada HTTP/AJAX
│   ├── Services/          # Lógica de negocio
│   ├── Models/            # Acceso a datos
│   ├── Core/              # Auth, DB, permisos, RaffleMode
│   └── Support/           # Reglas compartidas
├── front/                 # Vistas del panel admin
│   └── ajax/api.php       # Punto de entrada API
├── assets/                # CSS, JS, librerías
├── includes/              # Layout compartido admin
├── database/migrations/   # Migraciones SQL
└── uploads/settings/      # Logo y favicon (runtime)
```

## Enlaces útiles

- Landing de una rifa: `{SITE_URL}/?env={id_raffle}`
- API: `POST {SITE_URL}/front/ajax/api.php`
- Parámetros: `module`, `action` + datos del formulario

## Licencia y autor

Desarrollado por Cristian Ceballos — [RifaCloud](https://rifacloud-landing.cristianceballos.com/)
