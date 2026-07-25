<?php

namespace App\Filament\Resources\Assignments\Schemas;

use App\Models\Assignment;
use App\Models\Session;
use App\Services\ReportService;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * "Informe individual por dupla": the header of the dupla page. Shows the same
 * figures the coordinator reports list — mentor, mentee, current and expected
 * session, state, last activity and days behind — computed by ReportService so
 * a dupla can never read one way in the list and another way here.
 *
 * Read-only by construction: it is an infolist, so coordinators and the
 * read-only client see the follow-up without any edit affordance.
 */
class AssignmentInfolist
{
    /** Per-request memo: the page renders one record but many entries. */
    protected static array $reports = [];

    /** @return array<string,mixed> */
    protected static function report(Assignment $record): array
    {
        return static::$reports[$record->id] ??= ReportService::forUser(auth()->user())->duplaReport($record);
    }

    protected static function sessionLabel(?Session $session): ?string
    {
        return $session
            ? 'S'.$session->number.' · '.$session->getTranslation('name', app()->getLocale())
            : null;
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informe de la dupla')
                ->description('Seguimiento del acompañamiento: dónde está la dupla, dónde debería estar y cuándo se movió por última vez.')
                ->columns(3)
                ->schema([
                    TextEntry::make('facilitator.name')->label('Mentor')->placeholder('—'),
                    TextEntry::make('participant.name')->label('Mentee')->placeholder('—'),
                    TextEntry::make('program.name')->label('Programa')
                        ->getStateUsing(fn (Assignment $record) => $record->program?->getTranslation('name', app()->getLocale()))
                        ->placeholder('—'),

                    TextEntry::make('schedule_state')->label('Estado del cronograma')->badge()
                        ->getStateUsing(fn (Assignment $record) => static::report($record)['state_label'])
                        ->color(fn (Assignment $record) => match (true) {
                            static::report($record)['finished'] => 'info',
                            static::report($record)['category'] === 'fuera' => 'danger',
                            static::report($record)['category'] === 'sin_inicio' => 'gray',
                            default => 'success',
                        }),
                    TextEntry::make('status')->label('Estado de la dupla')->badge()
                        ->formatStateUsing(fn ($state) => match ($state) {
                            'active' => 'Activa', 'paused' => 'Pausada',
                            'finished' => 'Finalizada', 'cancelled' => 'Cancelada', default => $state,
                        })
                        ->color(fn ($state) => match ($state) {
                            'active' => 'success', 'paused' => 'warning',
                            'finished' => 'info', default => 'gray',
                        }),
                    TextEntry::make('progress')->label('Avance')
                        ->getStateUsing(function (Assignment $record) {
                            $r = static::report($record);

                            return $r['completed'].'/'.$r['total'].' sesiones · '.$r['percent'].'%';
                        }),

                    TextEntry::make('current_session')->label('Sesión actual')
                        ->getStateUsing(fn (Assignment $record) => static::sessionLabel(static::report($record)['current']))
                        ->placeholder('Programa finalizado'),
                    TextEntry::make('expected_session')->label('Sesión esperada')
                        ->helperText('Según las fechas del cronograma del programa.')
                        ->getStateUsing(fn (Assignment $record) => static::sessionLabel(static::report($record)['expected']))
                        ->placeholder('—'),
                    TextEntry::make('days_behind')->label('Días de atraso')
                        ->getStateUsing(fn (Assignment $record) => static::report($record)['days_behind'] ?: 'Sin atraso')
                        ->color(fn (Assignment $record) => static::report($record)['days_behind'] ? 'danger' : 'gray'),

                    TextEntry::make('last_activity')->label('Última actividad')
                        ->helperText('Sesión registrada, mensaje del buzón, archivo compartido o seguimiento del equipo.')
                        ->getStateUsing(function (Assignment $record) {
                            $r = static::report($record);

                            return $r['last_activity']
                                ? $r['last_activity']->format('d/m/Y H:i').' · '.$r['last_activity_type']
                                : null;
                        })
                        ->placeholder('Sin actividad registrada'),
                    TextEntry::make('mailbox')->label('Buzón')
                        ->getStateUsing(function (Assignment $record) {
                            $total = $record->messages()->count();
                            $unread = $record->messages()->whereNull('read_at')->count();

                            return $total.' '.str('mensaje')->plural($total).($unread ? " · {$unread} sin leer" : '');
                        }),
                    TextEntry::make('shared')->label('Archivos y seguimiento')
                        ->getStateUsing(function (Assignment $record) {
                            $files = $record->files()->count();
                            $followups = $record->followups()->count();

                            return $files.' '.str('archivo')->plural($files).' · '
                                .$followups.' '.str('seguimiento')->plural($followups);
                        }),
                ]),
        ]);
    }
}
