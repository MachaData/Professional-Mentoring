<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Session;
use App\Models\SessionRecord;
use App\Services\CalendarService;
use App\Services\ResourceResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MentorController extends Controller
{
    public function dashboard(Request $request)
    {
        $facilitator = $request->user();

        $assignments = Assignment::query()
            ->where('facilitator_id', $facilitator->id)
            ->with(['participant', 'program.sessions.stage', 'extraSessions.stage', 'records'])
            ->get();

        $stats = [
            'participants' => $assignments->count(),
            'pending' => 0,
            'completed' => 0,
            'expired' => 0,
        ];

        $progress = [];

        foreach ($assignments as $assignment) {
            $sessions = $assignment->allSessions()->keyBy('id');
            $total = 0;
            $done = 0;

            foreach ($assignment->records as $record) {
                $session = $sessions->get($record->session_id);

                // Hidden from the mentor means it does not exist for them.
                if (! $session || ! $session->isVisibleTo($facilitator->role)) {
                    continue;
                }

                // The progress bar spans the whole visible curriculum. A locked
                // session is work still ahead, not work that disappeared —
                // dropping it would push the bar to 100% mid-programme.
                $total++;
                $status = $this->effectiveStatus($record, $session);
                if ($status === SessionRecord::STATUS_COMPLETED) {
                    $done++;
                }

                // The counters above the bar are about what the mentor can act on
                // right now, so a locked session contributes to none of them.
                if ($session->isLocked()) {
                    continue;
                }

                if ($status === SessionRecord::STATUS_COMPLETED) {
                    $stats['completed']++;
                } elseif ($status === SessionRecord::STATUS_EXPIRED) {
                    $stats['expired']++;
                } elseif (in_array($status, [SessionRecord::STATUS_PENDING, SessionRecord::STATUS_DRAFT], true)) {
                    $stats['pending']++;
                }
            }

            $progress[$assignment->id] = [
                'total' => $total,
                'done' => $done,
                'percent' => $total ? (int) round($done / $total * 100) : 0,
            ];
        }

        return view('mentor.dashboard', compact('facilitator', 'assignments', 'stats', 'progress'));
    }

    public function participant(Request $request, Assignment $assignment)
    {
        $facilitator = $request->user();
        abort_unless($assignment->facilitator_id === $facilitator->id, 403);

        $assignment->load(['participant', 'program.sessions.stage', 'extraSessions.stage']);

        $records = $assignment->records()->get()->keyBy('session_id');
        $resolver = app(ResourceResolver::class);

        $sessions = $assignment->allSessions()
            ->filter(fn (Session $session) => $session->isVisibleTo($facilitator->role))
            ->map(function (Session $session) use ($records, $resolver, $facilitator) {
                $record = $records->get($session->id);
                $locked = $session->isLocked();

                // A locked session shows as "Próximamente": no resources, no
                // meeting link, no registration.
                return [
                    'session' => $session,
                    'record' => $record,
                    'locked' => $locked,
                    'status' => $record ? $this->effectiveStatus($record, $session) : SessionRecord::STATUS_PENDING,
                    'meeting_url' => $locked ? null : $record?->meeting_url,
                    'survey_url' => $locked ? null : $session->survey_url,
                    'tools' => $locked ? collect() : $resolver->sessionTools($session, $facilitator),
                ];
            })
            ->values();

        // Program-wide materials for the right sidebar (surveys live per session).
        $sidebarTools = $resolver->programTools($assignment->program, $facilitator);

        return view('mentor.participant', compact('assignment', 'sessions', 'sidebarTools'));
    }

    public function space(Request $request, Assignment $assignment)
    {
        abort_unless($assignment->facilitator_id === $request->user()->id, 403);

        $assignment->load('participant', 'program');

        return view('mentor.space', compact('assignment'));
    }

    public function calendar(Request $request, CalendarService $calendar)
    {
        $facilitator = $request->user();

        $assignments = Assignment::query()
            ->where('facilitator_id', $facilitator->id)
            ->with(['participant:id,name', 'program.sessions.stage', 'extraSessions.stage', 'records'])
            ->get();

        // Calendar events: every dupla's sessions (program curriculum + extras)
        // that this mentor may see, deduped. Per-dupla progress lives in the
        // table below.
        $sessions = $assignments
            ->flatMap(fn ($a) => $a->allSessions())
            ->unique('id')
            ->filter(fn (Session $s) => $s->isVisibleTo($facilitator->role))
            ->sortBy('sort_order')
            ->values();

        $month = $calendar->month($request->query('m'));
        $events = $calendar->events($sessions);

        // Basic indicators — one row per dupla.
        $rows = $assignments->map(function (Assignment $assignment) use ($facilitator) {
            $sessions = $assignment->allSessions()
                ->filter(fn (Session $s) => $s->isVisibleTo($facilitator->role))
                ->values();
            $recordsBySession = $assignment->records->keyBy('session_id');
            $total = $sessions->count();

            $completed = 0;
            $current = null;
            foreach ($sessions as $session) {
                $record = $recordsBySession->get($session->id);
                if ($record && $record->status === SessionRecord::STATUS_COMPLETED) {
                    $completed++;
                } elseif ($current === null && ! $session->isLocked()) {
                    // A locked session is never the mentor's "current" one — they
                    // cannot act on it, so it must not drive the overdue flag.
                    $current = $session;
                }
            }

            $overdue = $current && $current->end_date && $current->end_date->isPast();
            // Guard the empty case: without it 0 === 0 reads as "Finalizado" for a
            // dupla whose sessions are all still hidden.
            $state = match (true) {
                $total === 0, $completed === 0 => 'sin_inicio',
                $completed === $total => 'done',
                $overdue => 'fuera',
                default => 'dentro',
            };

            return [
                'assignment' => $assignment,
                'total' => $total,
                'completed' => $completed,
                'percent' => $total ? (int) round($completed / $total * 100) : 0,
                'current' => $current,
                'state' => $state,
            ];
        });

        [$prevUrl, $nextUrl, $todayUrl] = $this->monthLinks('mentor.calendar', $month);

        return view('mentor.calendar', compact('facilitator', 'month', 'events', 'rows', 'prevUrl', 'nextUrl', 'todayUrl'));
    }

    /** @return array{0:string,1:string,2:string} prev, next, today month URLs */
    protected function monthLinks(string $route, Carbon $month): array
    {
        return [
            route($route, ['m' => $month->copy()->subMonthNoOverflow()->format('Y-m')]),
            route($route, ['m' => $month->copy()->addMonthNoOverflow()->format('Y-m')]),
            route($route),
        ];
    }

    public function register(Request $request, SessionRecord $record)
    {
        $facilitator = $request->user();
        abort_unless($record->facilitator_id === $facilitator->id, 403);

        $record->load('session.stage', 'assignment');

        abort_unless($record->session->isEnterableBy($facilitator), 403);

        $resolver = app(ResourceResolver::class);

        $tools = $resolver->sessionTools($record->session, $facilitator);
        $surveyUrl = $record->session->survey_url;
        $assignment = $record->assignment;

        return view('mentor.register', compact('record', 'tools', 'surveyUrl', 'assignment'));
    }

    public function help(Request $request)
    {
        return view('mentor.help', ['facilitator' => $request->user()]);
    }

    /** A pending/draft record whose session window has passed is "expired". */
    protected function effectiveStatus(SessionRecord $record, ?Session $session): string
    {
        if (in_array($record->status, [SessionRecord::STATUS_PENDING, SessionRecord::STATUS_DRAFT], true)) {
            $session ??= $record->session;
            if ($session?->end_date && $session->end_date->isPast()) {
                return SessionRecord::STATUS_EXPIRED;
            }
        }

        return $record->status;
    }
}
