<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * The recovery email people get from "¿Olvidaste tu contraseña?" and from the
 * admin panel. Replaces Laravel's built-in English copy with the platform's
 * own, rendered in the recipient's language (see User::sendPasswordResetNotification).
 */
class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Restablece tu contraseña de :app', ['app' => config('app.name')]))
            ->greeting(__('Hola :name', ['name' => $notifiable->name]))
            ->line(__('Recibimos una solicitud para restablecer la contraseña de tu cuenta.'))
            ->action(__('Crear una nueva contraseña'), $this->resetUrl($notifiable))
            ->line(__('El enlace vence en :minutes minutos.', ['minutes' => $this->expiresInMinutes()]))
            ->line(__('Si no solicitaste el cambio, ignora este correo: tu contraseña seguirá siendo la misma.'));
    }

    /** Link lifetime configured for the active password broker. */
    protected function expiresInMinutes(): int
    {
        return (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);
    }
}
