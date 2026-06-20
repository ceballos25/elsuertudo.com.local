# El Suertudo — Sistema de Rifas (2 cifras)

Plataforma web de **El Suertudo** para gestionar dinámicas numéricas de 2 cifras: venta de boletos, reservas, participación gratuita y panel administrativo para vendedores y administradores.

**Landing pública del cliente:** [landing.elsuertudo.com.co](https://landing.elsuertudo.com.co/)  
**CDN de assets:** `https://cdn-el.elsuertudo.com.co`

## Características principales

- **Landing pública** — Grilla de números, selección, checkout y enlace por rifa (`/?env=ID`)
- **Rifas de pago** — Reserva vía web + confirmación por WhatsApp (24 h de vigencia)
- **Rifas gratis** — 1 número por persona, venta directa sin confirmación manual
- **Panel admin** — Vender, reservas, clientes, ventas, reportes, configuración del sitio
- **Roles** — `administrador` (acceso total) y `vendedor` (operación diaria)
- **Marca El Suertudo** — Colores verde/naranja, logos desde CDN, comprobante de venta con logo

## Stack tecnológico

| Capa | Tecnología |
|------|------------|
| Backend | PHP 8+ (MVC propio, PSR-4) |
| Base de datos | MySQL / MariaDB (PDO) |
| Frontend público | Bootstrap 5.3, JavaScript vanilla |
| Frontend admin | Bootstrap, jQuery, ApexCharts, Select2 |
| API | Un solo endpoint AJAX POST |

## Inicio rápido (local)

```bash
# 1. Clonar / copiar el proyecto
# 2. Instalar autoload
composer install

# 3. Crear .env-el (un nivel arriba del proyecto)
# Ejemplo: /home/usuario/websites/.env-el
# Ver: docs/configuracion.md

# 4. Crear base de datos e importar esquema
mysql -u usuario -p -e "CREATE DATABASE elsuertudo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
# Importar estructura desde un dump del proyecto o BD de referencia

# 5. Acceder
# Público:  {SITE_URL}/?env=ID_RIFA
# Admin:    {SITE_URL}/dash.php
```

### Variables clave en `.env-el`

```env
SITE_NAME=El Suertudo
SITE_URL=https://elsuertudo.com.local
CDN_LOGOS_URL=https://cdn-el.elsuertudo.com.co/logos
CONTACT_PHONE=+57 320 5817000
WHATSAPP_URL=https://chat.whatsapp.com/BbAI6b7i6xDFKBijJZIjW7
SALE_PREFIX=EL-
SESSION_NAME=EL_SUERTUDO
```

El bootstrap carga el archivo desde `config/config.php` → `../../.env-el`.

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
├── index.php              # Landing pública (grilla de números)
├── dash.php               # Login admin
├── config/                # Bootstrap y carga de .env-el
├── app/
│   ├── Controllers/       # Entrada HTTP/AJAX
│   ├── Services/          # Lógica de negocio
│   ├── Models/            # Acceso a datos
│   ├── Core/              # Auth, DB, permisos, RaffleMode
│   └── Support/           # Reglas compartidas
├── front/                 # Vistas del panel admin
│   └── ajax/api.php       # Punto de entrada API
├── assets/
│   ├── css/theme-base.css # Paleta El Suertudo
│   └── images/logos/      # Logos sincronizados desde CDN
├── includes/              # Layout compartido admin + template comprobante
└── uploads/settings/      # Logo/favicon subidos desde el panel (runtime)
```

## Enlaces útiles

- Landing de una rifa: `{SITE_URL}/?env={id_raffle}`
- API: `POST {SITE_URL}/front/ajax/api.php`
- Parámetros: `module`, `action` + datos del formulario
- Mi Negocio (admin): logo, WhatsApp, redes, activar/desactivar sitio

## Producción

Antes de lanzar, revisar [docs/despliegue.md](docs/despliegue.md):

- `.env-el` en el servidor con `SITE_URL` HTTPS real
- `SESSION_COOKIE_SECURE=true`
- `DEBUG_MODE=false` y `DISPLAY_ERRORS=false`
- Credenciales de BD del hosting (no las de local)
- Rifa real creada y probada end-to-end

## Licencia y autor

Desarrollado por Cristian Ceballos — [RifaCloud](https://rifacloud-landing.cristianceballos.com/)
