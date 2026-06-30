<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Organization;
use App\Models\Program;
use App\Models\ProgramType;
use App\Models\Session;
use App\Models\Stage;
use Illuminate\Database\Seeder;

/**
 * Initial configuration of the "Professional Mentoring — Las Bambas" program:
 * 4 stages and 10 sessions with the real dates (Jun 2026 → Apr 2027).
 *
 * Everything here is fully editable from the admin panel afterwards — the
 * number of stages/sessions, names, dates and objectives are NOT hard-coded
 * into the application logic.
 */
class LasBambasProgramSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::where('slug', 'crosspartners-group')->first();
        if (! $org) {
            return;
        }

        $client = Client::where('organization_id', $org->id)->where('slug', 'las-bambas')->first();
        $type = ProgramType::where('organization_id', $org->id)
            ->whereRaw("name->>'es' = ?", ['Mentoring'])->first();

        $program = Program::withTrashed()
            ->where('organization_id', $org->id)
            ->where('slug', 'professional-mentoring-las-bambas')
            ->first() ?? new Program;

        $program->fill([
            'organization_id' => $org->id,
            'client_id' => $client?->id,
            'program_type_id' => $type?->id,
            'name' => [
                'es' => 'Professional Mentoring Las Bambas',
                'en' => 'Professional Mentoring Las Bambas',
            ],
            'slug' => 'professional-mentoring-las-bambas',
            'description' => [
                'es' => 'Programa de mentoring para Las Bambas con metodología EPIC.',
                'en' => 'Mentoring program for Las Bambas using the EPIC methodology.',
            ],
            'facilitator_label' => ['es' => 'Mentor', 'en' => 'Mentor'],
            'participant_label' => ['es' => 'Mentee', 'en' => 'Mentee'],
            'start_date' => '2026-06-23',
            'end_date' => '2027-04-21',
            'default_locale' => 'es',
            'primary_color' => '#D2232A',
            'secondary_color' => '#00843D',
            'status' => 'active',
        ]);
        $program->save();

        $stages = $this->seedStages($org->id, $program->id);
        $this->seedSessions($org->id, $program->id, $stages);
    }

    /** @return array<string,int> stage key => id */
    protected function seedStages(int $orgId, int $programId): array
    {
        $definitions = [
            'exploration' => ['es' => 'Exploración', 'en' => 'Exploration', 'color' => '#E30613'],
            'planning' => ['es' => 'Planeamiento', 'en' => 'Planning', 'color' => '#6B7280'],
            'implementation' => ['es' => 'Implementación', 'en' => 'Implementation', 'color' => '#E30613'],
            'closing' => ['es' => 'Cierre', 'en' => 'Closing', 'color' => '#6B7280'],
        ];

        $map = [];
        $order = 1;
        foreach ($definitions as $key => $def) {
            $stage = Stage::where('program_id', $programId)
                ->whereRaw("name->>'es' = ?", [$def['es']])->first() ?? new Stage;

            $stage->fill([
                'organization_id' => $orgId,
                'program_id' => $programId,
                'name' => ['es' => $def['es'], 'en' => $def['en']],
                'color' => $def['color'],
                'sort_order' => $order++,
                'status' => 'active',
            ]);
            $stage->save();
            $map[$key] = $stage->id;
        }

        return $map;
    }

    /** @param array<string,int> $stages */
    protected function seedSessions(int $orgId, int $programId, array $stages): void
    {
        // [number, stage key, start, end, objective es, objective en]
        $sessions = [
            [1, 'exploration', '2026-06-23', '2026-07-21',
                'Crear un vínculo sólido y de confianza entre mentor y mentee.',
                'Create a strong and understanding bond between the mentor and the mentee.'],
            [2, 'exploration', '2026-07-22', '2026-08-21',
                'Establecer empatía y explorar aspectos clave de la vida del mentee.',
                "Establish empathy and explore key aspects of the mentee's life."],
            [3, 'planning', '2026-08-22', '2026-09-21',
                'Autoconocimiento.',
                'Self-awareness.'],
            [4, 'planning', '2026-09-22', '2026-10-21',
                'Definir objetivos medibles e inspiradores.',
                'Set measurable objectives and inspiring benchmarks.'],
            [5, 'planning', '2026-10-22', '2026-11-21',
                'Crear un plan claro y estructurado para alcanzar objetivos.',
                'Create a clear and structured plan to achieve goals.'],
            [6, 'implementation', '2026-11-22', '2026-12-21',
                'Brindar herramientas prácticas de organización.',
                'Provide the mentee with practical organizational tools.'],
            [7, 'implementation', '2026-12-22', '2027-01-21',
                'Fomentar la creación de hábitos sostenibles.',
                'Encourage the creation of sustainable habits.'],
            [8, 'implementation', '2027-01-22', '2027-02-21',
                'Evaluar el progreso del mentee.',
                "Evaluate the mentee's progress regarding their objectives and habits."],
            [9, 'implementation', '2027-02-22', '2027-03-21',
                'Revisar estrategias para superar obstáculos.',
                'Review strategies to overcome identified obstacles along the way.'],
            [10, 'closing', '2027-03-22', '2027-04-21',
                'Evaluar el progreso logrado.',
                'Evaluate the progress achieved.'],
        ];

        foreach ($sessions as [$number, $stageKey, $start, $end, $objEs, $objEn]) {
            $session = Session::where('program_id', $programId)
                ->where('number', $number)->first() ?? new Session;

            $session->fill([
                'organization_id' => $orgId,
                'program_id' => $programId,
                'stage_id' => $stages[$stageKey] ?? null,
                'number' => $number,
                'name' => ['es' => "Sesión {$number}", 'en' => "Session {$number}"],
                'objective' => ['es' => $objEs, 'en' => $objEn],
                'start_date' => $start,
                'end_date' => $end,
                'sort_order' => $number,
                'requires_registration' => true,
                'visible_to_participant' => true,
                'status' => 'active',
            ]);
            $session->save();
        }
    }
}
