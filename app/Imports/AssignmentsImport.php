<?php

namespace App\Imports;

use App\Models\Assignment;
use App\Models\Program;
use App\Models\User;
use App\Services\SessionRecordProvisioner;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Bulk-imports assignments from an Excel sheet with columns:
 * correo_facilitador, correo_participante, programa (slug), fecha_inicio
 *
 * A participant may only have one facilitator per program (enforced by upsert on
 * program + participant). Session records are provisioned for each new dupla.
 */
class AssignmentsImport implements ToCollection, WithHeadingRow
{
    public int $imported = 0;

    public function __construct(protected int $organizationId) {}

    public function collection(\Illuminate\Support\Collection $rows): void
    {
        $provisioner = app(SessionRecordProvisioner::class);

        foreach ($rows as $row) {
            $facilitator = User::where('email', trim((string) ($row['correo_facilitador'] ?? '')))->first();
            $participant = User::where('email', trim((string) ($row['correo_participante'] ?? '')))->first();
            $program = Program::where('organization_id', $this->organizationId)
                ->where('slug', trim((string) ($row['programa'] ?? '')))->first();

            if (! $facilitator || ! $participant || ! $program) {
                continue;
            }

            $assignment = Assignment::updateOrCreate(
                ['program_id' => $program->id, 'participant_id' => $participant->id],
                [
                    'organization_id' => $this->organizationId,
                    'facilitator_id' => $facilitator->id,
                    'start_date' => $row['fecha_inicio'] ?? $program->start_date,
                    'status' => Assignment::STATUS_ACTIVE,
                ],
            );

            $provisioner->forAssignment($assignment);
            $this->imported++;
        }
    }
}
