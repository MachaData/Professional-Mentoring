<?php

namespace App\Filament\Resources\Assignments\Schemas;

use App\Models\Program;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class AssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Asignación (dupla)')
                    ->columns(2)
                    ->schema([
                        Select::make('program_id')->label('Programa')
                            ->relationship('program', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record?->getTranslation('name', app()->getLocale()))
                            ->searchable()->preload()->required()->live(),

                        Select::make('facilitator_id')
                            ->label('Facilitador')
                            ->options(fn () => self::usersByRole(User::ROLE_FACILITATOR))
                            ->searchable()->required(),

                        Select::make('participant_id')
                            ->label('Participante')
                            ->options(fn () => self::usersByRole(User::ROLE_PARTICIPANT))
                            ->searchable()->required()
                            ->helperText('Un participante solo puede tener un facilitador por programa.'),

                        Select::make('status')->label('Estado')
                            ->options([
                                'active' => 'Activa', 'paused' => 'Pausada',
                                'finished' => 'Finalizada', 'cancelled' => 'Cancelada',
                            ])->default('active')->required(),

                        DatePicker::make('start_date')->label('Fecha de inicio'),
                        DatePicker::make('end_date')->label('Fecha de fin'),
                        Textarea::make('notes')->label('Notas')->rows(2)->columnSpanFull(),
                    ]),
            ]);
    }

    /** @return array<int,string> */
    protected static function usersByRole(string $role): array
    {
        return User::query()
            ->where('role', $role)
            ->where('status', 'active')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }
}
