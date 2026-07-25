<?php

namespace Tests\Feature;

use App\Filament\Resources\Tools\Pages\ListTools;
use App\Models\Program;
use App\Models\Tool;
use App\Models\User;
use App\Services\ResourceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ResourcesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function mentor(): User
    {
        return User::where('email', 'mentor@demo.test')->firstOrFail();
    }

    protected function mentee(): User
    {
        return User::where('email', 'mentee@demo.test')->firstOrFail();
    }

    public function test_resolver_filters_tools_by_role_visibility(): void
    {
        $program = Program::firstOrFail();
        $resolver = app(ResourceResolver::class);

        // Workbook is "both", guide is "facilitator" only (both program-level).
        $this->assertSame(2, $resolver->programTools($program, $this->mentor())->count());
        $this->assertSame(1, $resolver->programTools($program, $this->mentee())->count());
    }

    public function test_session_tools_are_scoped_to_the_session(): void
    {
        $program = Program::firstOrFail();
        $resolver = app(ResourceResolver::class);
        $session1 = $program->sessions()->where('number', 1)->firstOrFail();
        $session2 = $program->sessions()->where('number', 2)->firstOrFail();

        // The workbook is attached to Session 1 only (demo).
        $this->assertSame(1, $resolver->sessionTools($session1, $this->mentee())->count());
        $this->assertSame(0, $resolver->sessionTools($session2, $this->mentee())->count());
    }

    // ---- General vs per-session materials ---------------------------------

    /**
     * Reproduces how the admin panel stores things: the "Asociaciones" repeater
     * puts Programa, Etapa and Sesión on one row, so a session material lands as
     * a single {program_id, session_id} row.
     */
    protected function material(string $name, array $relation, string $visibility = 'all'): Tool
    {
        $program = Program::firstOrFail();

        $tool = Tool::create([
            'organization_id' => $program->organization_id,
            'name' => ['es' => $name, 'en' => $name],
            'type' => 'pdf',
            'visibility' => $visibility,
            'status' => 'active',
            'external_url' => 'https://example.test/'.md5($name).'.pdf',
        ]);
        $tool->relations()->create($relation + ['program_id' => $program->id]);

        return $tool;
    }

    public function test_a_session_material_never_appears_in_the_general_materials_list(): void
    {
        $program = Program::firstOrFail();
        $session1 = $program->sessions()->where('number', 1)->firstOrFail();
        $session2 = $program->sessions()->where('number', 2)->firstOrFail();

        $ficha = $this->material('Ficha de trabajo Sesión 1', ['session_id' => $session1->id]);

        $resolver = app(ResourceResolver::class);
        $mentee = $this->mentee();

        $this->assertFalse(
            $resolver->programTools($program, $mentee)->contains('id', $ficha->id),
            'A material pinned to a session must stay out of the general list.',
        );
        $this->assertTrue($resolver->sessionTools($session1, $mentee)->contains('id', $ficha->id));
        $this->assertFalse($resolver->sessionTools($session2, $mentee)->contains('id', $ficha->id));
    }

    public function test_a_stage_material_is_not_general_but_shows_in_that_stages_sessions(): void
    {
        $program = Program::firstOrFail();
        $session = $program->sessions()->whereNotNull('stage_id')->firstOrFail();

        $recurso = $this->material('Recurso de etapa', ['stage_id' => $session->stage_id]);

        $resolver = app(ResourceResolver::class);
        $mentee = $this->mentee();

        $this->assertFalse($resolver->programTools($program, $mentee)->contains('id', $recurso->id));
        $this->assertTrue($resolver->sessionTools($session, $mentee)->contains('id', $recurso->id));
    }

    public function test_a_program_material_without_stage_or_session_stays_general(): void
    {
        $program = Program::firstOrFail();
        $session1 = $program->sessions()->where('number', 1)->firstOrFail();

        $guia = $this->material('Guía del mentee', []);

        $resolver = app(ResourceResolver::class);
        $mentee = $this->mentee();

        $this->assertTrue($resolver->programTools($program, $mentee)->contains('id', $guia->id));
        $this->assertFalse(
            $resolver->sessionTools($session1, $mentee)->contains('id', $guia->id),
            'General materials must not be duplicated inside every session.',
        );
    }

    public function test_a_material_with_two_rows_can_be_general_and_per_session_at_once(): void
    {
        $program = Program::firstOrFail();
        $session1 = $program->sessions()->where('number', 1)->firstOrFail();

        // The seeded Workbook: one program-only row plus one session-1 row.
        $workbook = Tool::where('category', 'workbook')->firstOrFail();

        $resolver = app(ResourceResolver::class);
        $mentee = $this->mentee();

        $this->assertTrue($resolver->programTools($program, $mentee)->contains('id', $workbook->id));
        $this->assertTrue($resolver->sessionTools($session1, $mentee)->contains('id', $workbook->id));
    }

    public function test_session_materials_respect_business_unit_and_role(): void
    {
        $program = Program::firstOrFail();
        $session1 = $program->sessions()->where('number', 1)->firstOrFail();
        $resolver = app(ResourceResolver::class);

        $mentorOnly = $this->material('Guía de sesión del mentor', ['session_id' => $session1->id], 'facilitator');

        $this->assertTrue($resolver->sessionTools($session1, $this->mentor())->contains('id', $mentorOnly->id));
        $this->assertFalse($resolver->sessionTools($session1, $this->mentee())->contains('id', $mentorOnly->id));

        $mentorOnly->update(['business_unit' => 'Minería']);
        $mentor = $this->mentor();

        $mentor->update(['business_unit' => 'Corporativo']);
        $this->assertFalse($resolver->sessionTools($session1, $mentor->fresh())->contains('id', $mentorOnly->id));

        $mentor->update(['business_unit' => 'Minería']);
        $this->assertTrue($resolver->sessionTools($session1, $mentor->fresh())->contains('id', $mentorOnly->id));
    }

    public function test_mentee_dashboard_keeps_session_materials_out_of_the_sidebar(): void
    {
        $program = Program::firstOrFail();
        $session1 = $program->sessions()->where('number', 1)->firstOrFail();
        $ficha = $this->material('Ficha de trabajo Sesión 1', ['session_id' => $session1->id]);

        $this->actingAs($this->mentee())->get('/me')
            ->assertSuccessful()
            ->assertViewHas('sidebarTools', fn ($tools) => ! $tools->contains('id', $ficha->id));
    }

    public function test_mentor_participant_view_keeps_session_materials_out_of_the_sidebar(): void
    {
        $mentor = $this->mentor();
        $program = Program::firstOrFail();
        $session1 = $program->sessions()->where('number', 1)->firstOrFail();
        $ficha = $this->material('Ficha de trabajo Sesión 1', ['session_id' => $session1->id]);
        $assignment = $mentor->assignmentsAsFacilitator()->firstOrFail();

        $this->actingAs($mentor)->get(route('mentor.participant', $assignment))
            ->assertSuccessful()
            ->assertViewHas('sidebarTools', fn ($tools) => ! $tools->contains('id', $ficha->id));
    }

    public function test_session_detail_shows_the_materials_of_that_session(): void
    {
        $program = Program::firstOrFail();
        $session1 = $program->sessions()->where('number', 1)->firstOrFail();
        $this->material('Ficha de trabajo Sesión 1', ['session_id' => $session1->id]);

        $this->actingAs($this->mentee())->get(route('participant.session', $session1))
            ->assertSuccessful()
            ->assertSee('Ficha de trabajo Sesión 1');
    }

    public function test_surveys_visible_to_participant(): void
    {
        $program = Program::firstOrFail();
        $resolver = app(ResourceResolver::class);

        $this->assertGreaterThanOrEqual(1, $resolver->programSurveys($program, $this->mentee())->count());
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

    // ---- Language --------------------------------------------------------

    public function test_language_labels_a_material_but_never_hides_it(): void
    {
        $program = Program::firstOrFail();
        $mentee = $this->mentee();
        $mentee->update(['locale' => 'es']);

        $english = $this->material('Reflection worksheet', []);
        $english->update(['language' => 'en']);

        $this->assertTrue(
            app(ResourceResolver::class)->programTools($program, $mentee->fresh())->contains('id', $english->id),
            'A material in another language must stay reachable, only labelled.',
        );
    }

    public function test_materials_in_the_users_language_come_first(): void
    {
        $program = Program::firstOrFail();
        $mentee = $this->mentee();
        $mentee->update(['locale' => 'en']);

        // Same category so ordering inside the group is what is being checked;
        // the English one is created last, so id order alone would put it second.
        $spanish = $this->material('Guía en español', []);
        $spanish->update(['language' => 'es', 'category' => 'guias']);
        $english = $this->material('Guide in English', []);
        $english->update(['language' => 'en', 'category' => 'guias']);

        $ordered = app(ResourceResolver::class)->programTools($program, $mentee->fresh())
            ->whereIn('id', [$spanish->id, $english->id])
            ->pluck('id')
            ->values()
            ->all();

        $this->assertSame([$english->id, $spanish->id], $ordered);
    }

    public function test_session_detail_shows_the_language_of_a_material(): void
    {
        $program = Program::firstOrFail();
        $session1 = $program->sessions()->where('number', 1)->firstOrFail();

        $tool = $this->material('Worksheet', ['session_id' => $session1->id]);
        $tool->update(['language' => 'en']);

        $this->actingAs($this->mentee())->get(route('participant.session', $session1))
            ->assertSuccessful()
            ->assertSee('English');
    }

    public function test_general_materials_offer_a_language_filter_when_languages_are_mixed(): void
    {
        $mentee = $this->mentee();

        // The seeded workbook has no language; adding one in English gives the
        // sidebar something to filter between.
        $this->material('Guide in English', [])->update(['language' => 'en']);

        $this->actingAs($mentee)->get('/me')
            ->assertSuccessful()
            ->assertSee('data-lang-filter="en"', false);
    }

    public function test_admin_can_filter_materials_by_language_and_placement(): void
    {
        $program = Program::firstOrFail();
        $session1 = $program->sessions()->where('number', 1)->firstOrFail();

        $general = $this->material('Manual general', []);
        $general->update(['language' => 'es']);
        $ofSession = $this->material('Ficha de la sesión', ['session_id' => $session1->id]);
        $ofSession->update(['language' => 'en']);

        $admin = User::where('email', 'superadmin@pro-mentoring.com')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(ListTools::class)
            ->filterTable('language', 'en')
            ->assertCanSeeTableRecords([$ofSession])
            ->assertCanNotSeeTableRecords([$general])
            ->removeTableFilter('language')
            ->filterTable('placement', 'general')
            ->assertCanSeeTableRecords([$general])
            ->assertCanNotSeeTableRecords([$ofSession]);
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
