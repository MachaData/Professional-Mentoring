<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Organization;
use App\Models\Program;
use App\Models\User;
use App\Services\SessionRecordProvisioner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * A demo mentor + mentee + assignment so the portals are usable right away.
 * Credentials: mentor@demo.test / mentee@demo.test, password "password".
 */
class DemoDuplaSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::where('slug', 'crosspartners-group')->first();
        $program = Program::where('slug', 'professional-mentoring-las-bambas')->first();
        if (! $org || ! $program) {
            return;
        }

        $mentor = User::updateOrCreate(
            ['email' => 'mentor@demo.test'],
            [
                'name' => 'Eduardo Lanao',
                'role' => User::ROLE_FACILITATOR,
                'organization_id' => $org->id,
                'company' => 'Las Bambas', 'position' => 'Gerente', 'area' => 'Operaciones',
                'locale' => 'es', 'invitation_status' => 'active', 'status' => 'active',
                'password' => Hash::make('password'), 'email_verified_at' => now(),
            ]
        );
        $mentor->syncRoles([User::ROLE_FACILITATOR]);

        $mentee = User::updateOrCreate(
            ['email' => 'mentee@demo.test'],
            [
                'name' => 'Cedrick Swan',
                'role' => User::ROLE_PARTICIPANT,
                'organization_id' => $org->id,
                'company' => 'Las Bambas', 'position' => 'Analista', 'area' => 'Finanzas',
                'locale' => 'es', 'invitation_status' => 'active', 'status' => 'active',
                'password' => Hash::make('password'), 'email_verified_at' => now(),
            ]
        );
        $mentee->syncRoles([User::ROLE_PARTICIPANT]);

        $assignment = Assignment::updateOrCreate(
            ['program_id' => $program->id, 'participant_id' => $mentee->id],
            [
                'organization_id' => $org->id,
                'facilitator_id' => $mentor->id,
                'start_date' => $program->start_date,
                'status' => Assignment::STATUS_ACTIVE,
            ]
        );

        app(SessionRecordProvisioner::class)->forAssignment($assignment);
    }
}
