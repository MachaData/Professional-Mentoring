<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Session;
use App\Models\SessionRecord;
use App\Services\ResourceResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ParticipantController extends Controller
{
    public function dashboard(Request $request)
    {
        $participant = $request->user();

        $assignment = Assignment::query()
            ->where('participant_id', $participant->id)
            ->with(['facilitator', 'program.sessions.stage', 'records.values.customField'])
            ->latest()
            ->first();

        $sessions = collect();
        $sidebarTools = new Collection;

        if ($assignment) {
            $resolver = app(ResourceResolver::class);
            $sidebarTools = $resolver->programTools($assignment->program, 'participant');

            $records = $assignment->records->keyBy('session_id');

            $sessions = $assignment->program->sessions
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

    public function session(Request $request, Session $session)
    {
        $participant = $request->user();

        $assignment = Assignment::query()
            ->where('participant_id', $participant->id)
            ->where('program_id', $session->program_id)
            ->firstOrFail();

        abort_unless($session->visible_to_participant, 403);

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
