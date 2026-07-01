<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $session = \App\Models\Session::firstOrFail();
        $template = \App\Models\FormTemplate::firstOrFail();

        $this->actingAs($u)->get("/admin/sessions/{$session->getKey()}/edit")->assertSuccessful();
        $this->actingAs($u)->get("/admin/form-templates/{$template->getKey()}/edit")->assertSuccessful();
    }

    public function test_program_edit_with_relation_managers_renders(): void
    {
        $u = User::where('email', 'superadmin@pro-mentoring.com')->firstOrFail();
        $program = \App\Models\Program::firstOrFail();

        $this->actingAs($u)->get("/admin/programs/{$program->getKey()}/edit")->assertSuccessful();
    }

    public function test_las_bambas_program_seeded_with_four_stages_and_ten_sessions(): void
    {
        $program = \App\Models\Program::where('slug', 'professional-mentoring-las-bambas')->firstOrFail();

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
        $org = \App\Models\Organization::first();
        $facilitator = User::factory()->create([
            'organization_id' => $org->id,
            'role' => User::ROLE_FACILITATOR,
        ]);

        $this->actingAs($facilitator)->get('/admin')->assertForbidden();
    }
}
