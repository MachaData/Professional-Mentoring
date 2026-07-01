<?php

namespace App\Filament\Resources\EmailTemplates\Schemas;

use App\Services\TemplateRenderer;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;

class EmailTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        $currentUser = auth()->user();
        $vars = collect(TemplateRenderer::availableVariables())
            ->map(fn ($v) => '{{'.$v.'}}')->implode(', ');

        return $schema
            ->components([
                Section::make('Plantilla de correo')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Nombre')->required(),
                        Select::make('key')->label('Tipo')
                            ->options([
                                'invitation' => 'Invitación de acceso',
                                'welcome' => 'Bienvenida',
                                'reminder_session' => 'Recordatorio de sesión',
                                'reminder_register' => 'Recordatorio de registro',
                                'session_expired' => 'Sesión vencida',
                                'program_closing' => 'Cierre de programa',
                                'custom' => 'Personalizado',
                            ])->required(),
                        Select::make('organization_id')->label('Organización')
                            ->relationship('organization', 'name')->searchable()->preload()
                            ->default(fn () => $currentUser?->organization_id)
                            ->disabled(fn () => ! ($currentUser?->isSuperadmin() ?? false))
                            ->dehydrated()->required(),
                        Select::make('program_id')->label('Programa (opcional)')
                            ->relationship('program', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record?->getTranslation('name', app()->getLocale()))
                            ->searchable()->preload()
                            ->helperText('Vacío = aplica a toda la organización.'),
                        Select::make('status')->label('Estado')
                            ->options(['active' => 'Activa', 'inactive' => 'Inactiva'])->default('active')->required(),
                    ]),

                Section::make('Contenido (ES / EN)')
                    ->schema([
                        Text::make('Variables disponibles: '.$vars),
                        TextInput::make('subject.es')->label('Asunto (ES)')->required(),
                        Textarea::make('body.es')->label('Cuerpo (ES)')->rows(6)->required(),
                        TextInput::make('subject.en')->label('Subject (EN)')->required(),
                        Textarea::make('body.en')->label('Body (EN)')->rows(6)->required(),
                    ]),
            ]);
    }
}
