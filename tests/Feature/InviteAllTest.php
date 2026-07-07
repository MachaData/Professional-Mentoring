<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Mail\TemplatedMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class InviteAllTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_invite_all_emails_pending_mentors_and_mentees_only(): void
    {
        Mail::fake();

        $admin = User::where('email', 'admin@crosspartnersgroup.com')->firstOrFail();
        $org = $admin->organization_id;

        // Two pending, one already active (must be skipped).
        $pendingA = User::factory()->create(['organization_id' => $org, 'role' => User::ROLE_PARTICIPANT, 'invitation_status' => 'pending']);
        $pendingB = User::factory()->create(['organization_id' => $org, 'role' => User::ROLE_FACILITATOR, 'invitation_status' => 'sent']);
        $active = User::factory()->create(['organization_id' => $org, 'role' => User::ROLE_PARTICIPANT, 'invitation_status' => 'active']);

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->callAction('inviteAll')
            ->assertHasNoActionErrors();

        // The active account keeps its password (not re-invited).
        $active->refresh();
        $this->assertSame('active', $active->invitation_status);

        // Pending ones were invited.
        $this->assertSame('sent', $pendingA->fresh()->invitation_status);
        $this->assertSame('sent', $pendingB->fresh()->invitation_status);

        Mail::assertSent(TemplatedMail::class, fn ($m) => $m->hasTo($pendingA->email));
        Mail::assertNotSent(TemplatedMail::class, fn ($m) => $m->hasTo($active->email));
    }
}
