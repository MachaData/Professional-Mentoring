<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Session;
use App\Models\SessionRecord;
use App\Services\CalendarService;
use App\Services\ResourceResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ParticipantController extends Controller
{
    public function dashboard(Request $request)
    {
        $participant = $request->user();

        $assignment = Assignment::query()
            ->where('participant_id', $participant->id)
            ->with(['facilitator', 'program.sessions.stage', 'extraSessions.stage', 'records.values.customField'])
            ->latest()
            ->first();

        $sessions = collect();
        $sidebarTools = new Collection;

        if ($assignment) {
            $resolver = app(ResourceResolver::class);
            $sidebarTools = $resolver->programTools($assignment->program, 'participant');

            $records = $assignment->records->keyBy('session_id');

            $sessions = $assignment->allSessions()
                ->where('visible_to_participant', true)
                ->map(function ($session) use ($records, $resolver) {
                    $record = $records->get($session->id);

                    // Only fields flagged visible to participant, from completed records.
                    $visible = collect();
                    if ($record && $record->status === SessionRecord::STATUS_COMPLETED) {
                        $visible = $record->values
                            ->filter(fn ($v) => $v->customField?->is_visible_to_participant)
                            ->map(fn ($v) => [
                                'label' => $v->customField->getTranslation('label', app()->getLocale()),
                                'value' => $v->value_text ?? $v->value_date?->format('d/m/Y') ?? $v->value_number,
                            ])
                            ->filter(fn ($row) => filled($row['value']))
                            ->values();
                    }

                    return [
                        'session' => $session,
                        'record' => $record,
                        'visible_values' => $visible,
                        'meeting_url' => $record?->meeting_url,
                        'survey_url' => $session->survey_url,
                        'tools' => $resolver->sessionTools($session, 'participant'),
                    ];
                });
        }

        return view('participant.dashboard', compact('participant', 'assignment', 'sessions', 'sidebarTools'));
    }

    public function space(Request $request)
    {
        $assignment = Assignment::query()
            ->where('participant_id', $request->user()->id)
            ->with('facilitator', 'program')
            ->latest()
            ->firstOrFail();

        return view('participant.space', compact('assignment'));
    }

    public function calendar(Request $request, CalendarService $calendar)
    {
        $participant = $request->user();

        $assignment = Assignment::query()
            ->where('participant_id', $participant->id)
            ->with(['program.sessions', 'extraSessions', 'records'])
            ->latest()
            ->first();

        $month = $calendar->month($request->query('m'));
        $events = [];
        $stats = ['total' => 0, 'completed' => 0, 'pending' => 0, 'expired' => 0];
        $next = null;

        if ($assignment) {
            $today = Carbon::today();
            $records = $assignment->records->keyBy('session_id');

            $sessions = $assignment->allSessions()
                ->where('visible_to_participant', true)
                ->values();

            $statusBySession = $sessions
                ->mapWithKeys(fn ($s) => [$s->id => $records->get($s->id)?->status ?? SessionRecord::STATUS_PENDING])
                ->all();

            $events = $calendar->events(
                $sessions,
                $statusBySession,
                fn ($session) => route('participant.session', $session),
            );

            $stats['total'] = $sessions->count();
            foreach ($sessions as $session) {
                $status = $statusBySession[$session->id];
                if ($status === SessionRecord::STATUS_COMPLETED) {
                    $stats['completed']++;
                } elseif ($session->end_date && $session->end_date->lt($today)) {
                    $stats['expired']++;
                } else {
                    $stats['pending']++;
                    // First upcoming, not-completed session with a start date.
                    if (! $next && $session->start_date) {
                        $next = $session;
                    }
                }
            }
        }

        [$prevUrl, $nextUrl, $todayUrl] = [
            route('participant.calendar', ['m' => $month->copy()->subMonthNoOverflow()->format('Y-m')]),
            route('participant.calendar', ['m' => $month->copy()->addMonthNoOverflow()->format('Y-m')]),
            route('participant.calendar'),
        ];

        return view('participant.calendar', compact('participant', 'assignment', 'month', 'events', 'stats', 'next', 'prevUrl', 'nextUrl', 'todayUrl'));
    }

    public function session(Request $request, Session $session)
    {
        $participant = $request->user();

        $assignment = Assignment::query()
            ->where('participant_id', $participant->id)
            ->where('program_id', $session->program_id)
            ->firstOrFail();

        abort_unless($session->visible_to_participant, 403);
        // Extra (per-dupla) sessions may only be opened by their own dupla.
        abort_unless($session->assignment_id === null || $session->assignment_id === $assignment->id, 403);

        $record = $assignment->records()->where('session_id', $session->id)->first();

        $visible = collect();
        if ($record && $record->status === SessionRecord::STATUS_COMPLETED) {
            $visible = $record->values()->with('customField')->get()
                ->filter(fn ($v) => $v->customField?->is_visible_to_participant)
                ->map(fn ($v) => [
                    'label' => $v->customField->getTranslation('label', app()->getLocale()),
                    'value' => $v->value_text ?? $v->value_date?->format('d/m/Y') ?? $v->value_number,
                ])
                ->filter(fn ($row) => filled($row['value']))
                ->values();
        }

        $tools = app(ResourceResolver::class)->sessionTools($session, 'participant');

        return view('participant.session', compact('participant', 'assignment', 'session', 'record', 'visible', 'tools'));
    }
}
