<?php

namespace App\Providers;

use App\Mail\Transport\BrevoApiTransport;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Send via Brevo's HTTP API (port 443) — avoids blocked outbound SMTP.
        Mail::extend('brevo', fn () => new BrevoApiTransport((string) config('services.brevo.key')));

        // Route replies to a real inbox even when sending from a no-reply address.
        if ($replyTo = config('mail.reply_to.address')) {
            Mail::alwaysReplyTo($replyTo, config('mail.reply_to.name'));
        }

        // Spanish, branded password-reset email.
        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject(__('Restablece tu contraseña'))
                ->greeting(__('Hola'))
                ->line(__('Recibimos una solicitud para restablecer la contraseña de tu cuenta.'))
                ->action(__('Restablecer contraseña'), $url)
                ->line(__('El enlace vence en :count minutos.', ['count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60)]))
                ->line(__('Si no solicitaste este cambio, puedes ignorar este correo.'));
        });
    }
}
