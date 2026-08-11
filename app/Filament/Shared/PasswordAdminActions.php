<?php

namespace App\Filament\Shared;

use App\Models\User;
use App\Services\PasswordAdministrationService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * Panel actions to help someone who lost access, shared by the users table and
 * the edit-user page.
 *
 * Two different tools on purpose:
 *  - resetPassword: the admin sets the password and reads it out. For people
 *    with no working inbox (a common case among mentees). Admins only.
 *  - sendResetLink: emails a recovery link and changes nothing until the person
 *    uses it. Safe enough for coordinators supporting their duplas.
 */
class PasswordAdminActions
{
    public static function resetPassword(): Action
    {
        return Action::make('resetPassword')
            ->label('Restablecer contraseña')
            ->icon('heroicon-o-lock-closed')
            ->color('danger')
            ->modalHeading('Restablecer contraseña')
            ->modalDescription(fn (User $record) => 'Se reemplazará la contraseña de '.$record->name
                .'. Se cerrarán sus sesiones recordadas y deberás entregarle la nueva clave.')
            ->modalSubmitActionLabel('Restablecer')
            ->visible(fn (User $record) => static::maySetPassword($record))
            ->form([
                Toggle::make('auto')
                    ->label('Generar una contraseña automáticamente')
                    ->helperText('Desactívalo para escribir tú mismo la contraseña.')
                    ->default(true)
                    ->live(),
                TextInput::make('password')
                    ->label('Nueva contraseña')
                    ->password()
                    ->revealable()
                    ->rule(PasswordRule::min(8))
                    ->confirmed()
                    ->required(fn (callable $get) => ! $get('auto'))
                    ->visible(fn (callable $get) => ! $get('auto')),
                TextInput::make('password_confirmation')
                    ->label('Confirmar contraseña')
                    ->password()
                    ->revealable()
                    ->required(fn (callable $get) => ! $get('auto'))
                    ->visible(fn (callable $get) => ! $get('auto')),
                Toggle::make('must_change')
                    ->label('Pedirle que la cambie al iniciar sesión')
                    ->default(true),
            ])
            ->action(function (User $record, array $data) {
                $auto = (bool) ($data['auto'] ?? true);

                $plain = app(PasswordAdministrationService::class)->setPassword(
                    $record,
                    $auto ? null : $data['password'],
                    (bool) ($data['must_change'] ?? true),
                );

                $notification = Notification::make()
                    ->title('Contraseña restablecida')
                    ->success();

                // Only echo what the admin doesn't already know.
                if ($auto) {
                    $notification->body("Nueva contraseña de {$record->name}: {$plain}")->persistent();
                }

                $notification->send();
            });
    }

    public static function sendResetLink(): Action
    {
        return Action::make('sendResetLink')
            ->label('Enviar enlace de recuperación')
            ->icon('heroicon-o-envelope-open')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Enviar enlace de recuperación')
            ->modalDescription(fn (User $record) => 'Se enviará a '.$record->email
                .' un correo con un enlace para crear una contraseña nueva. Su contraseña actual sigue funcionando hasta que lo use.')
            ->modalSubmitActionLabel('Enviar')
            ->visible(fn (User $record) => static::maySendResetLink($record))
            ->action(function (User $record) {
                $status = app(PasswordAdministrationService::class)->sendResetLink($record);

                match ($status) {
                    Password::RESET_LINK_SENT => Notification::make()
                        ->title('Enlace enviado a '.$record->email)
                        ->success()
                        ->send(),
                    Password::RESET_THROTTLED => Notification::make()
                        ->title('Ya se envió un enlace hace poco')
                        ->body('Espera un minuto antes de reenviarlo.')
                        ->warning()
                        ->send(),
                    default => Notification::make()
                        ->title('No se pudo enviar el enlace')
                        ->body(__($status))
                        ->danger()
                        ->send(),
                };
            });
    }

    /** Setting someone's password outright stays with the admins. */
    public static function maySetPassword(User $record): bool
    {
        $actor = auth()->user();

        return (bool) $actor?->canManageContent() && static::mayTouch($actor, $record);
    }

    /** Emailing a link changes nothing on its own, so coordinators may do it too. */
    public static function maySendResetLink(User $record): bool
    {
        $actor = auth()->user();

        return (bool) $actor?->canSuperviseDuplas() && filled($record->email) && static::mayTouch($actor, $record);
    }

    /** Only a superadmin may take over another superadmin's access. */
    protected static function mayTouch(User $actor, User $record): bool
    {
        return $record->role !== User::ROLE_SUPERADMIN || $actor->isSuperadmin();
    }
}
