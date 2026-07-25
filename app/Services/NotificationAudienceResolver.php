<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Turns the "Enviar notificación" form data into a deduped list of recipients.
 * Each target is ['user' => User, 'assignment' => ?Assignment] so the sender can
 * build per-recipient variables (program, mentor/mentee names, links).
 */
class NotificationAudienceResolver
{
    /**
     * @param  array<string,mixed>  $data
     * @return Collection<int,array{user:User,assignment:?Assignment}>
     */
    public function resolve(array $data, ?int $orgId): Collection
    {
        $audience = $data['audience'] ?? null;
        $programId = $data['program_id'] ?? null;
        $recipients = $data['dupla_recipients'] ?? 'both';

        $targets = match ($audience) {
            'all' => $this->byRoles([User::ROLE_FACILITATOR, User::ROLE_PARTICIPANT, User::ROLE_COORDINATOR], $orgId, $programId),
            'facilitators' => $this->byRoles([User::ROLE_FACILITATOR], $orgId, $programId),
            'participants' => $this->byRoles([User::ROLE_PARTICIPANT], $orgId, $programId),
            'coordinators' => $this->byRoles([User::ROLE_COORDINATOR], $orgId, null),
            'user' => $this->singleUser($data['user_id'] ?? null, $programId),
            'duplas' => $this->fromAssignments($this->assignmentsByIds($data['assignment_ids'] ?? []), $recipients),
            'filter' => $this->fromAssignments($this->assignmentsByFilters($data['filters'] ?? [], $orgId, $programId), $recipients),
            default => collect(),
        };

        return $targets
            ->filter(fn ($t) => $t['user'] && filled($t['user']->email))
            ->unique(fn ($t) => $t['user']->id)
            ->values();
    }

    /** @return Collection<int,array{user:User,assignment:?Assignment}> */
    protected function byRoles(array $roles, ?int $orgId, ?int $programId): Collection
    {
        $query = User::query()->whereIn('role', $roles);

        if ($orgId) {
            $query->where('organization_id', $orgId);
        }

        if ($programId) {
            $query->where(function ($q) use ($programId) {
                $q->whereHas('assignmentsAsFacilitator', fn ($a) => $a->where('program_id', $programId))
                    ->orWhereHas('assignmentsAsParticipant', fn ($a) => $a->where('program_id', $programId));
            });
        }

        return $query->get()->map(fn (User $u) => [
            'user' => $u,
            'assignment' => $this->anyAssignment($u, $programId),
        ]);
    }

    /** @return Collection<int,array{user:User,assignment:?Assignment}> */
    protected function singleUser(?int $userId, ?int $programId): Collection
    {
        $user = $userId ? User::find($userId) : null;

        return $user
            ? collect([['user' => $user, 'assignment' => $this->anyAssignment($user, $programId)]])
            : collect();
    }

    /**
     * @param  Collection<int,Assignment>  $assignments
     * @return Collection<int,array{user:User,assignment:Assignment}>
     */
    protected function fromAssignments(Collection $assignments, string $recipients): Collection
    {
        $targets = collect();

        foreach ($assignments as $assignment) {
            if (in_array($recipients, ['mentor', 'both'], true) && $assignment->facilitator) {
                $targets->push(['user' => $assignment->facilitator, 'assignment' => $assignment]);
            }
            if (in_array($recipients, ['mentee', 'both'], true) && $assignment->participant) {
                $targets->push(['user' => $assignment->participant, 'assignment' => $assignment]);
            }
        }

        return $targets;
    }

    /** @return Collection<int,Assignment> */
    protected function assignmentsByIds(array $ids): Collection
    {
        if (empty($ids)) {
            return collect();
        }

        return Assignment::whereIn('id', $ids)
            ->with(['facilitator', 'participant', 'program'])
            ->get();
    }

    /** @return Collection<int,Assignment> */
    protected function assignmentsByFilters(array $filters, ?int $orgId, ?int $programId): Collection
    {
        if (empty($filters)) {
            return collect();
        }

        // Notifications target mentors/mentees, so a deactivated stage's sessions
        // must not drive "atrasada" / "sesión pendiente" classifications.
        $rows = (new ReportService($orgId, false))->duplasBySchedule();

        $ids = $rows->filter(function (array $row) use ($filters) {
            foreach ($filters as $filter) {
                if ($filter === 'atrasadas' && $row['category'] === 'fuera') {
                    return true;
                }
                if ($filter === 'sin_inicio' && $row['category'] === 'sin_inicio') {
                    return true;
                }
                if ($filter === 'sesion_pendiente' && $row['session_pending']) {
                    return true;
                }
                if ($filter === 'encuesta_pendiente' && $row['survey_pending']) {
                    return true;
                }
            }

            return false;
        })->pluck('assignment.id');

        $query = Assignment::whereIn('id', $ids)->with(['facilitator', 'participant', 'program']);

        if ($programId) {
            $query->where('program_id', $programId);
        }

        return $query->get();
    }

    protected function anyAssignment(User $user, ?int $programId = null): ?Assignment
    {
        foreach (['assignmentsAsFacilitator', 'assignmentsAsParticipant'] as $relation) {
            $query = $user->{$relation}()->with(['facilitator', 'participant', 'program']);
            if ($programId) {
                $query->where('program_id', $programId);
            }
            if ($assignment = $query->latest('id')->first()) {
                return $assignment;
            }
        }

        return null;
    }
}
