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
6. Configura el dominio (`pro-mentoring.com`); Railway provee SSL automático.
7. Primer despliegue: ejecuta el seed inicial una vez con
   `php artisan db:seed --force` (crea CrossPartners, Las Bambas y el superadmin).

## DigitalOcean (App Platform)

1. Crea una base de datos **Managed PostgreSQL**.
2. Crea una App desde el repo; component **Web Service** (build: `composer install
   --no-dev && npm ci && npm run build`; run: sirve `public/`).
3. Añade componentes **Worker** (`php artisan queue:work`) y un **Job**/cron para
   el scheduler (`php artisan schedule:run` cada minuto) o un worker
   `php artisan schedule:work`.
4. Variables de entorno como arriba; SSL gestionado por la plataforma.

## Post-despliegue
- Cambia las contraseñas de los usuarios sembrados.
- Sube los logos/branding desde el panel del superadmin.
- Verifica el envío de correo enviando una invitación de prueba.
- El scheduler dispara `reminders:dispatch` cada día a las 08:00.

## Backups
- Habilita backups automáticos de la base gestionada (Railway/DO los ofrecen).
- Si usas `FILESYSTEM_DISK=public`, considera migrar a S3/Spaces para persistir
  archivos entre despliegues.
