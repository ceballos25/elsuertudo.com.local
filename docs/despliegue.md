# Despliegue a producción

## Requisitos del servidor

| Requisito | Versión mínima |
|-----------|----------------|
| PHP | 8.0+ |
| Extensiones | PDO MySQL, session, json, mbstring, fileinfo |
| MySQL / MariaDB | 5.7+ / 10.3+ |
| Composer | 2.x (solo para autoload) |
| HTTPS | Recomendado (cookies seguras) |

## Checklist de despliegue

### 1. Preparar servidor

- [ ] Virtual host apuntando a la raíz del proyecto
- [ ] PHP-FPM o mod_php configurado
- [ ] Base de datos creada (utf8mb4)

### 2. Subir código

- [ ] Todos los archivos del proyecto (excepto `.git` si no aplica)
- [ ] Ejecutar `composer install --no-dev` en el servidor
- [ ] Permisos escritura en `uploads/settings/`

### 3. Configurar entorno

- [ ] Crear `.env-dinamicas` un nivel arriba del proyecto
- [ ] `SITE_URL` con dominio real HTTPS
- [ ] Credenciales BD correctas
- [ ] `DEBUG_MODE=false`, `DISPLAY_ERRORS=false`
- [ ] `SESSION_COOKIE_SECURE=true` (con HTTPS)

### 4. Base de datos

- [ ] Importar esquema base (`bd.sql`)
- [ ] Ejecutar migraciones:

```bash
mysql -u USUARIO -p NOMBRE_BD < database/migrations/001_add_is_free_raffle.sql
```

Verificar columna:

```sql
SHOW COLUMNS FROM raffles LIKE 'is_free_raffle';
```

### 5. Verificación funcional

- [ ] Login admin: `{SITE_URL}/dash.php`
- [ ] Settings: logo, WhatsApp, `site_active=1`
- [ ] Crear rifa de prueba
- [ ] Landing paga: `/?env=ID` → reserva
- [ ] Landing gratis: rifa con switch activo → confirmación inmediata
- [ ] Grilla: números vendidos y libres mismo tamaño
- [ ] Venta manual desde vender.php

### 6. Cache busting

Tras deploy, los assets usan query string de versión:

| Archivo | Parámetro actual |
|---------|------------------|
| `front.css` | `?v=43` |
| `raffle.js` | `?v=16` |
| `rifas.js` | `?v=25` |
| `theme-base.css` | `?v=6` |

Incrementar versión en `index.php` / `front/rifas.php` / `includes/head.php` si cambian CSS/JS.

---

## Estructura de archivos críticos (deploy)

### Nuevos (rifas gratis)

```
app/Core/RaffleMode.php
app/Support/ParticipationRules.php
app/Services/SaleFulfillmentService.php
app/Services/FreeParticipationService.php
app/Controllers/ParticipationController.php
database/migrations/001_add_is_free_raffle.sql
```

### Modificados frecuentemente

```
front/ajax/api.php
app/Services/SaleService.php
app/Services/RaffleService.php
app/Services/ReservationService.php
app/Models/Ticket.php
assets/js/raffle.js
assets/js/rifas.js
assets/css/front.css
front/rifas.php
index.php
```

---

## Apache / Nginx

### Apache (ejemplo)

DocumentRoot = raíz del proyecto. Asegurar que `index.php` es DirectoryIndex.

### Nginx (ejemplo)

```nginx
root /var/www/dinamicas;
index index.php;

location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

---

## Seguridad

1. **`.env-dinamicas`** fuera del document root (un nivel arriba).
2. No commitear credenciales ni `bd.sql` con datos reales.
3. `DEBUG_MODE=false` en producción.
4. HTTPS + `SESSION_COOKIE_SECURE=true`.
5. Contraseñas admin con bcrypt (ya implementado en `AuthService`).
6. API pública expone nombres en grilla — evaluar con el cliente.

---

## Troubleshooting

| Problema | Causa probable | Solución |
|----------|----------------|----------|
| Pantalla blanca al iniciar | `.env-dinamicas` no encontrado | Crear archivo en ruta correcta |
| Error al crear rifa | Falta columna `is_free_raffle` | Ejecutar migración 001 |
| API 500 genérico | Error PHP/BD | Revisar logs; temporalmente `DEBUG_MODE=true` |
| Landing sin rifa | Falta `?env=ID` en URL | Usar enlace completo |
| Reserva falla en gratis | Rifa marcada como gratis | Usar flujo participación, no reserva |
| CSS viejo en móvil | Cache navegador | Incrementar `?v=` en assets |
| WhatsApp no abre | `whatsapp_line_main` vacío | Configurar en Settings |
| “Sitio inactivo” | `site_active=0` | Activar en Mi Negocio |

### Logs

Errores PHP se registran con `error_log()` en servicios:

```
SaleFulfillmentService::fulfill - ...
FreeParticipationService / ReservationService / etc.
```

Ubicación según configuración PHP del servidor (`/var/log/php*.log` o log de Apache/Nginx).

---

## Rollback

1. Restaurar código anterior
2. La columna `is_free_raffle` es compatible hacia atrás (default `0`)
3. No es necesario revertir migración si rifas existentes no usan modo gratis

---

## Mantenimiento

### Backup recomendado

- Dump MySQL diario
- Copia de `uploads/settings/`
- Copia de `.env-dinamicas`

### Actualizar rifa en producción

1. Subir archivos changed
2. Ejecutar nuevas migraciones en `database/migrations/`
3. Limpiar cache CDN/navegador si aplica
4. Smoke test landing + admin

---

## Contacto técnico

Documentación generada para el proyecto Dinámicas APFenix.  
Desarrollador: [Cristian Ceballos](https://rifacloud-landing.cristianceballos.com/)
