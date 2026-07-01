<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\SessionRecord;
use Illuminate\Http\Request;

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

        if ($assignment) {
            $records = $assignment->records->keyBy('session_id');

            $sessions = $assignment->program->sessions
                ->where('visible_to_participant', true)
                ->map(function ($session) use ($records) {
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
                    ];
                });
        }

        return view('participant.dashboard', compact('participant', 'assignment', 'sessions'));
    }
}
