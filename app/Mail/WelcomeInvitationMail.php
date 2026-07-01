<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $temporaryPassword,
        public string $loginUrl,
    ) {}

    public function envelope(): Envelope
    {
        $program = config('app.name');

        return new Envelope(
            subject: __('Bienvenido a :app', ['app' => $program]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.welcome-invitation',
            with: [
                'name' => $this->user->name,
                'email' => $this->user->email,
                'temporaryPassword' => $this->temporaryPassword,
                'loginUrl' => $this->loginUrl,
                'appName' => config('app.name'),
            ],
        );
    }
}
