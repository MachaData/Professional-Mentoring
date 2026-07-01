<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Program;
use App\Models\Survey;
use App\Models\Tool;
use Illuminate\Database\Seeder;

/**
 * Initial reusable resources for the Las Bambas program (editable in the panel):
 *  - the shared Workbook (Google Sheet) and Mentor Guide (Drive)
 *  - the satisfaction survey (Google Form)
 */
class ResourcesSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::where('slug', 'crosspartners-group')->first();
        $program = Program::where('slug', 'professional-mentoring-las-bambas')->first();
        if (! $org || ! $program) {
            return;
        }

        $workbook = Tool::updateOrCreate(
            ['organization_id' => $org->id, 'type' => 'google_sheet', 'category' => 'workbook'],
            [
                'name' => ['es' => 'Workbook del Mentoring', 'en' => 'Mentoring Workbook'],
                'description' => ['es' => 'Hoja de trabajo con las actividades de cada sesión.', 'en' => 'Worksheet with each session activity.'],
                'external_url' => 'https://docs.google.com/spreadsheets/d/1m119ers1oyYQ5Hwa8P9Lp89cXfOEu6ef/edit',
                'visibility' => 'both',
                'status' => 'active',
            ]
        );
        $this->attach($workbook, $program->id);

        $guide = Tool::updateOrCreate(
            ['organization_id' => $org->id, 'type' => 'guide', 'category' => 'mentor_guide'],
            [
                'name' => ['es' => 'Guía del Mentor', 'en' => 'Mentor Guide'],
                'description' => ['es' => 'Guía metodológica para el mentor.', 'en' => 'Methodological guide for the mentor.'],
                'external_url' => 'https://drive.google.com/drive/folders/1OSlR3eROt-z_u66h1IkE8cla1Ua-7ciT',
                'visibility' => 'facilitator',
                'status' => 'active',
            ]
        );
        $this->attach($guide, $program->id);

        $survey = Survey::updateOrCreate(
            ['organization_id' => $org->id, 'program_id' => $program->id, 'external_url' => 'https://forms.gle/KRJzeGAvUAvaaomD6'],
            [
                'name' => ['es' => 'Encuesta de satisfacción', 'en' => 'Satisfaction survey'],
                'description' => ['es' => 'Encuesta de satisfacción del mentee tras cada sesión.', 'en' => 'Mentee satisfaction survey after each session.'],
                'visible_to' => 'participant',
                'display_moment' => 'after_session',
                'status' => 'active',
            ]
        );
    }

    protected function attach(Tool $tool, int $programId): void
    {
        $tool->relations()->firstOrCreate(['program_id' => $programId]);
    }
}
