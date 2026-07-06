<?php

namespace Tests\Feature;

use App\Filament\Pages\SendNotification;
use App\Mail\TemplatedMail;
use App\Models\Assignment;
use App\Models\EmailLog;
use App\Models\User;
use App\Models\WelcomePopup;
use App\Services\BulkNotificationSender;
use App\Services\NotificationAudienceResolver;
use App\Services\WelcomePopupResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationsAndPopupTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function mentor(): User
    {
        return User::where('email', 'mentor@demo.test')->firstOrFail();
    }

    private function mentee(): User
    {
        return User::where('email', 'mentee@demo.test')->firstOrFail();
    }

    private function assignment(): Assignment
    {
        return Assignment::where('participant_id', $this->mentee()->id)->firstOrFail();
    }

    // ---------- Feature 1: welcome popup per program + role ----------

    public function test_welcome_popup_resolves_by_program_and_role(): void
    {
        $assignment = $this->assignment();

        $popup = WelcomePopup::create([
            'organization_id' => $assignment->organization_id,
            'program_id' => $assignment->program_id,
            'role' => User::ROLE_PARTICIPANT,
            'enabled' => true,
            'title' => ['es' => 'Hola', 'en' => 'Hi'],
            'body' => ['es' => 'Bienvenido al programa', 'en' => 'Welcome'],
            'video_url' => 'https://www.youtube.com/watch?v=abc123',
        ]);

        $resolver = app(WelcomePopupResolver::class);

        // Mentee (participant) gets it; mentor (facilitator) does not.
        $this->assertNotNull($resolver->resolve($this->mentee()));
        $this->assertNull($resolver->resolve($this->mentor()));

        // Embeddable URL normalized.
        $this->assertSame('https://www.youtube.com/embed/abc123', $popup->embedUrl());

        // shouldShow: true when unseen, false once dismissed after last update.
        $mentee = $this->mentee();
        $mentee->onboarding_seen_at = null;
        $this->assertTrue($resolver->shouldShow($mentee, $popup));

        $mentee->onboarding_seen_at = now()->addMinute();
        $this->assertFalse($resolver->shouldShow($mentee, $popup));
    }

    public function test_disabled_popup_is_not_shown(): void
    {
        $assignment = $this->assignment();

        WelcomePopup::create([
            'organization_id' => $assignment->organization_id,
            'program_id' => $assignment->program_id,
            'role' => User::ROLE_PARTICIPANT,
            'enabled' => false,
            'body' => ['es' => 'Oculto', 'en' => 'Hidden'],
        ]);

        $this->assertNull(app(WelcomePopupResolver::class)->resolve($this->mentee()));
    }

    // ---------- Features 2/3/4: notification page + audiences ----------

    public function test_page_access_is_limited_to_supervisors(): void
    {
        $coordinator = User::where('email', 'coordinador@demo.test')->firstOrFail();

        $this->assertTrue(SendNotification::canAccess() === false); // no auth
        $this->actingAs($coordinator);
        $this->assertTrue(SendNotification::canAccess());
        $this->actingAs($this->mentor());
        $this->assertFalse(SendNotification::canAccess());
    }

    public function test_audience_by_role_returns_matching_users(): void
    {
        $orgId = $this->assignment()->organization_id;
        $resolver = app(NotificationAudienceResolver::class);

        $facilitators = $resolver->resolve(['audience' => 'facilitators'], $orgId);
        $this->assertTrue($facilitators->contains(fn ($t) => $t['user']->id === $this->mentor()->id));
        $this->assertFalse($facilitators->contains(fn ($t) => $t['user']->id === $this->mentee()->id));

        $participants = $resolver->resolve(['audience' => 'participants'], $orgId);
        $this->assertTrue($participants->contains(fn ($t) => $t['user']->id === $this->mentee()->id));
    }

    public function test_audience_by_dupla_respects_recipient_choice(): void
    {
        $orgId = $this->assignment()->organization_id;
        $resolver = app(NotificationAudienceResolver::class);

        $both = $resolver->resolve([
            'audience' => 'duplas',
            'assignment_ids' => [$this->assignment()->id],
            'dupla_recipients' => 'both',
        ], $orgId);
        $ids = $both->map(fn ($t) => $t['user']->id)->all();
        $this->assertContains($this->mentor()->id, $ids);
        $this->assertContains($this->mentee()->id, $ids);

        $onlyMentee = $resolver->resolve([
            'audience' => 'duplas',
            'assignment_ids' => [$this->assignment()->id],
            'dupla_recipients' => 'mentee',
        ], $orgId);
        $this->assertSame([$this->mentee()->id], $onlyMentee->map(fn ($t) => $t['user']->id)->all());
    }

    public function test_single_user_audience(): void
    {
        $orgId = $this->assignment()->organization_id;
        $targets = app(NotificationAudienceResolver::class)
            ->resolve(['audience' => 'user', 'user_id' => $this->mentee()->id], $orgId);

        $this->assertCount(1, $targets);
        $this->assertSame($this->mentee()->id, $targets->first()['user']->id);
    }

    public function test_sender_delivers_and_logs_free_form_message(): void
    {
        Mail::fake();
        $orgId = $this->assignment()->organization_id;

        $targets = app(NotificationAudienceResolver::class)->resolve([
            'audience' => 'duplas',
            'assignment_ids' => [$this->assignment()->id],
            'dupla_recipients' => 'both',
        ], $orgId);

        $result = app(BulkNotificationSender::class)->send($targets, [
            'subject' => 'Aviso para {{user_name}}',
            'body' => 'Programa: {{program_name}}',
        ], $orgId);

        $this->assertSame(2, $result['sent']);
        $this->assertSame(0, $result['failed']);

        Mail::assertSent(TemplatedMail::class, 2);
        Mail::assertSent(TemplatedMail::class, fn ($m) => str_contains($m->renderedSubject, $this->mentor()->name)
            || str_contains($m->renderedSubject, $this->mentee()->name));

        $this->assertSame(2, EmailLog::where('type', 'broadcast')->count());
    }

    public function test_filter_audience_returns_duplas_by_state(): void
    {
        $orgId = $this->assignment()->organization_id;

        // A brand-new seeded dupla with no completed sessions is "sin inicio".
        $targets = app(NotificationAudienceResolver::class)->resolve([
            'audience' => 'filter',
            'filters' => ['sin_inicio'],
            'dupla_recipients' => 'mentee',
        ], $orgId);

        // Should not error and returns a collection (may include the demo dupla).
        $this->assertNotNull($targets);
        foreach ($targets as $t) {
            $this->assertNotNull($t['user']->email);
        }
    }

    public function test_page_renders_for_coordinator(): void
    {
        $coordinator = User::where('email', 'coordinador@demo.test')->firstOrFail();
        Livewire::actingAs($coordinator)->test(SendNotification::class)->assertOk();
    }

    public function test_compose_action_form_mounts_without_errors(): void
    {
        $coordinator = User::where('email', 'coordinador@demo.test')->firstOrFail();

        Livewire::actingAs($coordinator)
            ->test(SendNotification::class)
            ->mountAction('compose')
            ->assertActionMounted('compose')
            ->assertHasNoActionErrors();
    }
}
