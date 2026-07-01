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
| Mentor (demo) | `mentor@demo.test` | `password` |
| Mentee (demo) | `mentee@demo.test` | `password` |

Panel admin en `/admin`; portales mentor/mentee en `/login`.

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
- [x] **Fase 3 — Campos dinámicos y plantillas:** definición de campos por sesión
      o plantilla (15 tipos: texto, fecha, select, rating, archivo, etc.), builder
      dinámico que renderiza el formulario y persiste respuestas en columnas
      tipadas (`session_records` / `session_record_values`). Seeder con los 9 campos
      base de registro de mentoring en cada sesión de Las Bambas. Todo editable.
- [x] **Fase 4 — Asignaciones, invitaciones y portales:** asignación mentor↔mentee
      con aprovisionamiento automático de registros de sesión; invitación por correo
      con contraseña temporal + cambio obligatorio al primer ingreso (con log de
      envíos); portal del mentor (dashboard, participantes, registro de sesión con
      formulario dinámico en Livewire) y portal del mentee (programa, mentor,
      sesiones y acuerdos visibles). Login branded y control de acceso por rol.
- [x] **Fase 5 — Herramientas, materiales y encuestas:** biblioteca reutilizable
      de recursos (16 tipos) asociable a programa/etapa/sesión con visibilidad por
      rol; encuestas externas por link (Google Forms) con alcance y momento de
      visualización. `ResourceResolver` filtra por rol y los recursos aparecen en
      los portales de mentor y mentee. Seeder con el workbook, la guía y la encuesta
      de satisfacción de Las Bambas.
- [ ] Fase 6 — Plantillas de correo y recordatorios programados.
- [ ] Fase 7 — Dashboards, reportes y export/import Excel.
- [ ] Fase 8 — i18n ES/EN y despliegue (Railway / DigitalOcean).

## Despliegue
Preparado para Railway / DigitalOcean. Variables de entorno para DB, mail,
dominio y storage. En producción: `php artisan migrate --force`, worker de cola
(`queue:work`) y scheduler (`schedule:run`).
