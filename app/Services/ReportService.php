<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Program;
use App\Models\Session;
use App\Models\SessionRecord;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Computes platform metrics for dashboards, reports and exports. All queries are
 * scoped to a single organization (null = all, for superadmins).
 */
class ReportService
{
    public function __construct(protected ?int $organizationId = null) {}

    public static function forUser(?User $user): self
    {
        return new self($user && ! $user->isSuperadmin() ? $user->organization_id : null);
    }

    protected function scope($query, string $column = 'organization_id')
    {
        return $this->organizationId
            ? $query->where($column, $this->organizationId)
            : $query;
    }

    public function programCount(bool $activeOnly = false): int
    {
        $q = $this->scope(Program::query());

        return $activeOnly ? $q->where('status', 'active')->count() : $q->count();
    }

    public function userCount(string $role): int
    {
        return $this->scope(User::query())->where('role', $role)->count();
    }

    public function activeAssignments(): int
    {
        return $this->scope(Assignment::query())->where('status', 'active')->count();
    }

    /** @return array{completed:int,pending:int,expired:int,total:int} */
    public function sessionStats(): array
    {
        $records = $this->scope(SessionRecord::query())
            ->with('session:id,end_date')
            ->get(['id', 'status', 'session_id']);

        $today = Carbon::today();
        $stats = ['completed' => 0, 'pending' => 0, 'expired' => 0, 'total' => $records->count()];

        foreach ($records as $record) {
            $status = $this->effectiveStatus($record, $today);
            if ($status === SessionRecord::STATUS_COMPLETED) {
                $stats['completed']++;
            } elseif ($status === SessionRecord::STATUS_EXPIRED) {
                $stats['expired']++;
            } elseif (in_array($status, [SessionRecord::STATUS_PENDING, SessionRecord::STATUS_DRAFT], true)) {
                $stats['pending']++;
            }
        }

        return $stats;
    }

    public function effectiveStatus(SessionRecord $record, ?Carbon $today = null): string
    {
        $today ??= Carbon::today();

        if (in_array($record->status, [SessionRecord::STATUS_PENDING, SessionRecord::STATUS_DRAFT], true)
            && $record->session?->end_date
            && $record->session->end_date->lt($today)) {
            return SessionRecord::STATUS_EXPIRED;
        }

        return $record->status;
    }

    /** Participants that have no assignment. */
    public function participantsWithoutFacilitator(): int
    {
        return $this->scope(User::query())
            ->where('role', User::ROLE_PARTICIPANT)
            ->whereDoesntHave('assignmentsAsParticipant')
            ->count();
    }

    /** @return Collection<int,array{program:Program,total:int,completed:int,percent:int}> */
    public function progressByProgram(): Collection
    {
        return $this->scope(Program::query())
            ->withCount([
                'sessions as records_total' => fn ($q) => $q,
            ])
            ->with(['assignments'])
            ->get()
            ->map(function (Program $program) {
                $records = SessionRecord::whereIn('assignment_id', $program->assignments->pluck('id'))->get(['status']);
                $total = $records->count();
                $completed = $records->where('status', SessionRecord::STATUS_COMPLETED)->count();

                return [
                    'program' => $program,
                    'total' => $total,
                    'completed' => $completed,
                    'percent' => $total ? (int) round($completed / $total * 100) : 0,
                ];
            });
    }

    /**
     * Overall progress per session: how many dupla records are completed,
     * pending or overdue for each session.
     *
     * @return Collection<int,array{session:Session,total:int,completed:int,pending:int,expired:int,percent:int}>
     */
    public function progressBySession(): Collection
    {
        $today = Carbon::today();

        return $this->scope(Session::query())
            ->whereNull('assignment_id') // curriculum only; per-dupla extras excluded
            ->with(['records', 'program:id,name'])
            ->orderBy('program_id')->orderBy('sort_order')
            ->get()
            ->map(function (Session $session) use ($today) {
                $isPast = $session->end_date && $session->end_date->lt($today);
                $completed = $pending = $expired = 0;

                foreach ($session->records as $record) {
                    if ($record->status === SessionRecord::STATUS_COMPLETED) {
                        $completed++;
                    } elseif (in_array($record->status, [SessionRecord::STATUS_PENDING, SessionRecord::STATUS_DRAFT], true)) {
                        $isPast ? $expired++ : $pending++;
                    }
                }

                $total = $session->records->count();

                return [
                    'session' => $session,
                    'total' => $total,
                    'completed' => $completed,
                    'pending' => $pending,
                    'expired' => $expired,
                    'percent' => $total ? (int) round($completed / $total * 100) : 0,
                ];
            });
    }

