<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Services\SessionRecordProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtraSessionsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function makeExtra(Assignment $assignment, array $overrides = [])
    {
        return $assignment->extraSessions()->create(array_merge([
            'organization_id' => $assignment->organization_id,
            'program_id' => $assignment->program_id,
            'name' => ['es' => 'Sesión extra', 'en' => 'Extra session'],
            'number' => 99,
            'sort_order' => 999,
            'visible_to_participant' => true,
            'start_date' => now()->toDateString(),
        ], $overrides));
    }

    public function test_extra_session_belongs_to_dupla_and_not_to_program_curriculum(): void
    {
        $assignment = Assignment::firstOrFail();
        $curriculum = $assignment->program->sessions()->count();

        $extra = $this->makeExtra($assignment);

        // Included in the dupla's full set, excluded from the shared curriculum.
        $this->assertTrue($assignment->fresh()->allSessions()->contains('id', $extra->id));
        $this->assertCount($curriculum + 1, $assignment->fresh()->allSessions());
        $this->assertFalse($assignment->program->sessions()->get()->contains('id', $extra->id));
    }

    public function test_provisioner_creates_a_record_for_the_extra_session(): void
    {
        $assignment = Assignment::firstOrFail();
        $extra = $this->makeExtra($assignment);

        app(SessionRecordProvisioner::class)->forAssignment($assignment);

        $this->assertNotNull(
            $assignment->records()->where('session_id', $extra->id)->first()
        );
    }

    public function test_mentor_sees_the_extra_session_in_the_portal(): void
    {
        $assignment = Assignment::firstOrFail();
        $this->makeExtra($assignment, ['name' => ['es' => 'Sesión de refuerzo', 'en' => 'Booster']]);
        app(SessionRecordProvisioner::class)->forAssignment($assignment);

        $this->actingAs($assignment->facilitator)
            ->get(route('mentor.participant', $assignment))
            ->assertSuccessful()
            ->assertSee('Sesión de refuerzo');
    }

    public function test_other_dupla_cannot_open_a_foreign_extra_session(): void
    {
        $assignment = Assignment::firstOrFail();
        $extra = $this->makeExtra($assignment);

        // A participant from a different assignment (or none) must be blocked.
        $outsider = \App\Models\User::where('role', 'participant')
            ->where('id', '!=', $assignment->participant_id)->first();

        if ($outsider) {
            $this->actingAs($outsider)->get(route('participant.session', $extra))->assertForbidden();
        }

        $this->assertSame($assignment->id, $extra->assignment_id);
    }
}
