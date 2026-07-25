<?php

namespace Tests\Feature;

use App\Filament\Resources\Assignments\Pages\ViewAssignment;
use App\Filament\Resources\Assignments\RelationManagers\ExtraSessionsRelationManager;
use App\Filament\Resources\Programs\Pages\EditProgram;
use App\Filament\Resources\Programs\RelationManagers\SessionsRelationManager;
use App\Models\Assignment;
use App\Models\FormTemplate;
use App\Models\Organization;
use App\Models\Program;
use App\Models\Session;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_superadmin_can_open_admin_pages(): void
    {
        $u = User::where('email', 'superadmin@pro-mentoring.com')->firstOrFail();

        foreach ([
            '/admin',
            '/admin/organizations', '/admin/organizations/create',
            '/admin/users', '/admin/users/create',
            '/admin/program-types', '/admin/program-types/create',
            '/admin/clients', '/admin/clients/create',
            '/admin/programs', '/admin/programs/create',
            '/admin/sessions', '/admin/sessions/create',
            '/admin/form-templates', '/admin/form-templates/create',
            '/admin/assignments', '/admin/assignments/create',
            '/admin/tools', '/admin/tools/create',
            '/admin/surveys', '/admin/surveys/create',
            '/admin/email-templates', '/admin/email-templates/create',
            '/admin/reminders', '/admin/reminders/create',
        ] as $url) {
            $this->actingAs($u)->get($url)->assertSuccessful();
        }
    }

    public function test_session_and_template_field_managers_render(): void
    {
        $u = User::where('email', 'superadmin@pro-mentoring.com')->firstOrFail();
        $session = Session::firstOrFail();
        $template = FormTemplate::firstOrFail();

        $this->actingAs($u)->get("/admin/sessions/{$session->getKey()}/edit")->assertSuccessful();
        $this->actingAs($u)->get("/admin/form-templates/{$template->getKey()}/edit")->assertSuccessful();
    }

    public function test_program_edit_with_relation_managers_renders(): void
    {
        $u = User::where('email', 'superadmin@pro-mentoring.com')->firstOrFail();
        $program = Program::firstOrFail();

        $this->actingAs($u)->get("/admin/programs/{$program->getKey()}/edit")->assertSuccessful();
    }

    public function test_assignment_view_with_relation_managers_renders(): void
    {
        $u = User::where('email', 'superadmin@pro-mentoring.com')->firstOrFail();
        $assignment = Assignment::firstOrFail();

        $this->actingAs($u)->get("/admin/assignments/{$assignment->getKey()}")->assertSuccessful();
        $this->actingAs($u)->get("/admin/assignments/{$assignment->getKey()}/edit")->assertSuccessful();
    }

    /** Relation-manager tables are lazy Livewire components — mount them directly. */
    public function test_extra_sessions_table_shows_the_lock_state(): void
    {
        $u = User::where('email', 'superadmin@pro-mentoring.com')->firstOrFail();
        $assignment = Assignment::firstOrFail();

        $assignment->extraSessions()->create([
            'organization_id' => $assignment->organization_id,
            'program_id' => $assignment->program_id,
            'name' => ['es' => 'Sesión extra', 'en' => 'Extra session'],
            'number' => 99,
            'sort_order' => 999,
            'is_locked' => true,
        ]);

        $this->actingAs($u);

        Livewire::test(
            ExtraSessionsRelationManager::class,
            [
                'ownerRecord' => $assignment,
                'pageClass' => ViewAssignment::class,
            ],
        )->assertSuccessful()->assertSee('Bloqueada');
    }

    public function test_program_sessions_table_shows_the_lock_state(): void
    {
        $u = User::where('email', 'superadmin@pro-mentoring.com')->firstOrFail();
        $program = Program::firstOrFail();
        $program->sessions()->firstOrFail()->update(['is_locked' => true]);

        $this->actingAs($u);

        Livewire::test(
            SessionsRelationManager::class,
            [
                'ownerRecord' => $program,
                'pageClass' => EditProgram::class,
            ],
        )->assertSuccessful()->assertSee('Bloqueada');
    }

    public function test_las_bambas_program_seeded_with_four_stages_and_ten_sessions(): void
    {
        $program = Program::where('slug', 'professional-mentoring-las-bambas')->firstOrFail();

        $this->assertSame(4, $program->stages()->count());
        $this->assertSame(10, $program->sessions()->count());
        $this->assertSame('2026-06-23', $program->start_date->toDateString());
        $this->assertSame('2027-04-21', $program->end_date->toDateString());
    }

    public function test_org_admin_cannot_open_organizations(): void
    {
        $u = User::where('email', 'admin@crosspartnersgroup.com')->firstOrFail();
        $this->actingAs($u)->get('/admin/organizations')->assertForbidden();
    }

    public function test_facilitator_cannot_access_admin_panel(): void
    {
        $org = Organization::first();
        $facilitator = User::factory()->create([
            'organization_id' => $org->id,
            'role' => User::ROLE_FACILITATOR,
        ]);

        $this->actingAs($facilitator)->get('/admin')->assertForbidden();
    }
}
