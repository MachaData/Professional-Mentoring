<?php

namespace Database\Seeders;

use App\Enums\FieldType;
use App\Models\CustomField;
use App\Models\FormTemplate;
use App\Models\Organization;
use App\Models\Program;
use Illuminate\Database\Seeder;

/**
 * Seeds the base session-registration form for the Las Bambas program:
 *  - a reusable form template "Registro básico de mentoring"
 *  - the same base fields attached to each of the program's sessions
 *
 * These are just the initial fields — admins add/edit/remove fields per session
 * from the panel; nothing here is hard-coded into the app logic.
 */
class MentoringFieldsSeeder extends Seeder
{
    /**
     * @return array<int,array{name:string,type:FieldType,es:string,en:string,required:bool,visible:bool,internal:bool,options?:array}>
     */
    protected function baseFields(): array
    {
        return [
            ['name' => 'real_session_date', 'type' => FieldType::Date, 'es' => 'Fecha real de la sesión', 'en' => 'Actual session date', 'required' => true, 'visible' => true, 'internal' => false],
            ['name' => 'session_status', 'type' => FieldType::Select, 'es' => 'Estado de la sesión', 'en' => 'Session status', 'required' => true, 'visible' => true, 'internal' => false,
                'options' => ['realizada' => 'Realizada', 'reprogramada' => 'Reprogramada', 'pendiente' => 'Pendiente']],
            ['name' => 'topic', 'type' => FieldType::Text, 'es' => 'Tema trabajado', 'en' => 'Topic worked on', 'required' => false, 'visible' => true, 'internal' => false],
            ['name' => 'summary', 'type' => FieldType::Textarea, 'es' => 'Resumen de la sesión', 'en' => 'Session summary', 'required' => false, 'visible' => true, 'internal' => false],
            ['name' => 'agreements', 'type' => FieldType::Textarea, 'es' => 'Acuerdos principales', 'en' => 'Key agreements', 'required' => false, 'visible' => true, 'internal' => false],
            ['name' => 'mentee_commitments', 'type' => FieldType::Textarea, 'es' => 'Compromisos del mentee', 'en' => "Mentee's commitments", 'required' => false, 'visible' => true, 'internal' => false],
            ['name' => 'next_steps', 'type' => FieldType::Textarea, 'es' => 'Próximos pasos', 'en' => 'Next steps', 'required' => false, 'visible' => true, 'internal' => false],
            ['name' => 'mentor_comments', 'type' => FieldType::Textarea, 'es' => 'Comentarios del mentor', 'en' => "Mentor's comments", 'required' => false, 'visible' => false, 'internal' => true],
            ['name' => 'alerts', 'type' => FieldType::Textarea, 'es' => 'Alertas o dificultades', 'en' => 'Alerts or difficulties', 'required' => false, 'visible' => false, 'internal' => true],
        ];
    }

    public function run(): void
    {
        $org = Organization::where('slug', 'crosspartners-group')->first();
        if (! $org) {
            return;
        }

        $program = Program::where('organization_id', $org->id)
            ->where('slug', 'professional-mentoring-las-bambas')->first();
        if (! $program) {
            return;
        }

        $this->seedTemplate($org->id, $program->id);
        $this->seedSessionFields($org->id, $program);
    }

    protected function seedTemplate(int $orgId, int $programId): void
    {
        $template = FormTemplate::firstOrCreate(
            ['organization_id' => $orgId, 'name' => 'Registro básico de mentoring'],
            ['program_id' => $programId, 'description' => 'Campos base para registrar el avance de una sesión.', 'status' => 'active'],
        );

        $order = 1;
        foreach ($this->baseFields() as $def) {
            CustomField::updateOrCreate(
                ['form_template_id' => $template->id, 'name' => $def['name']],
                $this->attributes($orgId, $def, $order++) + ['form_template_id' => $template->id],
            );
        }
    }

    protected function seedSessionFields(int $orgId, Program $program): void
    {
        foreach ($program->sessions as $session) {
            $order = 1;
            foreach ($this->baseFields() as $def) {
                CustomField::updateOrCreate(
                    ['session_id' => $session->id, 'name' => $def['name']],
                    $this->attributes($orgId, $def, $order++) + [
                        'session_id' => $session->id,
                        'program_id' => $program->id,
                    ],
                );
            }
        }
    }

    /** @return array<string,mixed> */
    protected function attributes(int $orgId, array $def, int $order): array
    {
        return [
            'organization_id' => $orgId,
            'label' => ['es' => $def['es'], 'en' => $def['en']],
            'name' => $def['name'],
            'field_type' => $def['type']->value,
            'options_json' => $def['options'] ?? null,
            'is_required' => $def['required'],
            'is_visible_to_participant' => $def['visible'],
            'is_internal' => $def['internal'],
            'sort_order' => $order,
            'status' => 'active',
        ];
    }
}
