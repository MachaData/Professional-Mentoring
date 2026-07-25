<?php

namespace Tests\Feature;

use App\Filament\Resources\Programs\Pages\EditProgram;
use App\Filament\Resources\Programs\RelationManagers\StagesRelationManager;
use App\Filament\Resources\Sessions\Pages\ListSessions;
use App\Livewire\Mentor\RegisterSession;
use App\Livewire\PrivateFiles;
use App\Models\Program;
use App\Models\Session;
use App\Models\SessionRecord;
use App\Models\Tool;
use App\Models\User;
use App\Services\ReportService;
use App\Services\ResourceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Progressive access: sessions can be hidden per role, and locked sessions show
 * as "Próximamente" until unlocked manually or by their unlock_at date.
 * Materials pinned to a business unit only reach that unit's users.
 */
class SessionVisibilityTest extends TestCase
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

    protected function curriculumSession(int $number = 1): Session
    {
        return Program::firstOrFail()->sessions()->where('number', $number)->firstOrFail();
    }

    /**
     * Seeded sessions are named "Sesión 1".."Sesión 12", so asserting on those
     * names matches sibling sessions by prefix. Give the one under test a name
     * nothing else shares.
     */
    protected function named(Session $session, string $name = 'Sesión Bajo Prueba'): string
    {
        $session->update(['name' => ['es' => $name, 'en' => $name]]);

        return $name;
    }

    /** Session ids share prefixes (1 vs 10), so match the whole href attribute. */
    protected function hrefTo(string $url): string
    {
        return 'href="'.$url.'"';
    }

    // ---- Lock semantics -------------------------------------------------

    public function test_a_session_is_not_locked_unless_the_flag_is_set(): void
    {
        $session = $this->curriculumSession();

        $this->assertFalse($session->isLocked());
    }

    public function test_a_locked_session_without_an_unlock_date_stays_locked(): void
    {
        $session = $this->curriculumSession();
        $session->update(['is_locked' => true, 'unlock_at' => null]);

        $this->assertTrue($session->fresh()->isLocked());
    }

    public function test_a_locked_session_unlocks_itself_once_the_date_arrives(): void
    {
        $session = $this->curriculumSession();

        $session->update(['is_locked' => true, 'unlock_at' => now()->addDay()]);
        $this->assertTrue($session->fresh()->isLocked(), 'A future unlock date keeps it locked.');

        $session->update(['unlock_at' => now()]);
        $this->assertFalse($session->fresh()->isLocked(), 'It opens on the unlock date itself.');

        $session->update(['unlock_at' => now()->subDay()]);
        $this->assertFalse($session->fresh()->isLocked(), 'And stays open afterwards.');
    }

    public function test_a_locked_but_visible_session_is_coming_soon(): void
    {
        $session = $this->curriculumSession();
        $session->update(['is_locked' => true, 'visible_to_participant' => true, 'visible_to_facilitator' => false]);
        $session->refresh();

        $this->assertTrue($session->isComingSoonFor(User::ROLE_PARTICIPANT));
        $this->assertFalse($session->isComingSoonFor(User::ROLE_FACILITATOR), 'Hidden beats locked — there is nothing to tease.');
    }

    // ---- Mentee portal ---------------------------------------------------

    public function test_mentee_sees_a_locked_session_as_coming_soon_and_cannot_open_it(): void
    {
        $session = $this->curriculumSession();
        $name = $this->named($session);
        $session->update(['is_locked' => true, 'visible_to_participant' => true]);

        $this->actingAs($this->mentee())->get('/me')
            ->assertSuccessful()
            ->assertSee($name)
            ->assertSee('Próximamente')
            ->assertDontSee($this->hrefTo(route('participant.session', $session)), false);

        $this->actingAs($this->mentee())->get(route('participant.session', $session))->assertForbidden();
    }

    public function test_mentee_can_open_a_locked_session_once_its_unlock_date_has_passed(): void
    {
        $session = $this->curriculumSession();
        $session->update(['is_locked' => true, 'unlock_at' => now()->subDay()]);

        $this->actingAs($this->mentee())->get(route('participant.session', $session))->assertSuccessful();
    }

    public function test_mentee_never_sees_a_session_hidden_from_them(): void
    {
        $session = $this->curriculumSession();
        $name = $this->named($session);
        $session->update(['visible_to_participant' => false]);

        $this->actingAs($this->mentee())->get('/me')
            ->assertSuccessful()
            ->assertDontSee($name);

        $this->actingAs($this->mentee())->get(route('participant.session', $session))->assertForbidden();
    }

    public function test_locked_sessions_hide_their_meeting_link_from_the_mentee(): void
    {
        // Session 1 carries a demo meeting URL on its record.
        $session = $this->curriculumSession();
        $meetingUrl = 'https://meet.google.com/abc-defg-hij';

        $this->actingAs($this->mentee())->get('/me')->assertSee($meetingUrl);

        $session->update(['is_locked' => true]);

        $this->actingAs($this->mentee())->get('/me')->assertDontSee($meetingUrl);
    }

    // ---- Mentor portal ---------------------------------------------------

    public function test_mentor_cannot_register_a_locked_session(): void
    {
        $mentor = $this->mentor();
        $session = $this->curriculumSession();
        $record = $session->records()->where('facilitator_id', $mentor->id)->firstOrFail();

        $this->actingAs($mentor)->get(route('mentor.register', $record))->assertSuccessful();

        $session->update(['is_locked' => true]);

        $this->actingAs($mentor)->get(route('mentor.register', $record))->assertForbidden();
    }

    public function test_mentor_cannot_register_a_session_hidden_from_mentors(): void
    {
        $mentor = $this->mentor();
        $session = $this->curriculumSession();
        $session->update(['visible_to_facilitator' => false]);
        $record = $session->records()->where('facilitator_id', $mentor->id)->firstOrFail();

        $this->actingAs($mentor)->get(route('mentor.register', $record))->assertForbidden();
    }

    public function test_mentor_sees_a_locked_session_as_coming_soon(): void
    {
        $mentor = $this->mentor();
        $session = $this->curriculumSession();
        $name = $this->named($session);
        $session->update(['is_locked' => true]);
        $assignment = $mentor->assignmentsAsFacilitator()->firstOrFail();
        $record = $session->records()->where('facilitator_id', $mentor->id)->firstOrFail();

        $this->actingAs($mentor)->get(route('mentor.participant', $assignment))
            ->assertSuccessful()
            ->assertSee($name)
            ->assertSee('Próximamente')
            ->assertDontSee($this->hrefTo(route('mentor.register', $record)), false);
    }

    public function test_mentor_does_not_see_a_session_hidden_from_mentors(): void
    {
        $mentor = $this->mentor();
        $session = $this->curriculumSession();
        $name = $this->named($session);
        $session->update(['visible_to_facilitator' => false]);
        $assignment = $mentor->assignmentsAsFacilitator()->firstOrFail();

        $this->actingAs($mentor)->get(route('mentor.participant', $assignment))
            ->assertSuccessful()
            ->assertDontSee($name);
    }

    // ---- Locked sessions are not writable either --------------------------

    public function test_mentor_cannot_submit_a_locked_session_through_livewire(): void
    {
        $mentor = $this->mentor();
        $session = $this->curriculumSession();
        $record = $session->records()->where('facilitator_id', $mentor->id)->firstOrFail();

        $this->actingAs($mentor);

        // Mount while open, then the admin locks it mid-edit.
        $component = Livewire::test(RegisterSession::class, ['record' => $record]);
        $session->update(['is_locked' => true]);

        $component->set('attendance', 'attended')->call('saveDraft')->assertForbidden();

        $this->assertSame(
            SessionRecord::STATUS_PENDING,
            $record->fresh()->status,
            'A locked session must not accept a draft.',
        );
    }

    public function test_mounting_the_register_component_for_a_locked_session_is_forbidden(): void
    {
        $mentor = $this->mentor();
        $session = $this->curriculumSession();
        $session->update(['is_locked' => true]);
        $record = $session->records()->where('facilitator_id', $mentor->id)->firstOrFail();

        $this->actingAs($mentor);

        Livewire::test(RegisterSession::class, ['record' => $record])
            ->assertForbidden();
    }

    public function test_shared_files_picker_hides_locked_and_hidden_sessions(): void
    {
        $mentee = $this->mentee();
        $assignment = $mentee->assignmentsAsParticipant()->firstOrFail();

        $locked = $this->curriculumSession(1);
        $locked->update(['is_locked' => true]);
        $hidden = $this->curriculumSession(2);
        $hidden->update(['visible_to_participant' => false]);
        $open = $this->curriculumSession(3);

        $this->actingAs($mentee);

        $ids = Livewire::test(PrivateFiles::class, ['assignment' => $assignment])
            ->instance()->sessions->pluck('id');

        $this->assertFalse($ids->contains($locked->id));
        $this->assertFalse($ids->contains($hidden->id));
        $this->assertTrue($ids->contains($open->id));
    }

    // ---- Calendars -------------------------------------------------------

    public function test_participant_calendar_treats_a_locked_session_as_pending_not_expired(): void
    {
        $session = $this->curriculumSession();
        $session->update([
            'is_locked' => true,
            'start_date' => now()->subMonth(),
            'end_date' => now()->subWeek(),
        ]);

        $this->actingAs($this->mentee())->get(route('participant.calendar'))
            ->assertSuccessful()
            ->assertViewHas('stats', fn (array $stats) => $stats['expired'] === 0);
    }

    public function test_participant_calendar_never_highlights_a_locked_session_as_next(): void
    {
        // Lock every session: there is then no openable "next" to link to.
        Session::withoutGlobalScopes()->whereNull('assignment_id')->update(['is_locked' => true]);

        $this->actingAs($this->mentee())->get(route('participant.calendar'))
            ->assertSuccessful()
            ->assertViewHas('next', null);
    }

    public function test_mentor_calendar_does_not_mark_an_all_locked_dupla_as_finished(): void
    {
        Session::withoutGlobalScopes()->whereNull('assignment_id')->update(['is_locked' => true]);

        $this->actingAs($this->mentor())->get(route('mentor.calendar'))
            ->assertSuccessful()
            ->assertViewHas('rows', fn ($rows) => $rows->every(fn ($row) => $row['state'] !== 'done'));
    }

    public function test_mentor_progress_bar_still_counts_locked_sessions_as_work_ahead(): void
    {
        $mentor = $this->mentor();
        $assignment = $mentor->assignmentsAsFacilitator()->firstOrFail();
        $total = $assignment->allSessions()->count();

        $this->curriculumSession(1)->update(['is_locked' => true]);

        $this->actingAs($mentor)->get('/mentor')
            ->assertSuccessful()
            ->assertViewHas('progress', fn (array $progress) => $progress[$assignment->id]['total'] === $total);
    }

    // ---- Manual unlock from the admin panel -------------------------------

    public function test_admin_can_unlock_a_session_from_the_sessions_table(): void
    {
        $session = $this->curriculumSession();
        $session->update(['is_locked' => true, 'unlock_at' => now()->addYear()]);

        $this->actingAs(User::where('email', 'superadmin@pro-mentoring.com')->firstOrFail());

        Livewire::test(ListSessions::class)
            ->callTableAction('toggleLock', $session)
            ->assertHasNoActionErrors();

        $session->refresh();
        $this->assertFalse($session->is_locked);
        $this->assertNull($session->unlock_at, 'Unlocking clears the stale unlock date.');
        $this->assertFalse($session->isLocked());
    }

    public function test_admin_can_lock_a_session_from_the_sessions_table(): void
    {
        $session = $this->curriculumSession();

        $this->actingAs(User::where('email', 'superadmin@pro-mentoring.com')->firstOrFail());

        Livewire::test(ListSessions::class)
            ->callTableAction('toggleLock', $session);

        $this->assertTrue($session->fresh()->isLocked());
    }

    // ---- Session activation ----------------------------------------------

    public function test_a_deactivated_session_is_hidden_from_mentor_and_mentee(): void
    {
        $session = $this->curriculumSession();
        $session->update(['status' => 'inactive']);
        $session->refresh();

        $this->assertTrue($session->isDeactivated());
        $this->assertFalse($session->isVisibleTo(User::ROLE_PARTICIPANT));
        $this->assertFalse($session->isVisibleTo(User::ROLE_FACILITATOR));
    }

    public function test_a_finished_session_stays_visible(): void
    {
        $session = $this->curriculumSession();
        $session->update(['status' => 'finished']);
        $session->refresh();

        $this->assertFalse($session->isDeactivated(), 'Finalizada no es lo mismo que inactiva.');
        $this->assertTrue($session->isVisibleTo(User::ROLE_PARTICIPANT));
    }

    public function test_mentee_never_sees_a_deactivated_session(): void
    {
        $session = $this->curriculumSession();
        $name = $this->named($session);
        $session->update(['status' => 'inactive']);

        $this->actingAs($this->mentee())->get('/me')
            ->assertSuccessful()
            ->assertDontSee($name);

        $this->actingAs($this->mentee())->get(route('participant.session', $session))->assertForbidden();
    }

    public function test_mentor_never_sees_a_deactivated_session(): void
    {
        $mentor = $this->mentor();
        $session = $this->curriculumSession();
        $name = $this->named($session);
        $session->update(['status' => 'inactive']);
        $assignment = $mentor->assignmentsAsFacilitator()->firstOrFail();
        $record = $session->records()->where('facilitator_id', $mentor->id)->firstOrFail();

        $this->actingAs($mentor)->get(route('mentor.participant', $assignment))
            ->assertSuccessful()
            ->assertDontSee($name);

        $this->actingAs($mentor)->get(route('mentor.register', $record))->assertForbidden();
    }

    public function test_reports_hide_deactivated_sessions_from_the_coordinator_but_not_the_admin(): void
    {
        $session = $this->curriculumSession();
        $session->update(['status' => 'inactive']);

        $this->assertFalse(
            ReportService::forUser($this->coordinator())
                ->progressBySession()->pluck('session.id')->contains($session->id),
        );
        $this->assertTrue(
            ReportService::forUser($this->admin())
                ->progressBySession()->pluck('session.id')->contains($session->id),
        );
    }

    public function test_admin_can_deactivate_and_reactivate_a_session_from_the_table(): void
    {
        $session = $this->curriculumSession();

        $this->actingAs(User::where('email', 'superadmin@pro-mentoring.com')->firstOrFail());

        Livewire::test(ListSessions::class)
            ->callTableAction('toggleStatus', $session)
            ->assertHasNoActionErrors();
        $this->assertSame('inactive', $session->fresh()->status);

        Livewire::test(ListSessions::class)->callTableAction('toggleStatus', $session->fresh());
        $this->assertSame('active', $session->fresh()->status, 'Reactivar no destruye nada.');
    }

    // ---- A locked session leaks neither materials nor survey ---------------

    public function test_a_locked_session_exposes_no_materials_or_survey_to_the_mentee(): void
    {
        $session = $this->curriculumSession();
        $session->update(['is_locked' => true, 'survey_url' => 'https://forms.gle/secreta']);

        $this->actingAs($this->mentee())->get('/me')
            ->assertSuccessful()
            ->assertDontSee('https://forms.gle/secreta')
            ->assertViewHas('sessions', function ($sessions) use ($session) {
                $row = $sessions->firstWhere('session.id', $session->id);

                return $row['locked'] === true
                    && $row['tools']->isEmpty()
                    && $row['survey_url'] === null
                    && $row['meeting_url'] === null;
            });
    }

    public function test_a_locked_session_exposes_no_materials_or_survey_to_the_mentor(): void
    {
        $mentor = $this->mentor();
        $session = $this->curriculumSession();
        $session->update(['is_locked' => true, 'survey_url' => 'https://forms.gle/secreta']);
        $assignment = $mentor->assignmentsAsFacilitator()->firstOrFail();

        $this->actingAs($mentor)->get(route('mentor.participant', $assignment))
            ->assertSuccessful()
            ->assertDontSee('https://forms.gle/secreta')
            ->assertViewHas('sessions', function ($sessions) use ($session) {
                $row = $sessions->firstWhere('session.id', $session->id);

                return $row['locked'] === true
                    && $row['tools']->isEmpty()
                    && $row['survey_url'] === null;
            });
    }

    // ---- Ordering ---------------------------------------------------------

    public function test_portal_sessions_follow_program_order_not_id_or_dates(): void
    {
        $mentee = $this->mentee();
        $assignment = $mentee->assignmentsAsParticipant()->firstOrFail();

        // Invert the calendar: the first session gets the latest dates. Order
        // must still follow sort_order.
        $first = $this->curriculumSession(1);
        $first->update(['start_date' => now()->addYear(), 'end_date' => now()->addYear()->addWeek()]);

        $ordered = $assignment->allSessions()->pluck('sort_order')->all();
        $this->assertSame($ordered, collect($ordered)->sort()->values()->all());

        $this->actingAs($mentee)->get('/me')
            ->assertSuccessful()
            ->assertViewHas('sessions', function ($sessions) {
                $orders = $sessions->pluck('session.sort_order')->all();

                return $orders === collect($orders)->sort()->values()->all();
            });
    }

    // ---- Stage activation -------------------------------------------------

    protected function admin(): User
    {
        return User::where('email', 'admin@crosspartnersgroup.com')->firstOrFail();
    }

    protected function coordinator(): User
    {
        return User::where('email', 'coordinador@demo.test')->firstOrFail();
    }

    /** Session 1 belongs to a seeded stage; deactivating it hides the session. */
    protected function deactivateStageOf(Session $session): void
    {
        $stage = $session->stage()->firstOrFail();
        $stage->update(['status' => 'inactive']);
    }

    public function test_a_session_on_an_inactive_stage_is_hidden_from_mentor_and_mentee(): void
    {
        $session = $this->curriculumSession();
        $this->deactivateStageOf($session);
        $session->refresh()->load('stage');

        $this->assertFalse($session->isVisibleTo(User::ROLE_PARTICIPANT));
        $this->assertFalse($session->isVisibleTo(User::ROLE_FACILITATOR));
    }

    public function test_mentee_never_sees_a_session_on_a_deactivated_stage(): void
    {
        $session = $this->curriculumSession();
        $name = $this->named($session);
        $this->deactivateStageOf($session);

        $this->actingAs($this->mentee())->get('/me')
            ->assertSuccessful()
            ->assertDontSee($name);

        $this->actingAs($this->mentee())->get(route('participant.session', $session))->assertForbidden();
    }

    public function test_mentor_never_sees_a_session_on_a_deactivated_stage(): void
    {
        $mentor = $this->mentor();
        $session = $this->curriculumSession();
        $name = $this->named($session);
        $this->deactivateStageOf($session);
        $assignment = $mentor->assignmentsAsFacilitator()->firstOrFail();
        $record = $session->records()->where('facilitator_id', $mentor->id)->firstOrFail();

        $this->actingAs($mentor)->get(route('mentor.participant', $assignment))
            ->assertSuccessful()
            ->assertDontSee($name);

        $this->actingAs($mentor)->get(route('mentor.register', $record))->assertForbidden();
    }

    public function test_reports_hide_inactive_stage_sessions_from_the_coordinator_but_not_the_admin(): void
    {
        $session = $this->curriculumSession();
        $this->deactivateStageOf($session);

        $coordinatorSees = ReportService::forUser($this->coordinator())
            ->progressBySession()->pluck('session.id');
        $this->assertFalse($coordinatorSees->contains($session->id));

        $adminSees = ReportService::forUser($this->admin())
            ->progressBySession()->pluck('session.id');
        $this->assertTrue($adminSees->contains($session->id));
    }

    public function test_admin_can_deactivate_a_stage_from_the_program_relation_manager(): void
    {
        $session = $this->curriculumSession();
        $stage = $session->stage()->firstOrFail();
        $program = $stage->program;

        $this->actingAs($this->admin());

        Livewire::test(StagesRelationManager::class, [
            'ownerRecord' => $program,
            'pageClass' => EditProgram::class,
        ])
            ->callTableAction('toggleStatus', $stage)
            ->assertHasNoActionErrors();

        $this->assertFalse($stage->fresh()->isActive());
    }

    // ---- Materials by business unit ---------------------------------------

    public function test_a_material_without_a_business_unit_reaches_everyone(): void
    {
        $program = Program::firstOrFail();
        $tool = $this->materialForProgram($program, businessUnit: null);

        $tools = app(ResourceResolver::class)->programTools($program, $this->mentee());

        $this->assertTrue($tools->contains('id', $tool->id));
    }

    public function test_a_material_pinned_to_a_business_unit_only_reaches_that_unit(): void
    {
        $program = Program::firstOrFail();
        $tool = $this->materialForProgram($program, businessUnit: 'Minería');

        $mentee = $this->mentee();
        $resolver = app(ResourceResolver::class);

        $mentee->update(['business_unit' => 'Corporativo']);
        $this->assertFalse($resolver->programTools($program, $mentee->fresh())->contains('id', $tool->id));

        $mentee->update(['business_unit' => 'Minería']);
        $this->assertTrue($resolver->programTools($program, $mentee->fresh())->contains('id', $tool->id));
    }

    public function test_a_user_without_a_business_unit_never_sees_pinned_materials(): void
    {
        $program = Program::firstOrFail();
        $tool = $this->materialForProgram($program, businessUnit: 'Minería');

        $mentee = $this->mentee();
        $mentee->update(['business_unit' => null]);

        $this->assertFalse(
            app(ResourceResolver::class)->programTools($program, $mentee->fresh())->contains('id', $tool->id),
        );
    }

    public function test_business_unit_also_gates_session_materials(): void
    {
        $session = $this->curriculumSession();
        $mentee = $this->mentee();

        $tool = Tool::create([
            'organization_id' => $mentee->organization_id,
            'name' => ['es' => 'Material de unidad', 'en' => 'Business unit material'],
            'type' => 'pdf',
            'visibility' => 'all',
            'business_unit' => 'Minería',
            'status' => 'active',
            'external_url' => 'https://example.test/pinned.pdf',
        ]);
        $tool->relations()->create(['session_id' => $session->id]);

        $resolver = app(ResourceResolver::class);

        $mentee->update(['business_unit' => 'Corporativo']);
        $this->assertFalse($resolver->sessionTools($session, $mentee->fresh())->contains('id', $tool->id));

        $mentee->update(['business_unit' => 'Minería']);
        $this->assertTrue($resolver->sessionTools($session, $mentee->fresh())->contains('id', $tool->id));
    }

    public function test_mentee_dashboard_hides_a_material_from_another_business_unit(): void
    {
        $program = Program::firstOrFail();
        $this->materialForProgram($program, businessUnit: 'Minería', name: 'Manual exclusivo');

        $mentee = $this->mentee();
        $mentee->update(['business_unit' => 'Corporativo']);

        $this->actingAs($mentee->fresh())->get('/me')
            ->assertSuccessful()
            ->assertDontSee('Manual exclusivo');

        $mentee->update(['business_unit' => 'Minería']);

        $this->actingAs($mentee->fresh())->get('/me')
            ->assertSuccessful()
            ->assertSee('Manual exclusivo');
    }

    protected function materialForProgram(Program $program, ?string $businessUnit, string $name = 'Material de prueba'): Tool
    {
        $tool = Tool::create([
            'organization_id' => $program->organization_id,
            'name' => ['es' => $name, 'en' => $name],
            'type' => 'pdf',
            'visibility' => 'all',
            'business_unit' => $businessUnit,
            'status' => 'active',
            'external_url' => 'https://example.test/material.pdf',
        ]);
        $tool->relations()->create(['program_id' => $program->id]);

        return $tool;
    }
}
