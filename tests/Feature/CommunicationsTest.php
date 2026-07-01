<?php

namespace Tests\Feature;

use App\Mail\TemplatedMail;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\Program;
use App\Models\Session;
use App\Services\TemplateRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CommunicationsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_renderer_interpolates_variables(): void
    {
        $renderer = app(TemplateRenderer::class);
        $program = Program::firstOrFail();
        $session = $program->sessions()->firstOrFail();

        $vars = $renderer->variables(['program' => $program, 'session' => $session]);
        $out = $renderer->render('Sesión {{session_name}} de {{program_name}}', $vars);

        $this->assertStringContainsString($session->getTranslation('name', 'es'), $out);
        $this->assertStringContainsString($program->getTranslation('name', 'es'), $out);
    }

    public function test_template_resolution_prefers_program_scope(): void
    {
        $program = Program::firstOrFail();
        $orgId = $program->organization_id;

        EmailTemplate::create([
            'organization_id' => $orgId, 'program_id' => $program->id, 'key' => 'welcome',
            'name' => 'Override', 'subject' => ['es' => 'X'], 'body' => ['es' => 'Y'], 'status' => 'active',
        ]);

        $resolved = EmailTemplate::resolve($orgId, 'welcome', $program->id);
        $this->assertSame($program->id, $resolved->program_id);
    }

    public function test_dispatch_sends_due_reminders_once(): void
    {
        Mail::fake();

        // S1 starts 2026-06-23; a "3 days before start" reminder is due 2026-06-20.
        $this->artisan('reminders:dispatch', ['--date' => '2026-06-20'])->assertSuccessful();

        Mail::assertSent(TemplatedMail::class, 2); // both recipients of the demo dupla
        $this->assertSame(2, EmailLog::where('type', 'reminder')->count());

        // Running again does not resend.
        $this->artisan('reminders:dispatch', ['--date' => '2026-06-20'])->assertSuccessful();
        $this->assertSame(2, EmailLog::where('type', 'reminder')->count());
    }

    public function test_invitation_uses_editable_template_when_present(): void
    {
        Mail::fake();

        $user = \App\Models\User::factory()->create([
            'organization_id' => Program::firstOrFail()->organization_id,
            'role' => \App\Models\User::ROLE_PARTICIPANT,
        ]);

        app(\App\Services\UserInvitationService::class)->invite($user);

        // The seeded "invitation" template drives a TemplatedMail (not the fallback).
        Mail::assertSent(TemplatedMail::class, fn ($m) => $m->hasTo($user->email));
    }
}
