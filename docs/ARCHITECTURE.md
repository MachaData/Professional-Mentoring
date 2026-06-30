# Professional Mentoring — Arquitectura

Plataforma web para gestionar **programas de acompañamiento basados en sesiones**
(mentoring, coaching, psicología, tutoría, asesoría, liderazgo, etc.).

Primer caso de uso: **Professional Mentoring** operada por **CrossPartners Group**,
cliente inicial **Las Bambas**. El sistema NO está cableado a mentoring: etapas,
sesiones, campos de formulario, labels de rol, plantillas de correo y branding son
**100% administrables** desde el panel.

---

## 1. Stack

| Capa | Tecnología |
|------|-----------|
| Framework | Laravel 12 · PHP 8.2+ |
| Panel admin (superadmin + org-admin) | Filament v4 (multi-panel) |
| Portales mentor / mentee | Blade + Livewire (branded, responsive) |
| Público (landing + login brandeado) | Blade |
| Roles / permisos | spatie/laravel-permission |
| Multi-tenant | Single-DB, row-level por `organization_id` (Filament tenancy) |
| Contenido bilingüe (ES/EN, +idiomas) | spatie/laravel-translatable (columnas JSON) |
| Base de datos | PostgreSQL 16 (JSONB) |
| Mail / colas | Mailable + database queue + scheduler |
| Excel (import/export) | maatwebsite/excel |

## 2. Modelo de tenencia

```
Superadmin  /admin        → CrossPartners opera todo; crea organizaciones
Org-admin   /app          → scoped a su organización (tenant = Organization)
Mentor      /mentor       → solo sus asignaciones
Mentee      /me           → solo su programa
Público     / , /{slug}   → landing + login con branding del cliente
```

- **Organization = tenant.** CrossPartners Group es la organización operadora.
- **Las Bambas = `client`** dentro de la organización (no es otra organización),
  con su propio branding (logo, colores). Así se agregan más clientes/programas
  sin multiplicar organizaciones.
- Todo modelo de negocio lleva `organization_id`; un trait `BelongsToOrganization`
  + global scope aíslan los datos. Las Policies validan ownership por encima.

## 3. Jerarquía de datos

```
Organization
 └─ Client (Las Bambas)
     └─ Program (Professional Mentoring Las Bambas)
         ├─ Stage (Exploración, Planeamiento, Implementación, Cierre)
         │   └─ Session (1..N, ventana de fechas, objetivo)
         │       └─ SessionRecord (por asignación)  ── SessionRecordValue (campos dinámicos)
         ├─ CustomField / FormTemplate  (formularios dinámicos)
         ├─ Assignment (mentor ↔ mentee)
         ├─ Tool / Material  (biblioteca reutilizable, visibilidad por rol)
         ├─ Survey  (link externo: programa / etapa / sesión)
         ├─ Reminder / EmailTemplate / EmailLog
         └─ Training (capacitaciones: kick-off, etapas...)
```

## 4. Esquema de base de datos

Ver `docs/DATABASE.md` para el detalle de columnas. Tablas:

**Fundación:** `organizations`, `clients`, `users`, `roles`, `permissions` (+ pivotes spatie).

**Estructura:** `program_types`, `programs`, `stages`, `sessions`.

**Formularios dinámicos:** `form_templates`, `custom_fields`,
`session_records`, `session_record_values`.

**Personas:** `assignments`.

**Recursos:** `tools`, `tool_relations` (polimórfica), `materials`, `surveys`.

**Comunicaciones:** `email_templates`, `reminders`, `email_logs`, `trainings`.

### Decisiones de diseño
- **Contenido traducible** (`name`, `objective`, `label`, `subject`, `message`…)
  se guarda como **JSON `{"es": "...", "en": "..."}`** vía `spatie/translatable`,
  extensible a N idiomas sin migraciones.
- **`session_record_values`** usa columnas tipadas (`value_text`, `value_number`,
  `value_date`, `value_file`, `value_json`) elegidas según `field_type`.
- **`tools` + `tool_relations`** polimórficas permiten reutilizar un recurso en
  programa, etapa o sesión. `materials` se mantiene separada solo si se requiere
  flujo distinto; por defecto un material es un `tool` con `type`.

## 5. Roles y labels visibles

Roles base (internos): `superadmin`, `organization_admin`, `facilitator`,
`participant`. El **nombre visible** se resuelve por programa →
`program.facilitator_label` / `participant_label` (hereda de `program_type`).

| Programa | facilitator_label | participant_label |
|----------|-------------------|-------------------|
| Mentoring | Mentor | Mentee |
| Coaching | Coach | Coachee |
| Psicología | Psicólogo | Paciente |

## 6. Flujos clave
1. **Onboarding usuario:** alta → contraseña temporal → correo de bienvenida con
   link + credenciales → cambio obligatorio al primer ingreso. Estado de
   invitación: `pending` → `sent` → `active`.
2. **Configurar programa:** etapas → sesiones (fechas/objetivo) → campos/plantillas
   → materiales/herramientas/encuestas. Todo editable.
3. **Registro de sesión:** mentor elige mentee + sesión → ve objetivo y materiales
   → completa formulario **dinámico** → borrador o completado.
4. **Recordatorios:** scheduler detecta ventanas → encola correos → `email_logs`.

## 7. i18n
- UI: archivos de idioma Laravel (`lang/es`, `lang/en`) + selector de locale.
- Contenido: columnas JSON traducibles. Default ES, fallback EN.

## 8. Despliegue (Railway / DigitalOcean)
- Variables de entorno para DB, mail, dominio, storage.
- `php artisan migrate --force`, `db:seed` (estado inicial Las Bambas).
- Worker de cola (`queue:work`) + scheduler (`schedule:run` cada minuto).
- Storage público para fotos/logos/archivos; SSL en el dominio `pro-mentoring.com`.

## 9. Roadmap
1. **Fundación** — scaffold, auth, tenancy, organizations/users, branding login, seeders base. ← *actual*
2. **Programa configurable** — program_types, programs, stages, sessions.
3. **Formularios dinámicos** — custom_fields, form_templates, session_records.
4. **Personas y portales** — assignments, invitaciones, portal mentor/mentee.
5. **Recursos** — tools/materials, surveys.
6. **Comunicaciones** — email templates, reminders, logs.
7. **Dashboards + reportes + export/import Excel.**
8. **i18n ES/EN + despliegue.**
