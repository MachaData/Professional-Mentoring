<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
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
            ->with(['participant', 'program', 'records'])
            ->get();

        $stats = [
            'participants' => $assignments->count(),
            'pending' => 0,
            'completed' => 0,
            'expired' => 0,
        ];

        foreach ($assignments as $assignment) {
            foreach ($assignment->records as $record) {
                $status = $this->effectiveStatus($record, $assignment);
                if ($status === SessionRecord::STATUS_COMPLETED) {
                    $stats['completed']++;
                } elseif ($status === SessionRecord::STATUS_EXPIRED) {
                    $stats['expired']++;
                } elseif (in_array($status, [SessionRecord::STATUS_PENDING, SessionRecord::STATUS_DRAFT], true)) {
                    $stats['pending']++;
                }
            }
        }

        return view('mentor.dashboard', compact('facilitator', 'assignments', 'stats'));
    }

    public function participant(Request $request, Assignment $assignment)
    {
        abort_unless($assignment->facilitator_id === $request->user()->id, 403);

        $assignment->load(['participant', 'program.sessions.stage', 'extraSessions.stage']);

        $records = $assignment->records()->get()->keyBy('session_id');
        $resolver = app(ResourceResolver::class);

        $sessions = $assignment->allSessions()->map(function ($session) use ($records, $assignment, $resolver) {
            $record = $records->get($session->id);

            return [
                'session' => $session,
                'record' => $record,
                'status' => $record ? $this->effectiveStatus($record, $assignment) : SessionRecord::STATUS_PENDING,
                'meeting_url' => $record?->meeting_url,
                'survey_url' => $session->survey_url,
                'tools' => $resolver->sessionTools($session, 'facilitator'),
            ];
        });

        // Program-wide materials for the right sidebar (surveys live per session).
        $sidebarTools = $resolver->programTools($assignment->program, 'facilitator');

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
            ->with(['participant:id,name', 'program.sessions', 'extraSessions', 'records'])
            ->get();

        // Calendar events: every dupla's sessions (program curriculum + extras),
        // deduped. Per-dupla progress lives in the table below.
        $sessions = $assignments
            ->flatMap(fn ($a) => $a->allSessions())
            ->unique('id')
            ->sortBy('sort_order')
            ->values();

        $month = $calendar->month($request->query('m'));
        $events = $calendar->events($sessions);

        // Basic indicators — one row per dupla.
        $rows = $assignments->map(function (Assignment $assignment) {
            $sessions = $assignment->allSessions();
            $recordsBySession = $assignment->records->keyBy('session_id');
            $total = $sessions->count();

            $completed = 0;
            $current = null;
            foreach ($sessions as $session) {
                $record = $recordsBySession->get($session->id);
                if ($record && $record->status === SessionRecord::STATUS_COMPLETED) {
                    $completed++;
                } elseif ($current === null) {
                    $current = $session;
                }
            }

            $overdue = $current && $current->end_date && $current->end_date->isPast();
            $state = $completed === $total ? 'done' : ($completed === 0 ? 'sin_inicio' : ($overdue ? 'fuera' : 'dentro'));

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
        abort_unless($record->facilitator_id === $request->user()->id, 403);

        $record->load('session.stage', 'assignment');
        $resolver = app(ResourceResolver::class);

        $tools = $resolver->sessionTools($record->session, 'facilitator');
        $surveyUrl = $record->session->survey_url;
        $assignment = $record->assignment;

        return view('mentor.register', compact('record', 'tools', 'surveyUrl', 'assignment'));
    }

    public function help(Request $request)
    {
        return view('mentor.help', ['facilitator' => $request->user()]);
    }

    /** A pending/draft record whose session window has passed is "expired". */
    protected function effectiveStatus(SessionRecord $record, Assignment $assignment): string
    {
        if (in_array($record->status, [SessionRecord::STATUS_PENDING, SessionRecord::STATUS_DRAFT], true)) {
            $session = $assignment->program->sessions->firstWhere('id', $record->session_id)
                ?? $record->session;
            if ($session?->end_date && $session->end_date->isPast()) {
                return SessionRecord::STATUS_EXPIRED;
            }
        }

        return $record->status;
    }
}
