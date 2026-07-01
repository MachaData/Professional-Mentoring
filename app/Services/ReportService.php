<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Program;
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
}
