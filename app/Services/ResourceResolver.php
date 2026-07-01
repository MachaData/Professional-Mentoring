<?php

namespace App\Services;

use App\Models\Program;
use App\Models\Session;
use App\Models\Survey;
use App\Models\Tool;
use Illuminate\Support\Collection;

/**
 * Resolves which tools and surveys a given role can see for a program, and
 * optionally narrowed to a session (also picking up the session's stage and
 * the program-wide resources).
 */
class ResourceResolver
{
    /** @return Collection<int,Tool> */
    public function toolsFor(Program $program, string $role, ?Session $session = null): Collection
    {
        $sessionIds = $session ? [$session->id] : $program->sessions->pluck('id')->all();
        $stageIds = $session && $session->stage_id ? [$session->stage_id] : [];

        return Tool::query()
            ->where('status', 'active')
            ->whereHas('relations', function ($q) use ($program, $sessionIds, $stageIds) {
                $q->where('program_id', $program->id)
                    ->when($sessionIds, fn ($q) => $q->orWhereIn('session_id', $sessionIds))
                    ->when($stageIds, fn ($q) => $q->orWhereIn('stage_id', $stageIds));
            })
            ->get()
            ->filter(fn (Tool $tool) => $tool->visibleTo($role))
            ->values();
    }

    /** @return Collection<int,Survey> */
    public function surveysFor(Program $program, string $role, ?Session $session = null): Collection
    {
        return Survey::query()
            ->where('status', 'active')
            ->where('program_id', $program->id)
            ->when($session, fn ($q) => $q->where(function ($q) use ($session) {
                $q->whereNull('session_id')->orWhere('session_id', $session->id);
            }))
            ->get()
            ->filter(fn (Survey $survey) => $survey->visibleTo($role))
            ->values();
    }
}
