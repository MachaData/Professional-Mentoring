<?php

namespace App\Filament\Resources\Assignments\Pages;

use App\Filament\Resources\Assignments\AssignmentResource;
use App\Services\SessionRecordProvisioner;
use Filament\Resources\Pages\CreateRecord;

class CreateAssignment extends CreateRecord
{
    protected static string $resource = AssignmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Denormalized tenant key from the selected program.
        if (empty($data['organization_id']) && ! empty($data['program_id'])) {
            $data['organization_id'] = \App\Models\Program::find($data['program_id'])?->organization_id;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Provision a pending session record for each session of the program.
        app(SessionRecordProvisioner::class)->forAssignment($this->record);
    }
}
