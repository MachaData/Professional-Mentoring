# Despliegue — Professional Mentoring

Guía para desplegar en **Railway** o **DigitalOcean**. La app es Laravel 12 +
PostgreSQL, con colas (driver `database`) y un scheduler para recordatorios.

## Variables de entorno (obligatorias)

```env
APP_NAME="Professional Mentoring"
APP_ENV=production
APP_KEY=            # php artisan key:generate --show
APP_DEBUG=false
APP_URL=https://pro-mentoring.com
APP_LOCALE=es
APP_FALLBACK_LOCALE=en

# Multi-tenant: dominio base para subdominios por cliente.
# lasbambas.pro-mentoring.com  →  Organización con slug "lasbambas".
# Vacío = login genérico sin subdominios.
APP_BASE_DOMAIN=pro-mentoring.com

# PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=...
DB_PORT=5432
DB_DATABASE=professional_mentoring
DB_USERNAME=...
DB_PASSWORD=...

# Colas y cache
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database

# Storage (archivos subidos: logos, fotos, materiales)
FILESYSTEM_DISK=public       # o s3 con AWS_* para almacenamiento externo

# Correo (invitaciones y recordatorios)
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="no-reply@pro-mentoring.com"
MAIL_FROM_NAME="Professional Mentoring"
```

## Procesos

| Proceso | Comando | Necesario |
|---------|---------|-----------|
| Web | servidor HTTP sirviendo `public/` | sí |
| Release | `php artisan migrate --force && php artisan storage:link` | sí (al desplegar) |
| Worker | `php artisan queue:work --tries=3 --max-time=3600` | sí (correos) |
| Scheduler | `php artisan schedule:work` | sí (recordatorios diarios) |

## Railway

1. Crea un proyecto y añade un **PostgreSQL**. Copia sus credenciales a las `DB_*`.
2. Conecta el repositorio. Railway detecta Nixpacks (`nixpacks.toml`).
3. Configura las variables de entorno de arriba. Genera `APP_KEY` con
   `php artisan key:generate --show` y pégalo.
4. El servicio web ejecuta migraciones y arranca automáticamente (ver `nixpacks.toml`).
5. Añade **dos servicios adicionales** apuntando al mismo repo con estos comandos
   de inicio:
   - Worker: `php artisan queue:work --tries=3 --max-time=3600`
   - Scheduler: `php artisan schedule:work`
6. Configura el dominio (`pro-mentoring.com`) y el comodín `*.pro-mentoring.com`
   para los subdominios por cliente; Railway provee SSL automático.
7. **Persistencia de archivos (importante):** el sistema de archivos de Railway
   es efímero — los logos, fondos y fotos subidos se **pierden en cada
   redespliegue**. Añade un **Volume** montado en `storage/app/public`, o
   configura `FILESYSTEM_DISK=s3` con un bucket (S3/Cloudflare R2/DO Spaces).
   Sin esto, la marca de cada cliente desaparecería al actualizar la app.
8. Primer despliegue: ejecuta el seed inicial una vez con
   `php artisan db:seed --force` (crea CrossPartners, Las Bambas y el superadmin).

## Subdominios por cliente (multi-tenant)

Cada empresa cliente es una **Organización** con un `slug`. El middleware
`ResolveTenant` lee el host de la petición y muestra el login con la marca del
cliente (logo, fondo, color, nombre) y restringe el acceso a sus miembros.

Para activarlo en producción:

1. Define `APP_BASE_DOMAIN=pro-mentoring.com` (ver variables arriba).
2. **DNS wildcard**: crea un registro `*.pro-mentoring.com` apuntando al mismo
   servicio web (en Railway: añade el dominio comodín `*.pro-mentoring.com` en
   Settings → Domains; requiere un CNAME wildcard en tu proveedor DNS).
3. El certificado SSL debe cubrir el comodín. Railway/Cloudflare emiten
   certificados wildcard automáticamente para dominios verificados.
4. Crea cada cliente como Organización en el panel del superadmin y asígnale un
   `slug` (ej. `lasbambas`). Su espacio queda en `lasbambas.pro-mentoring.com`.
   - `www`, `app`, `admin`, `operador`, `panel` están reservados (login genérico).
5. Local sin DNS: puedes probar con `?tenant=<slug>` (solo fuera de producción).

> Los subdominios centrales y el dominio raíz muestran el login de la
> plataforma; el panel de administración vive en `/admin`.

## DigitalOcean (App Platform)

1. Crea una base de datos **Managed PostgreSQL**.
2. Crea una App desde el repo; component **Web Service** (build: `composer install
   --no-dev && npm ci && npm run build`; run: sirve `public/`).
3. Añade componentes **Worker** (`php artisan queue:work`) y un **Job**/cron para
   el scheduler (`php artisan schedule:run` cada minuto) o un worker
   `php artisan schedule:work`.
4. Variables de entorno como arriba; SSL gestionado por la plataforma.

## Seguridad del seed inicial (¡leer antes de producción!)

El seed crea el superadmin y el admin de la operadora. **No los dejes con la
contraseña por defecto** en una URL pública. Define en Railway:

```env
SEED_SUPERADMIN_PASSWORD=<contraseña-fuerte>   # superadmin@pro-mentoring.com
SEED_ADMIN_PASSWORD=<contraseña-fuerte>        # admin@crosspartnersgroup.com
SEED_DEMO=false                                # omite las cuentas *@demo.test de prueba
```

- Con `SEED_DEMO=false` **no** se crean las cuentas demo (mentor/mentee/coordinador
  `@demo.test`). Úsalo cuando ya tengas usuarios reales. Si quieres mostrar el
  sistema con datos de ejemplo primero, déjalo en `true` y bórralas después.
- Las cuentas demo se pueden sembrar aparte cuando quieras:
  `php artisan db:seed --class=DemoDuplaSeeder --force`.
- Cambia igualmente las contraseñas desde el panel tras el primer ingreso.

## Post-despliegue
- Cambia las contraseñas de los usuarios sembrados.
- Sube los logos/branding desde el panel del superadmin.
- Verifica el envío de correo enviando una invitación de prueba.
- El scheduler dispara `reminders:dispatch` cada día a las 08:00.

## Backups
- Habilita backups automáticos de la base gestionada (Railway/DO los ofrecen).
- Si usas `FILESYSTEM_DISK=public`, considera migrar a S3/Spaces para persistir
  archivos entre despliegues.
