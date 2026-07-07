<?php

namespace App\Services;

use App\Mail\TemplatedMail;
use App\Mail\WelcomeInvitationMail;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Sends a user their welcome email with a freshly generated temporary password
 * and forces a password change on first login. Logs every send in email_logs.
 */
class UserInvitationService
{
    public function invite(User $user): void
    {
        $temporaryPassword = $this->generatePassword();

        $user->forceFill([
            'password' => Hash::make($temporaryPassword),
            'must_change_password' => true,
            'invitation_status' => 'sent',
            'invited_at' => now(),
        ])->save();

        [$mailable, $subject] = $this->buildMail($user, $temporaryPassword);

        try {
            Mail::to($user->email)->send($mailable);
            $this->log($user, $subject, 'sent');
        } catch (\Throwable $e) {
            $this->log($user, $subject, 'failed', $e->getMessage());
            throw $e;
        }
    }

    /**
     * Prefer the editable "invitation" email template; fall back to the built-in
     * markdown mailable when none is configured.
     *
     * @return array{0:Mailable,1:string}
     */
    protected function buildMail(User $user, string $temporaryPassword): array
    {
        $loginUrl = route('portal.login');
        $template = $user->organization_id
            ? EmailTemplate::resolve($user->organization_id, 'invitation')
            : null;

        if ($template) {
            $renderer = app(TemplateRenderer::class);
            $vars = $renderer->variables([
                'user' => $user,
                'platform_link' => $loginUrl,
            ]) + [
                'email' => $user->email,
                'temporary_password' => $temporaryPassword,
            ];
            $subject = $renderer->render($template->getTranslation('subject', app()->getLocale()), $vars);
            $body = $renderer->render($template->getTranslation('body', app()->getLocale()), $vars);
            $body = TemplateRenderer::toHtml($body); // normalize (rich HTML or legacy Markdown)
            $body = $this->ensureCredentials($body, $user->email, $temporaryPassword);

            return [new TemplatedMail($subject, $body, $template->headerImageUrl()), $subject];
        }

        $subject = __('Bienvenido a :app', ['app' => config('app.name')]);

        return [new WelcomeInvitationMail($user, $temporaryPassword, $loginUrl), $subject];
    }

    /**
     * Guarantee the invitation carries the login credentials, appending them when
     * the template copy doesn't already include the temporary password.
     */
    protected function ensureCredentials(string $body, string $email, string $temporaryPassword): string
    {
        if (str_contains($body, $temporaryPassword)) {
            return $body;
        }

        $block = app()->getLocale() === 'en'
            ? "<p><strong>Your access details:</strong></p><ul><li>Email: {$email}</li><li>Temporary password: {$temporaryPassword}</li></ul><p>You'll be asked to change it on your first login.</p>"
            : "<p><strong>Tus datos de acceso:</strong></p><ul><li>Correo: {$email}</li><li>Contraseña temporal: {$temporaryPassword}</li></ul><p>Se te pedirá cambiarla en tu primer ingreso.</p>";

        return $body.$block;
    }

    protected function generatePassword(): string
    {
        // Readable temporary password: e.g. "PM-Ax7Kq2".
        return 'PM-'.Str::upper(Str::random(2)).Str::random(4);
    }

    protected function log(User $user, string $subject, string $status, ?string $error = null): void
    {
        EmailLog::create([
            'organization_id' => $user->organization_id,
            'user_id' => $user->id,
            'type' => 'invitation',
            'subject' => $subject,
            'recipient_email' => $user->email,
            'status' => $status,
            'sent_at' => $status === 'sent' ? now() : null,
            'error_message' => $error,
        ]);
    }
}
