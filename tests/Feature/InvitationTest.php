<?php

namespace Tests\Feature;

use App\Mail\WelcomeInvitationMail;
use App\Models\Assignment;
use App\Models\Program;
use App\Models\User;
use App\Services\SessionRecordProvisioner;
use App\Services\UserInvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_invitation_sets_temp_password_and_logs_email(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'organization_id' => 1,
            'role' => User::ROLE_PARTICIPANT,
            'must_change_password' => false,
            'invitation_status' => 'pending',
        ]);
        $originalHash = $user->password;

        app(UserInvitationService::class)->invite($user);
        $user->refresh();

        $this->assertTrue($user->must_change_password);
        $this->assertSame('sent', $user->invitation_status);
        $this->assertNotSame($originalHash, $user->password);

        Mail::assertSent(WelcomeInvitationMail::class, fn ($m) => $m->hasTo($user->email));

        $this->assertDatabaseHas('email_logs', [
            'user_id' => $user->id,
            'type' => 'invitation',
            'status' => 'sent',
        ]);
    }

    public function test_first_login_forces_password_change(): void
    {
        $user = User::factory()->create([
            'organization_id' => 1,
            'role' => User::ROLE_FACILITATOR,
            'must_change_password' => true,
            'password' => Hash::make('temp1234'),
        ]);

        // Any portal page redirects to the change-password screen.
        $this->actingAs($user)->get('/mentor')->assertRedirect(route('password.change'));

        // After changing it, the flag clears.
        $this->actingAs($user)->post('/password/change', [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertRedirect();

        $this->assertFalse($user->fresh()->must_change_password);
    }

    public function test_creating_assignment_provisions_pending_records(): void
    {
        $program = Program::firstOrFail();
        $mentor = User::factory()->create(['organization_id' => 1, 'role' => User::ROLE_FACILITATOR]);
        $mentee = User::factory()->create(['organization_id' => 1, 'role' => User::ROLE_PARTICIPANT]);

        $assignment = Assignment::create([
            'organization_id' => 1,
            'program_id' => $program->id,
            'facilitator_id' => $mentor->id,
            'participant_id' => $mentee->id,
            'status' => Assignment::STATUS_ACTIVE,
        ]);

        $created = app(SessionRecordProvisioner::class)->forAssignment($assignment);

        $this->assertSame($program->sessions()->count(), $created);
        $this->assertSame($program->sessions()->count(), $assignment->records()->count());
    }
}
