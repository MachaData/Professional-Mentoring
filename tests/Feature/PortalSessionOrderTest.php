<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalSessionOrderTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function mentee(): User
    {
        return User::where('email', 'mentee@demo.test')->firstOrFail();
    }

    private function mentor(): User
    {
        return User::where('email', 'mentor@demo.test')->firstOrFail();
    }

    private function assignment(): Assignment
    {
        return Assignment::where('participant_id', $this->mentee()->id)->firstOrFail();
    }

    /** Give session number N a date that is the REVERSE of program order. */
    private function scrambleDates(Assignment $assignment): void
    {
        foreach ($assignment->program->sessions()->get() as $session) {
            // number 1 => latest date, number 10 => earliest date.
            $session->forceFill([
                'start_date' => now()->addDays(100 - (int) $session->number)->toDateString(),
            ])->saveQuietly();
        }
    }

    /**
     * Pull the session numbers in the order they are rendered on the page, by
     * reading the /me/sessions/{id} links and mapping id -> number.
     *
     * @return array<int,int>
     */
    private function renderedNumbers(string $html, Assignment $assignment): array
    {
        preg_match_all('#/(?:me|sessions)/(?:sessions/)?(\d+)#', $html, $m);
        $ids = array_values(array_unique(array_map('intval', $m[1])));

        $numberById = $assignment->program->sessions()->pluck('number', 'id');

        return collect($ids)
            ->map(fn ($id) => $numberById[$id] ?? null)
            ->filter()
            ->values()
            ->all();
    }

    public function test_mentee_dashboard_lists_sessions_in_program_order(): void
    {
        $assignment = $this->assignment();
        $this->scrambleDates($assignment);

        $html = $this->actingAs($this->mentee())->get('/me')->assertSuccessful()->getContent();
        $numbers = $this->renderedNumbers($html, $assignment);

        $this->assertNotEmpty($numbers, 'No se detectaron sesiones renderizadas.');
        $sorted = $numbers;
        sort($sorted);
        $this->assertSame($sorted, $numbers, 'Las sesiones del mentee no están en orden ascendente: '.implode(',', $numbers));
    }

    public function test_mentor_participant_view_lists_sessions_in_program_order(): void
    {
        $assignment = $this->assignment();
        $this->scrambleDates($assignment);

        $html = $this->actingAs($this->mentor())->get("/mentor/participants/{$assignment->id}")->assertSuccessful()->getContent();

        // Mentor view groups by stage and shows an "S{number}" badge per session;
        // within the flattened render, program order holds because stages are
        // contiguous ranges of sort_order.
        preg_match_all('#>\s*S(\d+)\s*<#', $html, $m);
        $numbers = array_map('intval', $m[1]);

        $this->assertNotEmpty($numbers);
        $sorted = $numbers;
        sort($sorted);
        $this->assertSame($sorted, $numbers, 'Las sesiones del mentor no están en orden: '.implode(',', $numbers));
    }
}
