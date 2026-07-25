<?php

namespace App\Filament\Resources\Assignments\RelationManagers;

use App\Models\AssignmentFollowup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Follow-up history for a dupla. Supervisors (admins + coordinators) register
 * contacts and actions with the mentor or mentee; everyone else with panel
 * access — including the read-only client — can review the history.
 */
class FollowupsRelationManager extends RelationManager
{
    protected static string $relationship = 'followups';

    protected static ?string $title = 'Seguimiento';

    /** Only supervisors register/edit; clients and others are read-only. */
    public function isReadOnly(): bool
    {
        return ! (auth()->user()?->canSuperviseDuplas() ?? false);
    }

    /** Facilitator + participant of this dupla, for the "contacted person" field. */
    protected function contactablePeople(): array
    {
        $assignment = $this->getOwnerRecord();
        $people = [];

        if ($assignment->facilitator) {
            $people[$assignment->facilitator_id] = $assignment->facilitator->name.' (Mentor)';
        }
        if ($assignment->participant) {
            $people[$assignment->participant_id] = $assignment->participant->name.' (Participante)';
        }

        return $people;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('contacted_user_id')->label('Persona contactada')
                ->options($this->contactablePeople())
                ->placeholder('General / ambos'),
            Select::make('contact_type')->label('Tipo de contacto')
                ->options(AssignmentFollowup::CONTACT_TYPES)
                ->required()->native(false),
            Select::make('status')->label('Estado')
                ->options(AssignmentFollowup::STATUSES)
                ->default('done')->required()->native(false),
            DatePicker::make('contacted_at')->label('Fecha del contacto')
                ->native(false)->default(now()),
            Textarea::make('comment')->label('Comentario')
                ->rows(3)->columnSpanFull()
                ->placeholder('Detalle de la acción realizada (qué se hizo, qué respondió, próximos pasos…).'),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Historial de seguimiento')
            ->defaultSort('contacted_at', 'desc')
            ->columns([
                TextColumn::make('contacted_at')->label('Fecha')->date('d/m/Y')->placeholder('—')->sortable(),
                TextColumn::make('contactedUser.name')->label('Contactado')->placeholder('General'),
                TextColumn::make('contact_type')->label('Tipo')->badge()
                    ->formatStateUsing(fn ($state) => AssignmentFollowup::CONTACT_TYPES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'no_response' => 'danger',
                        'support', 'followup' => 'success',
                        'call', 'message', 'meeting' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('status')->label('Estado')->badge()
                    ->formatStateUsing(fn ($state) => AssignmentFollowup::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'done' => 'success', 'in_progress' => 'warning',
                        'no_response' => 'danger', default => 'gray',
                    }),
                TextColumn::make('comment')->label('Comentario')->limit(60)->wrap()->placeholder('—'),
                TextColumn::make('author.name')->label('Registró')->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make()->label('Registrar seguimiento')
                    ->mutateDataUsing(function (array $data): array {
                        $data['created_by'] = auth()->id();
                        $data['organization_id'] = $this->getOwnerRecord()->organization_id;

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make()->label('Editar'),
                DeleteAction::make()->label('Eliminar')
                    ->visible(fn () => auth()->user()?->canManageContent() ?? false),
            ]);
    }
}
