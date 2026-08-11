<?php

namespace App\Services;

use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Password operations an admin performs *on behalf of* someone else from the
 * panel: setting a password directly (for people who cannot receive email) and
 * emailing a self-service recovery link (which never touches the current
 * password). Self-service recovery lives in Auth\PasswordResetController.
 */
class PasswordAdministrationService
{
    /**
     * Overwrite a user's password. Returns the plain password so the panel can
     * show it once to whoever is handing over the credentials.
     *
     * @param  string|null  $plain  null generates a readable temporary password
     */
    public function setPassword(User $user, ?string $plain = null, bool $mustChange = true): string
    {
        $plain ??= $this->generatePassword();

        $user->forceFill([
            'password' => Hash::make($plain),
            'must_change_password' => $mustChange,
            // Invalidate any "remember me" cookie still holding the old password.
            'remember_token' => Str::random(60),
        ])->save();

        return $plain;
    }

    /**
     * Email the account owner a recovery link. The current password keeps
     * working until they actually complete the reset.
     *
     * @return string One of the Password broker status constants
     */
    public function sendResetLink(User $user): string
    {
        $status = Password::sendResetLink(['email' => $user->email]);

        $this->log($user, $status);

        return $status;
    }

    /** Readable temporary password, same shape as the invitation one: "PM-Ax7Kq2". */
    protected function generatePassword(): string
    {
        return 'PM-'.Str::upper(Str::random(2)).Str::random(4);
    }

    protected function log(User $user, string $status): void
    {
        $sent = $status === Password::RESET_LINK_SENT;

        EmailLog::create([
            'organization_id' => $user->organization_id,
            'user_id' => $user->id,
            'type' => 'password_reset',
            'subject' => trans(
                'Restablece tu contraseña de :app',
                ['app' => config('app.name')],
                $user->locale ?: config('app.locale'),
            ),
            'recipient_email' => $user->email,
            'status' => $sent ? 'sent' : 'failed',
            'sent_at' => $sent ? now() : null,
            'error_message' => $sent ? null : $status,
        ]);
    }
}
