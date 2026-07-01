<?php

namespace App\Services;

use App\Models\Program;
use App\Models\Session;
use App\Models\Survey;
use App\Models\Tool;
use Illuminate\Support\Collection;

/**
 * Resolves which tools and surveys a role can see. Program-level resources feed
 * the sidebar; session-level resources belong to each session card.
 */
class ResourceResolver
{
    /** Tools attached to the whole program (sidebar). @return Collection<int,Tool> */
    public function programTools(Program $program, string $role): Collection
    {
        return Tool::query()
            ->where('status', 'active')
            ->whereHas('relations', fn ($q) => $q->where('program_id', $program->id))
            ->get()
            ->filter(fn (Tool $tool) => $tool->visibleTo($role))
            ->values();
    }

    /** Tools attached to a specific session (or its stage). @return Collection<int,Tool> */
    public function sessionTools(Session $session, string $role): Collection
    {
        return Tool::query()
            ->where('status', 'active')
            ->whereHas('relations', function ($q) use ($session) {
                $q->where('session_id', $session->id)
                    ->when($session->stage_id, fn ($q) => $q->orWhere('stage_id', $session->stage_id));
            })
            ->get()
            ->filter(fn (Tool $tool) => $tool->visibleTo($role))
            ->values();
    }

    /** Program-wide surveys (not tied to a session). @return Collection<int,Survey> */
    public function programSurveys(Program $program, string $role): Collection
    {
        return Survey::query()
            ->where('status', 'active')
            ->where('program_id', $program->id)
            ->whereNull('session_id')
            ->get()
            ->filter(fn (Survey $survey) => $survey->visibleTo($role))
            ->values();
    }

    /** Surveys tied to a specific session. @return Collection<int,Survey> */
    public function sessionSurveys(Session $session, string $role): Collection
    {
        return Survey::query()
            ->where('status', 'active')
            ->where('session_id', $session->id)
            ->get()
            ->filter(fn (Survey $survey) => $survey->visibleTo($role))
            ->values();
    }
}
