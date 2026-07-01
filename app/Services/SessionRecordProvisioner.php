<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\SessionRecord;

/**
 * Ensures every session of an assignment's program has a session_record row
 * (created as "pending"). Idempotent — safe to call whenever sessions change.
 */
class SessionRecordProvisioner
{
    public function forAssignment(Assignment $assignment): int
    {
        $created = 0;

        $sessions = $assignment->program->sessions()->get();

        foreach ($sessions as $session) {
            $record = SessionRecord::firstOrNew([
                'session_id' => $session->id,
                'assignment_id' => $assignment->id,
            ]);

            if (! $record->exists) {
                $record->fill([
                    'organization_id' => $assignment->organization_id,
                    'facilitator_id' => $assignment->facilitator_id,
                    'participant_id' => $assignment->participant_id,
                    'status' => SessionRecord::STATUS_PENDING,
                ])->save();
                $created++;
            }
        }

        return $created;
    }
}
