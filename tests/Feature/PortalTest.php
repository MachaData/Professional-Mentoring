<?php

namespace Tests\Feature;

use App\Livewire\Mentor\RegisterSession;
use App\Models\Assignment;
use App\Models\SessionRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PortalTest extends TestCase
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

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertSuccessful()->assertSee('Professional Mentoring');
    }

    public function test_facilitator_lands_on_mentor_portal(): void
    {
        $this->actingAs($this->mentor())->get('/mentor')->assertSuccessful()->assertSee('Mis participantes');
    }

    public function test_participant_lands_on_their_portal(): void
    {
        $this->actingAs($this->mentee())->get('/me')->assertSuccessful()->assertSee('Mis sesiones');
    }

    public function test_facilitator_cannot_open_participant_portal(): void
    {
        $this->actingAs($this->mentor())->get('/me')->assertForbidden();
    }

    public function test_mentor_can_view_a_participant_sessions(): void
    {
        $assignment = Assignment::where('facilitator_id', $this->mentor()->id)->firstOrFail();
        $this->actingAs($this->mentor())->get("/mentor/participants/{$assignment->id}")->assertSuccessful();
    }

    public function test_mentor_cannot_view_someone_elses_assignment(): void
    {
        $assignment = Assignment::where('facilitator_id', $this->mentor()->id)->firstOrFail();
        $intruder = User::factory()->create([
            'organization_id' => $this->mentor()->organization_id,
            'role' => User::ROLE_FACILITATOR,
        ]);

        $this->actingAs($intruder)->get("/mentor/participants/{$assignment->id}")->assertForbidden();
    }

    public function test_mentor_registers_a_session_via_livewire(): void
    {
        $assignment = Assignment::where('facilitator_id', $this->mentor()->id)->firstOrFail();
        $record = $assignment->records()->firstOrFail();
        $summary = $record->session->customFields()->where('name', 'summary')->firstOrFail();

        Livewire::actingAs($this->mentor())
            ->test(RegisterSession::class, ['record' => $record])
            ->set('realSessionDate', '2026-07-05')
            ->set('attendance', 'attended')
            ->set('modality', 'virtual')
            ->set('meetingUrl', 'https://zoom.us/j/123')
            ->set("data.field_{$summary->id}", 'Primera sesión muy positiva')
            ->call('complete')
            ->assertHasNoErrors();

        $record->refresh();
        // Structured fields persist reliably on the record.
        $this->assertSame(SessionRecord::STATUS_COMPLETED, $record->status);
        $this->assertSame('attended', $record->attendance);
        $this->assertSame('virtual', $record->modality);
        $this->assertSame('2026-07-05', $record->real_session_date->toDateString());
        $this->assertSame('https://zoom.us/j/123', $record->meeting_url);
        $this->assertNotNull($record->submitted_at);
        $this->assertDatabaseHas('session_record_values', [
            'session_record_id' => $record->id,
            'custom_field_id' => $summary->id,
            'value_text' => 'Primera sesión muy positiva',
        ]);
    }

    public function test_participant_can_open_session_detail(): void
    {
        $assignment = Assignment::where('participant_id', $this->mentee()->id)->firstOrFail();
        $session = $assignment->program->sessions()->where('number', 1)->firstOrFail();

        $this->actingAs($this->mentee()->fresh())->get("/me/sessions/{$session->getKey()}")
            ->assertSuccessful()
            ->assertSee('Ingresar a la sesión')   // Session 1 record has a meeting link
            ->assertSee('Materiales de la sesión'); // Session 1 has the workbook attached
    }

    public function test_welcome_popup_shows_on_first_login_then_is_dismissed(): void
    {
        $mentee = $this->mentee();
        $this->assertNull($mentee->onboarding_seen_at);

        // First visit shows the welcome popup.
        $this->actingAs($mentee)->get('/me')->assertSee('Te damos la bienvenida');

        // Dismissing marks it seen.
        $this->actingAs($mentee)->post('/onboarding/seen')->assertNoContent();
        $this->assertNotNull($mentee->fresh()->onboarding_seen_at);

        // It no longer appears.
        $this->actingAs($mentee->fresh())->get('/me')->assertDontSee('Te damos la bienvenida');
    }

    public function test_participant_sees_session_join_link(): void
    {
        // The demo seeds a meeting link on Session 1's record.
        $this->actingAs($this->mentee()->fresh())->get('/me')
            ->assertSee('Ingresar a la sesión')
            ->assertSee('meet.google.com');
    }

    public function test_participant_sees_per_session_survey(): void
    {
        $this->actingAs($this->mentee()->fresh())->get('/me')
            ->assertSee('Abrir encuesta')
            ->assertSee('forms.gle');
    }

    public function test_mentor_sees_copy_survey_button(): void
    {
        $assignment = Assignment::where('facilitator_id', $this->mentor()->id)->firstOrFail();

        $this->actingAs($this->mentor())->get("/mentor/participants/{$assignment->id}")
            ->assertSee('Copiar encuesta')
            ->assertSee('Abrir encuesta');
    }

    public function test_completed_visible_fields_appear_on_participant_dashboard(): void
    {
        $assignment = Assignment::where('participant_id', $this->mentee()->id)->firstOrFail();
        $record = $assignment->records()->firstOrFail();
        $summary = $record->session->customFields()->where('name', 'summary')->firstOrFail();

        $record->update(['status' => SessionRecord::STATUS_COMPLETED, 'submitted_at' => now()]);
        $record->values()->create(['custom_field_id' => $summary->id, 'value_text' => 'Acuerdo visible']);

        $this->actingAs($this->mentee())->get('/me')->assertSuccessful()->assertSee('Acuerdo visible');
    }
}
