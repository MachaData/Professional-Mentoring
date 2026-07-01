<?php

namespace App\Services;

use App\Mail\WelcomeInvitationMail;
use App\Models\EmailLog;
use App\Models\User;
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

        $subject = __('Bienvenido a :app', ['app' => config('app.name')]);
        $mailable = new WelcomeInvitationMail($user, $temporaryPassword, route('portal.login'));

        try {
            Mail::to($user->email)->send($mailable);
            $this->log($user, $subject, 'sent');
        } catch (\Throwable $e) {
            $this->log($user, $subject, 'failed', $e->getMessage());
            throw $e;
        }
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
