<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Shared\PasswordAdminActions;
use App\Mail\TemplatedMail;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Services\PasswordAdministrationService;
use App\Services\UserInvitationService;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    // ---- Temporary password in the invitation email ----

    public function test_invitation_email_includes_the_temporary_password(): void
    {
        Mail::fake();

        $org = Organization::first();
        $user = User::factory()->create([
            'organization_id' => $org->id,
            'role' => User::ROLE_PARTICIPANT,
            'email' => 'nuevo@ejemplo.com',
        ]);

        app(UserInvitationService::class)->invite($user);

        Mail::assertSent(TemplatedMail::class, function (TemplatedMail $mail) {
            // The generated password looks like "PM-XXxxxx"; assert the block is present.
            return str_contains($mail->renderedBody, 'nuevo@ejemplo.com')
                && str_contains($mail->renderedBody, 'PM-');
        });
    }

    // ---- Forgot / reset password ----

    public function test_forgot_password_page_renders_and_login_links_to_it(): void
    {
        $this->get('/password/forgot')->assertOk()->assertSee('Olvidaste tu contraseña', false);
        $this->get('/login')->assertOk()->assertSee(route('password.request'), false);
    }

    public function test_reset_link_is_emailed(): void
    {
        Notification::fake();

        $user = User::where('email', 'mentee@demo.test')->firstOrFail();

        $this->post('/password/forgot', ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_user_can_reset_their_password(): void
    {
        $user = User::where('email', 'mentee@demo.test')->firstOrFail();
        $user->forceFill(['must_change_password' => true])->save();

        $token = Password::createToken($user);

        $response = $this->post('/password/reset', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NuevaClave123',
            'password_confirmation' => 'NuevaClave123',
        ]);

        $response->assertRedirect(route('portal.login'));
        $response->assertSessionHas('status');

        $user->refresh();
        $this->assertTrue(Hash::check('NuevaClave123', $user->password));
        $this->assertFalse((bool) $user->must_change_password);
    }

    public function test_reset_page_renders_with_token(): void
    {
        $this->get('/password/reset/some-token?email=mentee@demo.test')
            ->assertOk()
            ->assertSee('Crea tu nueva contraseña', false)
            ->assertSee('some-token', false);
    }

    public function test_recovery_email_uses_the_platform_copy_in_spanish(): void
    {
        $user = User::where('email', 'mentee@demo.test')->firstOrFail();
        $user->forceFill(['locale' => 'es'])->save();

        $mail = (new ResetPasswordNotification('some-token'))->toMail($user);

        $this->assertStringContainsString('Restablece tu contraseña', $mail->subject);
        $this->assertSame('Crear una nueva contraseña', $mail->actionText);
        $this->assertStringContainsString('/password/reset/some-token', $mail->actionUrl);
    }

    // ---- Admin-driven recovery (panel) ----

    public function test_admin_can_reset_a_user_password_from_the_panel(): void
    {
        $admin = User::where('email', 'admin@crosspartnersgroup.com')->firstOrFail();
        $target = User::where('email', 'mentee@demo.test')->firstOrFail();
        $target->forceFill(['must_change_password' => false])->save();
        $originalHash = $target->password;

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->callAction(
                TestAction::make('resetPassword')->table($target),
                ['auto' => true, 'must_change' => true],
            )
            ->assertHasNoActionErrors();

        $target->refresh();
        $this->assertNotSame($originalHash, $target->password);
        $this->assertTrue((bool) $target->must_change_password);
    }

    public function test_admin_can_set_an_explicit_password_without_forcing_a_change(): void
    {
        $target = User::where('email', 'mentee@demo.test')->firstOrFail();

        $plain = app(PasswordAdministrationService::class)
            ->setPassword($target, 'ClaveElegida123', mustChange: false);

        $target->refresh();
        $this->assertSame('ClaveElegida123', $plain);
        $this->assertTrue(Hash::check('ClaveElegida123', $target->password));
        $this->assertFalse((bool) $target->must_change_password);
    }

    public function test_admin_can_send_a_recovery_link_from_the_panel_and_it_is_logged(): void
    {
        Notification::fake();

        $admin = User::where('email', 'admin@crosspartnersgroup.com')->firstOrFail();
        $target = User::where('email', 'mentee@demo.test')->firstOrFail();
        $originalHash = $target->password;

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->callAction(TestAction::make('sendResetLink')->table($target))
            ->assertHasNoActionErrors();

        Notification::assertSentTo($target, ResetPasswordNotification::class);

        // The link alone must not disturb the account's current credentials.
        $this->assertSame($originalHash, $target->fresh()->password);

        $this->assertDatabaseHas('email_logs', [
            'user_id' => $target->id,
            'type' => 'password_reset',
            'status' => 'sent',
        ]);
    }

    public function test_edit_user_page_exposes_both_recovery_actions(): void
    {
        $admin = User::where('email', 'admin@crosspartnersgroup.com')->firstOrFail();
        $target = User::where('email', 'mentee@demo.test')->firstOrFail();

        $this->actingAs($admin)
            ->get("/admin/users/{$target->getKey()}/edit")
            ->assertSuccessful()
            ->assertSee('Restablecer contraseña')
            ->assertSee('Enviar enlace de recuperación');
    }

    public function test_coordinator_may_send_links_but_not_overwrite_passwords(): void
    {
        $coordinator = User::where('email', 'coordinador@demo.test')->firstOrFail();
        $target = User::where('email', 'mentee@demo.test')->firstOrFail();

        $this->actingAs($coordinator);

        $this->assertTrue(PasswordAdminActions::maySendResetLink($target));
        $this->assertFalse(PasswordAdminActions::maySetPassword($target));
    }

    public function test_org_admin_cannot_take_over_a_superadmin_account(): void
    {
        $admin = User::where('email', 'admin@crosspartnersgroup.com')->firstOrFail();
        $superadmin = User::where('email', 'superadmin@pro-mentoring.com')->firstOrFail();

        $this->actingAs($admin);

        $this->assertFalse(PasswordAdminActions::maySetPassword($superadmin));
        $this->assertFalse(PasswordAdminActions::maySendResetLink($superadmin));
    }
}
