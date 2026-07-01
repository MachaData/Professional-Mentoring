<?php

namespace Tests\Feature;

use App\Exports\ProgramProgressExport;
use App\Imports\AssignmentsImport;
use App\Imports\UsersImport;
use App\Models\Assignment;
use App\Models\Program;
use App\Models\SessionRecord;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_report_service_counts_and_session_stats(): void
    {
        $report = new ReportService(1);

        $this->assertGreaterThanOrEqual(1, $report->programCount());
        $this->assertGreaterThanOrEqual(1, $report->userCount(User::ROLE_FACILITATOR));

        $stats = $report->sessionStats();
        $this->assertSame(
            $stats['total'],
            $stats['completed'] + $stats['pending'] + $stats['expired']
                + $this->otherStatuses($stats)
        );
    }

    protected function otherStatuses(array $stats): int
    {
        // total may include rescheduled/cancelled; ensure the three buckets never exceed total.
        return max(0, $stats['total'] - $stats['completed'] - $stats['pending'] - $stats['expired']);
    }

    public function test_expired_detection_flags_past_pending_sessions(): void
    {
        $assignment = Assignment::firstOrFail();
        $record = $assignment->records()->firstOrFail();
        $record->update(['status' => SessionRecord::STATUS_PENDING]);
        $record->session->update(['end_date' => now()->subDay()]);

        $report = new ReportService(1);
        $this->assertSame(SessionRecord::STATUS_EXPIRED, $report->effectiveStatus($record->fresh()));
    }

    public function test_program_progress_export_produces_rows(): void
    {
        $program = Program::firstOrFail();
        $export = new ProgramProgressExport($program);

        $this->assertGreaterThan(0, $export->collection()->count());
        $this->assertCount(7, $export->headings());
    }

    public function test_users_import_creates_and_normalizes_roles(): void
    {
        $import = new UsersImport(1);
        $import->collection(new Collection([
            new Collection(['nombre' => 'Nuevo Mentor', 'correo' => 'nuevo.mentor@test.com', 'rol' => 'Mentor', 'cargo' => 'Jefe']),
            new Collection(['nombre' => 'Nueva Mentee', 'correo' => 'nueva.mentee@test.com', 'rol' => 'Mentee']),
        ]));

        $this->assertSame(2, $import->imported);
        $this->assertSame(User::ROLE_FACILITATOR, User::where('email', 'nuevo.mentor@test.com')->value('role'));
        $this->assertSame(User::ROLE_PARTICIPANT, User::where('email', 'nueva.mentee@test.com')->value('role'));
        $this->assertTrue((bool) User::where('email', 'nuevo.mentor@test.com')->value('must_change_password'));
    }

    public function test_assignments_import_creates_dupla_and_provisions_records(): void
    {
        $program = Program::firstOrFail();
        $mentor = User::factory()->create(['organization_id' => 1, 'role' => User::ROLE_FACILITATOR, 'email' => 'imp.mentor@test.com']);
        $mentee = User::factory()->create(['organization_id' => 1, 'role' => User::ROLE_PARTICIPANT, 'email' => 'imp.mentee@test.com']);

        $import = new AssignmentsImport(1);
        $import->collection(new Collection([
            new Collection([
                'correo_facilitador' => 'imp.mentor@test.com',
                'correo_participante' => 'imp.mentee@test.com',
                'programa' => $program->slug,
            ]),
        ]));

        $this->assertSame(1, $import->imported);
        $assignment = Assignment::where('participant_id', $mentee->id)->firstOrFail();
        $this->assertSame($program->sessions()->count(), $assignment->records()->count());
    }
}