    /**
     * The session a program "should" be on today per its calendar: the latest
     * session whose start date has arrived (or the first if it hasn't started).
     *
     * @param  Collection<int,Session>  $sessions  ordered by sort_order
     */
    protected function expectedSession(Collection $sessions, Carbon $today): ?Session
    {
        $expected = $sessions->first();
        foreach ($sessions as $session) {
            if ($session->start_date && $session->start_date->lte($today)) {
                $expected = $session;
            }
        }

        return $expected;
    }

    /**
     * Classifies every dupla by schedule state and enriches it with its current
     * session, the expected session per the calendar and the days behind.
     *
     *  - sin_inicio: no completed sessions yet
     *  - fuera: behind schedule (overdue current session or below the expected one)
     *  - dentro: started and on schedule
     *
     * @return Collection<int,array<string,mixed>>
     */
    public function duplasBySchedule(): Collection
    {
        $today = Carbon::today();

        $allSessions = $this->scope(Session::query())
            ->get(['id', 'program_id', 'assignment_id', 'number', 'name', 'sort_order', 'start_date', 'end_date'])
            ->sortBy('sort_order');

        // Curriculum (program-wide) sessions per program + extras per dupla.
        $programSessions = $allSessions->whereNull('assignment_id')->groupBy('program_id');
        $extraByAssignment = $allSessions->whereNotNull('assignment_id')->groupBy('assignment_id');

        // "Expected" position follows the curriculum only, not ad-hoc extras.
        $expectedByProgram = $programSessions->map(fn ($sessions) => $this->expectedSession($sessions->values(), $today));

        return $this->scope(Assignment::query())
            ->with(['facilitator:id,name', 'participant:id,name', 'program:id,name', 'records'])
            ->get()
            ->map(function (Assignment $assignment) use ($today, $programSessions, $extraByAssignment, $expectedByProgram) {
                $sessions = ($programSessions[$assignment->program_id] ?? collect())
                    ->concat($extraByAssignment[$assignment->id] ?? collect())
                    ->sortBy('sort_order')
                    ->values();
                $recordsBySession = $assignment->records->keyBy('session_id');

                $completed = 0;
                $current = null; // first session not completed = where the dupla is

                foreach ($sessions as $session) {
                    $record = $recordsBySession->get($session->id);
                    if ($record && $record->status === SessionRecord::STATUS_COMPLETED) {
                        $completed++;
                    } elseif ($current === null) {
                        $current = $session;
                    }
                }

                $total = $sessions->count();
                $expected = $expectedByProgram[$assignment->program_id] ?? null;

                // Days behind: how long the current session's window has been closed.
                $daysBehind = 0;
                if ($current && $current->end_date && $current->end_date->lt($today)) {
                    $daysBehind = $current->end_date->diffInDays($today);
                }

                $behind = $daysBehind > 0
                    || ($current && $expected && $current->sort_order < $expected->sort_order);

                if ($completed === $total) {
                    $category = 'dentro'; // finished
                } elseif ($completed === 0) {
                    $category = 'sin_inicio';
                } else {
                    $category = $behind ? 'fuera' : 'dentro';
                }

                return [
                    'assignment' => $assignment,
                    'total' => $total,
                    'completed' => $completed,
                    'percent' => $total ? (int) round($completed / $total * 100) : 0,
                    'category' => $category,
                    'current' => $current,               // Session|null (null = finished)
                    'expected' => $expected,             // Session|null
                    'days_behind' => (int) $daysBehind,
                ];
            });
    }
}
