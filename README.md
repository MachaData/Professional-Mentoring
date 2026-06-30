# Professional Mentoring

Plataforma web para gestionar **programas de acompañamiento basados en sesiones**
(mentoring, coaching, psicología, tutoría, asesoría, liderazgo…). Operada por
**CrossPartners Group**; primer cliente **Las Bambas**.

El sistema **no** está cableado a mentoring: etapas, sesiones, campos de
formulario, labels de rol, plantillas de correo y branding son **administrables**
desde el panel. Ver diseño completo en [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md).

## Stack
- Laravel 12 · PHP 8.2+
- Filament v4 (panel admin) · Blade + Livewire (portales mentor/mentee)
- PostgreSQL 16 · spatie/laravel-permission · spatie/laravel-translatable
- maatwebsite/excel · colas database + scheduler

## Requisitos
- PHP 8.2+ con extensiones `pdo_pgsql`, `mbstring`, `intl`, `gd`, `zip`
- Composer · Node 20+ · PostgreSQL 14+

## Puesta en marcha (local)
```bash
composer install
npm install && npm run build        # o `npm run dev`
cp .env.example .env
php artisan key:generate

# Configura la conexión PostgreSQL en .env (DB_*)
createdb professional_mentoring

php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Panel admin: `http://localhost:8000/admin`

### Credenciales sembradas (cámbialas en producción)
| Rol | Email | Password |
|-----|-------|----------|
| Superadministrador | `superadmin@pro-mentoring.com` | `password` |
| Admin de organización | `admin@crosspartnersgroup.com` | `password` |

## Tests
```bash
createdb professional_mentoring_test
php artisan test
```
La suite usa PostgreSQL (la app depende de operadores JSON de Postgres).

## Estado del proyecto (roadmap por fases)
- [x] **Fase 1 — Fundación:** auth, roles, tenancy por organización, branding de
      login, gestión de organizaciones/clientes/usuarios/tipos de programa, seeders.
- [x] **Fase 2 — Programas, etapas y sesiones configurables:** CRUD de programas
      con etapas y sesiones reordenables (relation managers), labels de rol
      heredados del tipo, y seeder del programa Las Bambas (4 etapas, 10 sesiones
      con fechas reales jun-2026 → abr-2027). Todo editable.
- [ ] Fase 3 — Campos dinámicos, plantillas de formulario y registro de sesiones.
- [ ] Fase 4 — Asignaciones, invitaciones por correo, portales mentor/mentee.
- [ ] Fase 5 — Herramientas, materiales y encuestas.
- [ ] Fase 6 — Plantillas de correo y recordatorios programados.
- [ ] Fase 7 — Dashboards, reportes y export/import Excel.
- [ ] Fase 8 — i18n ES/EN y despliegue (Railway / DigitalOcean).

## Despliegue
Preparado para Railway / DigitalOcean. Variables de entorno para DB, mail,
dominio y storage. En producción: `php artisan migrate --force`, worker de cola
(`queue:work`) y scheduler (`schedule:run`).
