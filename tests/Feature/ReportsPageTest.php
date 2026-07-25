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

    public function test_session_breakdown_counts_over_all_duplas(): void
    {
        $assignment = Assignment::firstOrFail();
        $program = $assignment->program;
        $sessions = $program->sessions()->orderBy('sort_order')->get();

        // Complete S1 for this dupla; make S2 overdue (past end_date), record stays pending.
        $assignment->records()->where('session_id', $sessions[0]->id)
            ->update(['status' => SessionRecord::STATUS_COMPLETED]);
        $sessions[1]->update(['end_date' => now()->subDay()]);

        $breakdown = (new ReportService(1))->sessionBreakdownByProgram();
        $progRow = $breakdown->firstWhere(fn ($r) => $r['program']->id === $program->id);
        $this->assertNotNull($progRow);

        $total = $progRow['total_duplas'];
        $this->assertGreaterThan(0, $total);

        // Partition invariant: every dupla counted exactly once per session.
        foreach ($progRow['sessions'] as $row) {
            $this->assertSame($total, $row['completed'] + $row['pending'] + $row['expired']);
        }

        $s1 = $progRow['sessions']->firstWhere(fn ($r) => $r['session']->id === $sessions[0]->id);
        $this->assertGreaterThanOrEqual(1, $s1['completed']);

        // S2 is past its deadline -> non-completed duplas are overdue, none pending.
        $s2 = $progRow['sessions']->firstWhere(fn ($r) => $r['session']->id === $sessions[1]->id);
        $this->assertGreaterThanOrEqual(1, $s2['expired']);
        $this->assertSame(0, $s2['pending']);
    }

    // ---- Última actividad e informe individual ----------------------------

    public function test_last_activity_picks_the_most_recent_sign_of_life(): void
    {
        $assignment = Assignment::firstOrFail();
        $row = fn () => (new ReportService(1))->duplasBySchedule()->firstWhere('assignment.id', $assignment->id);

        // A shared file, then a newer mailbox message: the newest source wins.
        $assignment->files()->create([
            'organization_id' => $assignment->organization_id,
            'uploaded_by' => $assignment->facilitator_id,
            'title' => 'Acta', 'type' => 'text', 'body_text' => 'notas',
            'created_at' => now()->subDays(3), 'updated_at' => now()->subDays(3),
        ]);
        $assignment->messages()->create([
            'organization_id' => $assignment->organization_id,
            'sender_id' => $assignment->participant_id,
            'subject' => 'Hola', 'created_at' => now()->subDay(), 'updated_at' => now()->subDay(),
        ]);

        $this->assertSame('mensaje', $row()['last_activity_type']);

        // Touching a still-pending record is not activity — records are created
        // with the dupla, so counting them would date every dupla to its setup.
        $assignment->records()->where('status', SessionRecord::STATUS_PENDING)
            ->update(['updated_at' => now()]);
        $this->assertSame('mensaje', $row()['last_activity_type']);

        // Filling one in is.
        $assignment->records()->where('status', SessionRecord::STATUS_PENDING)->limit(1)
            ->update(['status' => SessionRecord::STATUS_COMPLETED, 'updated_at' => now()]);
        $this->assertSame('sesión', $row()['last_activity_type']);
        $this->assertTrue($row()['last_activity']->isToday());
    }

    /** The individual report must agree with the list it is opened from. */
    public function test_dupla_report_matches_the_list_row(): void
    {
        $assignment = Assignment::firstOrFail();
        $sessions = $assignment->program->sessions()->orderBy('sort_order')->get();

        $assignment->records()->where('session_id', $sessions[0]->id)
            ->update(['status' => SessionRecord::STATUS_COMPLETED]);
        $sessions[1]->update(['end_date' => now()->subDays(4)]);

        $service = new ReportService(1);
        $listRow = $service->duplasBySchedule()->firstWhere('assignment.id', $assignment->id);
        $single = $service->duplaReport($assignment);

        foreach (['category', 'state_label', 'completed', 'total', 'percent', 'days_behind'] as $key) {
            $this->assertSame($listRow[$key], $single[$key], "«{$key}» difiere entre la lista y el informe individual.");
        }
        $this->assertSame($listRow['current']?->id, $single['current']?->id);
        $this->assertSame($listRow['expected']?->id, $single['expected']?->id);
    }

    public function test_dupla_page_shows_the_individual_report(): void
    {
        $coordinator = User::where('email', 'coordinador@demo.test')->firstOrFail();
        $assignment = Assignment::firstOrFail();

        $this->actingAs($coordinator)
            ->get("/admin/assignments/{$assignment->getKey()}")
            ->assertSuccessful()
            ->assertSee('Informe de la dupla')
            ->assertSee('Sesión esperada')
            ->assertSee('Última actividad')
            ->assertSee($assignment->facilitator->name)
            ->assertSee($assignment->participant->name);
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
