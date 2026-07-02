<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\SessionRecord;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsPageTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_coordinator_can_open_reports_page(): void
    {
        $coordinator = User::where('email', 'coordinador@demo.test')->firstOrFail();
        $this->actingAs($coordinator)->get('/admin/reports')
            ->assertSuccessful()
            ->assertSee('Avance general por sesión')
            ->assertSee('Duplas dentro del cronograma')
            ->assertSee('Duplas fuera del cronograma')
            ->assertSee('Duplas sin inicio');
    }

    public function test_facilitator_cannot_open_reports_page(): void
    {
        $facilitator = User::where('email', 'mentor@demo.test')->firstOrFail();
        $this->actingAs($facilitator)->get('/admin/reports')->assertForbidden();
    }

    public function test_progress_by_session_counts_states(): void
    {
        // Complete Session 1 for the demo dupla.
        $assignment = Assignment::firstOrFail();
        $s1 = $assignment->program->sessions()->where('number', 1)->firstOrFail();
        $assignment->records()->where('session_id', $s1->id)
            ->update(['status' => SessionRecord::STATUS_COMPLETED]);

        $rows = (new ReportService(1))->progressBySession();
        $s1row = $rows->firstWhere(fn ($r) => $r['session']->id === $s1->id);

        $this->assertSame(1, $s1row['completed']);
    }

    public function test_dupla_schedule_classification(): void
    {
        $assignment = Assignment::firstOrFail();
        $sessions = $assignment->program->sessions()->orderBy('sort_order')->get();

        // No completed sessions yet -> sin_inicio.
        $duplas = (new ReportService(1))->duplasBySchedule();
        $this->assertSame('sin_inicio', $duplas->firstWhere('assignment.id', $assignment->id)['category']);

        // Complete S1 -> started; make S2 overdue -> fuera del cronograma.
        $assignment->records()->where('session_id', $sessions[0]->id)->update(['status' => SessionRecord::STATUS_COMPLETED]);
        $sessions[1]->update(['end_date' => now()->subDay()]);
        // S2 record stays pending.

        $duplas = (new ReportService(1))->duplasBySchedule();
        $row = $duplas->firstWhere('assignment.id', $assignment->id);
        $this->assertSame('fuera', $row['category']);
        $this->assertGreaterThanOrEqual(1, $row['days_behind']);
        // Current session is S2 (first not completed) after completing S1.
        $this->assertSame(2, $row['current']->number);
        $this->assertNotNull($row['expected']);
    }
}
