<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\SessionRecord;
use App\Services\ResourceResolver;
use Illuminate\Http\Request;

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

        $assignment->load(['participant', 'program.sessions.stage']);

        $records = $assignment->records()->get()->keyBy('session_id');
        $resolver = app(ResourceResolver::class);

        $sessions = $assignment->program->sessions->map(function ($session) use ($records, $assignment, $resolver) {
            $record = $records->get($session->id);

            return [
                'session' => $session,
                'record' => $record,
                'status' => $record ? $this->effectiveStatus($record, $assignment) : SessionRecord::STATUS_PENDING,
                'meeting_url' => $record?->meeting_url,
                'tools' => $resolver->sessionTools($session, 'facilitator'),
                'surveys' => $resolver->sessionSurveys($session, 'facilitator'),
            ];
        });

        // Program-wide resources for the right sidebar.
        $sidebarTools = $resolver->programTools($assignment->program, 'facilitator');
        $sidebarSurveys = $resolver->programSurveys($assignment->program, 'facilitator');

        return view('mentor.participant', compact('assignment', 'sessions', 'sidebarTools', 'sidebarSurveys'));
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
