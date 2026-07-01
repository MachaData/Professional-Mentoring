<?php

namespace App\Filament\Resources\Reminders\Schemas;

use App\Models\Session;
use App\Support\ResourceOptions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ReminderForm
{
    public static function configure(Schema $schema): Schema
    {
        $currentUser = auth()->user();

        return $schema
            ->components([
                Section::make('Recordatorio')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Nombre')->required()->columnSpanFull(),
                        Select::make('organization_id')->label('Organización')
                            ->relationship('organization', 'name')->searchable()->preload()
                            ->default(fn () => $currentUser?->organization_id)
                            ->disabled(fn () => ! ($currentUser?->isSuperadmin() ?? false))
                            ->dehydrated()->required(),
                        Select::make('program_id')->label('Programa')
                            ->relationship('program', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record?->getTranslation('name', app()->getLocale()))
                            ->searchable()->preload()->required()->live(),
                        Select::make('session_id')->label('Sesión (opcional)')
                            ->options(fn (Get $get) => Session::query()->where('program_id', $get('program_id'))
                                ->get()->mapWithKeys(fn (Session $s) => [$s->id => $s->getTranslation('name', app()->getLocale())])->toArray())
                            ->searchable()
                            ->helperText('Vacío = aplica a todas las sesiones del programa.'),
                        Select::make('recipient_type')->label('Destinatario')
                            ->options([
                                'facilitator' => 'Facilitador', 'participant' => 'Participante',
                                'both' => 'Ambos', 'admin' => 'Administrador',
                            ])->default('participant')->required(),
                    ]),

                Section::make('Programación')
                    ->columns(3)
                    ->schema([
                        Select::make('anchor')->label('Respecto a')
                            ->options(['session_start' => 'Inicio de sesión', 'session_end' => 'Fin de sesión'])
                            ->default('session_start')->required(),
                        Select::make('timing')->label('Cuándo')
                            ->options(['before' => 'Antes', 'after' => 'Después', 'on' => 'El mismo día'])
                            ->default('before')->required(),
                        TextInput::make('days')->label('Días')->numeric()->default(3)->required(),
                    ]),

                Section::make('Contenido')
                    ->schema([
                        Select::make('email_template_id')->label('Usar plantilla de correo')
                            ->relationship('template', 'name')->searchable()->preload()
                            ->helperText('Si eliges una plantilla, se usará su asunto y cuerpo. Si no, completa el texto abajo.'),
                        TextInput::make('subject.es')->label('Asunto (ES)'),
                        Textarea::make('message.es')->label('Mensaje (ES)')->rows(4),
                        TextInput::make('subject.en')->label('Subject (EN)'),
                        Textarea::make('message.en')->label('Message (EN)')->rows(4),
                        Select::make('status')->label('Estado')
                            ->options(['active' => 'Activo', 'inactive' => 'Inactivo'])->default('active')->required(),
                    ]),
            ]);
    }
}
