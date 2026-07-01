<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use App\Models\Organization;
use App\Models\Program;
use App\Models\Reminder;
use Illuminate\Database\Seeder;

/**
 * Default (editable) email templates and reminders. All copy uses {{variables}}
 * and can be edited from the admin panel.
 */
class CommunicationsSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::where('slug', 'crosspartners-group')->first();
        if (! $org) {
            return;
        }
        $program = Program::where('slug', 'professional-mentoring-las-bambas')->first();

        $this->template($org->id, 'invitation', 'Invitación de acceso',
            'Bienvenido/a a {{program_name}}',
            "Hola {{user_name}},\n\nSe ha creado tu acceso a la plataforma. Ingresa con tu correo y la contraseña temporal que te compartimos.\n\nAccede aquí: {{platform_link}}\n\nSaludos.",
            'Welcome to {{program_name}}',
            "Hi {{user_name}},\n\nYour access has been created. Sign in with your email and the temporary password we shared.\n\nAccess here: {{platform_link}}\n\nBest regards.");

        $this->template($org->id, 'welcome', 'Bienvenida al programa',
            '¡Comienza tu programa de mentoring!',
            "Hola {{user_name}},\n\nTe damos la bienvenida a {{program_name}}. Muy pronto iniciarás tu proceso.\n\nIngresa a la plataforma: {{platform_link}}",
            'Your mentoring program begins!',
            "Hi {{user_name}},\n\nWelcome to {{program_name}}. Your journey starts soon.\n\nEnter the platform: {{platform_link}}");

        $this->template($org->id, 'reminder_session', 'Recordatorio de próxima sesión',
            'Recordatorio: {{session_name}} de {{program_name}}',
            "Hola {{user_name}},\n\nTe recordamos tu sesión **{{session_name}}**.\nVentana: {{start_date}} – {{end_date}}.\n\nCoordina con {{facilitator_name}} y prepara tu workbook.\n\n{{platform_link}}",
            'Reminder: {{session_name}} of {{program_name}}',
            "Hi {{user_name}},\n\nA reminder about your session **{{session_name}}**.\nWindow: {{start_date}} – {{end_date}}.\n\nCoordinate with {{facilitator_name}} and prepare your workbook.\n\n{{platform_link}}");

        $this->template($org->id, 'reminder_register', 'Recordatorio de registro de avance',
            'Registra el avance de {{session_name}}',
            "Hola {{user_name}},\n\nRecuerda registrar el avance de la sesión **{{session_name}}** con {{participant_name}} antes del cierre de la ventana ({{end_date}}).\n\n{{platform_link}}",
            'Log the progress of {{session_name}}',
            "Hi {{user_name}},\n\nRemember to log the progress of session **{{session_name}}** with {{participant_name}} before the window closes ({{end_date}}).\n\n{{platform_link}}");

        if ($program) {
            $this->reminders($org->id, $program->id);
        }
    }

    protected function template(int $orgId, string $key, string $name, string $subEs, string $bodyEs, string $subEn, string $bodyEn): void
    {
        EmailTemplate::updateOrCreate(
            ['organization_id' => $orgId, 'program_id' => null, 'key' => $key],
            [
                'name' => $name,
                'subject' => ['es' => $subEs, 'en' => $subEn],
                'body' => ['es' => $bodyEs, 'en' => $bodyEn],
                'status' => 'active',
            ]
        );
    }

    protected function reminders(int $orgId, int $programId): void
    {
        $sessionReminder = EmailTemplate::resolve($orgId, 'reminder_session', $programId);
        $registerReminder = EmailTemplate::resolve($orgId, 'reminder_register', $programId);

        Reminder::updateOrCreate(
            ['organization_id' => $orgId, 'program_id' => $programId, 'name' => 'Aviso 3 días antes de cada sesión'],
            [
                'email_template_id' => $sessionReminder?->id,
                'recipient_type' => 'both',
                'anchor' => 'session_start',
                'timing' => 'before',
                'days' => 3,
                'status' => 'active',
            ]
        );

        Reminder::updateOrCreate(
            ['organization_id' => $orgId, 'program_id' => $programId, 'name' => 'Recordatorio de registro 3 días antes del cierre'],
            [
                'email_template_id' => $registerReminder?->id,
                'recipient_type' => 'facilitator',
                'anchor' => 'session_end',
                'timing' => 'before',
                'days' => 3,
                'status' => 'active',
            ]
        );
    }
}
