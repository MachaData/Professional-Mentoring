<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\AssignmentFollowup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Follow-up history per dupla: supervisors register contacts/actions with the
 * mentor or mentee; the read-only client can review the history.
 */
class FollowupTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_supervisor_can_log_a_followup_and_it_persists(): void
    {
        $assignment = Assignment::firstOrFail();
        $coordinator = User::where('email', 'coordinador@demo.test')->firstOrFail();

        $followup = AssignmentFollowup::create([
            'organization_id' => $assignment->organization_id,
            'assignment_id' => $assignment->id,
            'contacted_user_id' => $assignment->participant_id,
            'created_by' => $coordinator->id,
            'contact_type' => 'call',
            'status' => 'done',
            'comment' => 'Se llamó al mentee para dar seguimiento.',
            'contacted_at' => now(),
        ]);

        $this->assertDatabaseHas('assignment_followups', [
            'id' => $followup->id,
            'assignment_id' => $assignment->id,
            'created_by' => $coordinator->id,
            'contact_type' => 'call',
        ]);

        // Relations + labels
        $this->assertSame('Se llamó', $followup->contactTypeLabel());
        $this->assertSame('Realizado', $followup->statusLabel());
        $this->assertSame($assignment->participant_id, $followup->contactedUser->id);
        $this->assertSame($coordinator->id, $followup->author->id);
        $this->assertTrue($assignment->followups()->whereKey($followup->id)->exists());
    }

    public function test_write_permission_is_limited_to_supervisors(): void
    {
        $coordinator = User::where('email', 'coordinador@demo.test')->firstOrFail();

        $client = User::updateOrCreate(
            ['email' => 'cliente@demo.test'],
            [
                'name' => 'Cliente Demo',
                'role' => User::ROLE_CLIENT,
                'organization_id' => $coordinator->organization_id,
                'locale' => 'es', 'invitation_status' => 'active', 'status' => 'active',
                'password' => Hash::make('password'), 'email_verified_at' => now(),
            ]
        );

        // The RelationManager keys write access on canSuperviseDuplas().
        $this->assertTrue($coordinator->canSuperviseDuplas());
        $this->assertFalse($client->canSuperviseDuplas());
    }

    public function test_client_can_see_the_followup_history_on_the_dupla(): void
    {
        $assignment = Assignment::firstOrFail();
        $coordinator = User::where('email', 'coordinador@demo.test')->firstOrFail();

        AssignmentFollowup::create([
            'organization_id' => $assignment->organization_id,
            'assignment_id' => $assignment->id,
            'contacted_user_id' => $assignment->facilitator_id,
            'created_by' => $coordinator->id,
            'contact_type' => 'support',
            'status' => 'in_progress',
            'comment' => 'Se brindó soporte al mentor.',
            'contacted_at' => now(),
        ]);

        $client = User::updateOrCreate(
            ['email' => 'cliente2@demo.test'],
            [
                'name' => 'Cliente Observador',
                'role' => User::ROLE_CLIENT,
                'organization_id' => $assignment->organization_id,
                'locale' => 'es', 'invitation_status' => 'active', 'status' => 'active',
                'password' => Hash::make('password'), 'email_verified_at' => now(),
            ]
        );

        // The dupla detail page (which mounts the Seguimiento relation manager) loads.
        $this->actingAs($client)->get("/admin/assignments/{$assignment->getKey()}")->assertSuccessful();
    }
}
