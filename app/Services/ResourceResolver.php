<?php

namespace App\Services;

use App\Models\Program;
use App\Models\Session;
use App\Models\Survey;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Resolves which tools and surveys a portal user can see. Program-level
 * resources feed the sidebar; session-level resources belong to each session
 * card. Tools are gated by role *and* by the user's business unit, so every
 * method takes the User rather than a bare role — that way no caller can
 * forget the second gate.
 */
class ResourceResolver
{
    /**
     * General program materials for the "Materiales" sidebar: mentor/mentee
     * guides, cronograma, welcome video, manual…
     *
     * Only rows scoped to the program *and nothing narrower* count. The admin
     * form puts Programa, Etapa and Sesión on the same repeater row, so a
     * session material is stored as {program_id, session_id} — matching on
     * program_id alone dumped every per-session file into the general list.
     * A material tied to a stage or a session belongs inside that session,
     * never here; to also publish it as general, give it its own row with
     * Etapa and Sesión empty.
     *
     * @return Collection<int,Tool>
     */
    public function programTools(Program $program, User $user): Collection
    {
        return $this->visibleTools(
            fn ($q) => $q->where('program_id', $program->id)
                ->whereNull('stage_id')
                ->whereNull('session_id'),
            $user,
        );
    }

    /**
     * Materials shown inside one session: those pinned to the session itself
     * plus those pinned to its stage. Program-general rows never match, so the
     * sidebar and the session card never show the same file twice.
     *
     * @return Collection<int,Tool>
     */
    public function sessionTools(Session $session, User $user): Collection
    {
        return $this->visibleTools(
            fn ($q) => $q->where(fn ($q) => $q->where('session_id', $session->id)
                ->when($session->stage_id, fn ($q) => $q->orWhere('stage_id', $session->stage_id))),
            $user,
        );
    }

    /**
     * Active tools whose relations match $scope, gated by role and business
     * unit. Ordered by category then id so the list groups predictably instead
     * of following insertion order.
     *
     * Within a category, materials written in the user's own language come
     * first, then the ones with no language, then the rest. Language only
     * reorders: a document in another language stays on the list, labelled, so
     * a bilingual dupla can still reach both versions.
     *
     * @return Collection<int,Tool>
     */
    protected function visibleTools(callable $scope, User $user): Collection
    {
        $language = $user->locale ?: config('app.locale');

        return Tool::query()
            ->where('status', 'active')
            ->whereHas('relations', $scope)
            ->orderByRaw('category is null, category')
            ->orderByRaw('case when language = ? then 0 when language is null then 1 else 2 end', [$language])
            ->orderBy('id')
            ->get()
            ->filter(fn (Tool $tool) => $tool->visibleToUser($user))
            ->values();
    }

    /** Program-wide surveys (not tied to a session). @return Collection<int,Survey> */
    public function programSurveys(Program $program, User $user): Collection
    {
        return Survey::query()
            ->where('status', 'active')
            ->where('program_id', $program->id)
            ->whereNull('session_id')
            ->get()
            ->filter(fn (Survey $survey) => $survey->visibleTo($user->role))
            ->values();
    }

    /** Surveys tied to a specific session. @return Collection<int,Survey> */
    public function sessionSurveys(Session $session, User $user): Collection
    {
        return Survey::query()
            ->where('status', 'active')
            ->where('session_id', $session->id)
            ->get()
            ->filter(fn (Survey $survey) => $survey->visibleTo($user->role))
            ->values();
    }
}
