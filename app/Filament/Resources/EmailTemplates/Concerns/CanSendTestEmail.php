<?php

namespace App\Filament\Resources\EmailTemplates\Concerns;

use App\Services\TestEmailSender;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Provides the "Enviar test" header action for the email template create/edit
 * pages, sending a sample-data render of the current (unsaved) form state.
 */
trait CanSendTestEmail
{
    protected function sendTestAction(): Action
    {
        return Action::make('sendTest')
            ->label('Enviar test')
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->color('gray')
            ->schema([
                TextInput::make('email')->label('Enviar a')->email()->required()
                    ->default(fn () => auth()->user()?->email),
                Select::make('locale')->label('Idioma')
                    ->options(['es' => 'Español', 'en' => 'English'])
                    ->default('es')->required(),
            ])
            ->action(function (array $data): void {
                app(TestEmailSender::class)->send(
                    $data['email'],
                    $data['locale'],
                    $this->form->getRawState(),
                );

                Notification::make()
                    ->success()
                    ->title('Correo de prueba enviado')
                    ->body('Se envió a '.$data['email'].'.')
                    ->send();
            });
    }
}
