<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Program;
use App\Models\Session;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionOrderingTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_all_sessions_are_ordered_by_program_order_not_by_date(): void
    {
        $program = Program::firstOrFail();
        $assignment = Assignment::where('program_id', $program->id)->firstOrFail();

        // Scramble the dates so date-based ordering would put Sesión 10 first.
        foreach ($program->sessions()->get() as $session) {
            $session->forceFill([
                'start_date' => now()->subDays($session->number)->toDateString(),
            ])->saveQuietly();
        }

        $assignment->load('program.sessions', 'extraSessions');

        $numbers = $assignment->allSessions()
            ->pluck('number')
            ->filter()
            ->values()
            ->all();

        $this->assertSame(range(1, count($numbers)), $numbers);
    }

    public function test_new_session_without_sort_order_falls_back_to_its_number(): void
    {
        $program = Program::firstOrFail();

        $session = Session::create([
            'organization_id' => $program->organization_id,
            'program_id' => $program->id,
            'number' => 7,
            'name' => ['es' => 'Nueva', 'en' => 'New'],
            'status' => 'active',
            // No sort_order provided (as when created from the admin form).
        ]);

        $this->assertSame(7, $session->fresh()->sort_order);
    }

    public function test_explicit_sort_order_is_respected(): void
    {
        $program = Program::firstOrFail();

        $session = Session::create([
            'organization_id' => $program->organization_id,
            'program_id' => $program->id,
            'number' => 3,
            'sort_order' => 99,
            'name' => ['es' => 'Extra', 'en' => 'Extra'],
            'status' => 'active',
        ]);

        $this->assertSame(99, $session->fresh()->sort_order);
    }
}
