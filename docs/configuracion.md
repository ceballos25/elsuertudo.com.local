# Configuración

## Archivo de entorno

El proyecto **no** usa `.env` en la raíz. Carga un archivo externo:

```
{padre_del_proyecto}/.env-dinamicas
```

**Ejemplo de rutas:**

| Proyecto | Archivo env |
|----------|-------------|
| `/home/usuario/public_html/dinamicas/` | `/home/usuario/.env-dinamicas` |
| `/home/cristian-ceballos/websites/dinamicas.apfenix.local/` | `/home/cristian-ceballos/websites/.env-dinamicas` |

Si el archivo no existe o no es legible, la aplicación lanza excepción al iniciar.

### Formato

```env
# URL pública (sin barra final)
SITE_URL=https://tudominio.com

# Base de datos
DB_HOST=localhost
DB_PORT=3306
DB_USER=mi_usuario
DB_PASS=mi_contraseña
DB_NAME=mi_base
DB_CHARSET=utf8mb4

# Aplicación
SITE_NAME=El día de TU SUERTE
APP_ENV=production
DEBUG_MODE=false
TIMEZONE=America/Bogota

# Errores PHP
DISPLAY_ERRORS=false

# Sesión
SESSION_NAME=SAAS_RIFA
SESSION_LIFETIME=28800
SESSION_AUTO_START=true
SESSION_COOKIE_HTTPONLY=true
SESSION_COOKIE_SECURE=true
SESSION_COOKIE_SAMESITE=Lax

# Ventas (reservados para uso futuro)
SALE_PREFIX=EDTS-
SALE_PAD=6
```

## Variables detalladas

| Variable | Default | Descripción |
|----------|---------|-------------|
| `SITE_URL` | `''` | Base para assets, redirects y enlaces públicos |
| `DB_HOST` | `localhost` | Servidor MySQL |
| `DB_PORT` | `3306` | Puerto MySQL |
| `DB_USER` | | Usuario BD |
| `DB_PASS` | | Contraseña BD |
| `DB_NAME` | | Nombre de la base |
| `DB_CHARSET` | `utf8mb4` | Charset PDO |
| `SITE_NAME` | `Mi Rifa` | Fallback si no hay setting en BD |
| `APP_ENV` | `production` | Entorno lógico |
| `DEBUG_MODE` | `false` | Si `true`, API 500 expone mensaje de excepción |
| `TIMEZONE` | `America/Bogota` | Zona horaria PHP |
| `DISPLAY_ERRORS` | `false` | Mostrar errores PHP en pantalla |
| `SESSION_NAME` | `SAAS_RIFA` | Nombre cookie de sesión |
| `SESSION_LIFETIME` | `28800` | 8 horas (segundos) |
| `SESSION_AUTO_START` | `true` | Iniciar sesión en bootstrap |
| `SESSION_COOKIE_HTTPONLY` | `true` | Cookie no accesible desde JS |
| `SESSION_COOKIE_SECURE` | `false` | `true` obligatorio con HTTPS |
| `SESSION_COOKIE_SAMESITE` | `Lax` | Política SameSite |
| `SALE_PREFIX` | `EDTS-` | No usado actualmente en código |
| `SALE_PAD` | `6` | No usado actualmente en código |

## Constantes PHP generadas

Tras cargar el env, `config/config.php` define:

```php
ROOT_PATH      // Raíz del proyecto
BASE_URL       // SITE_URL sin barra final
ASSETS_URL     // BASE_URL + '/assets'
DB_*           // Credenciales
DEBUG_MODE     // bool
```

## Configuración desde panel (BD)

Settings en tabla `settings`, gestionados en **Mi Negocio** (`front/settings.php`).

### Claves públicas (visibles sin login)

| Clave | Uso |
|-------|-----|
| `site_name` | Título del sitio |
| `site_logo` | Logo header |
| `site_favicon` | Favicon |
| `whatsapp_line_main` | WhatsApp checkout |
| `whatsapp_line_support` | WhatsApp alternativo |
| `facebook_url` | Red social landing |
| `instagram_url` | Red social landing |
| `site_active` | `1` = landing activa |

### Uploads

Imágenes de settings se guardan en:

```
uploads/settings/
```

Requiere permisos de escritura para el usuario del servidor web.

## Tema visual

Variables CSS en `assets/css/theme-base.css`:

- Colores primarios de la rifa
- Estados de números: libre, seleccionado, vendido
- Usados en landing y recibos (`ThemeConfig`)

## Desarrollo local

```env
APP_ENV=development
DEBUG_MODE=true
DISPLAY_ERRORS=true
SITE_URL=http://dinamicas.apfenix.local
SESSION_COOKIE_SECURE=false
```

## Producción recomendada

```env
APP_ENV=production
DEBUG_MODE=false
DISPLAY_ERRORS=false
SESSION_COOKIE_SECURE=true
SITE_URL=https://tudominio.com
```
