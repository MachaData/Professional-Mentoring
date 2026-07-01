<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\User;
use App\Services\ResourceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourcesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_resolver_filters_tools_by_role_visibility(): void
    {
        $program = Program::firstOrFail();
        $resolver = app(ResourceResolver::class);

        // Workbook is "both", guide is "facilitator" only (both program-level).
        $this->assertSame(2, $resolver->programTools($program, 'facilitator')->count());
        $this->assertSame(1, $resolver->programTools($program, 'participant')->count());
    }

    public function test_session_tools_are_scoped_to_the_session(): void
    {
        $program = Program::firstOrFail();
        $resolver = app(ResourceResolver::class);
        $session1 = $program->sessions()->where('number', 1)->firstOrFail();
        $session2 = $program->sessions()->where('number', 2)->firstOrFail();

        // The workbook is attached to Session 1 only (demo).
        $this->assertSame(1, $resolver->sessionTools($session1, 'participant')->count());
        $this->assertSame(0, $resolver->sessionTools($session2, 'participant')->count());
    }

    public function test_surveys_visible_to_participant(): void
    {
        $program = Program::firstOrFail();
        $resolver = app(ResourceResolver::class);

        $this->assertGreaterThanOrEqual(1, $resolver->programSurveys($program, 'participant')->count());
    }

    public function test_admin_can_open_tool_and_survey_pages(): void
    {
        $u = User::where('email', 'superadmin@pro-mentoring.com')->firstOrFail();

        foreach ([
            '/admin/tools', '/admin/tools/create',
            '/admin/surveys', '/admin/surveys/create',
        ] as $url) {
            $this->actingAs($u)->get($url)->assertSuccessful();
        }
    }

    public function test_mentee_sees_workbook_but_not_mentor_guide(): void
    {
        $mentee = User::where('email', 'mentee@demo.test')->firstOrFail();

        $this->actingAs($mentee)->get('/me')
            ->assertSuccessful()
            ->assertSee('Workbook del Mentoring')
            ->assertDontSee('Guía del Mentor');
    }
}
