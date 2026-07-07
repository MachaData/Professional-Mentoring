<?php

namespace Tests\Feature;

use App\Mail\TemplatedMail;
use App\Models\Organization;
use App\Models\User;
use App\Services\UserInvitationService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
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

        Notification::assertSentTo($user, ResetPassword::class);
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
}
