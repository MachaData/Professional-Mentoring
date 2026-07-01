<?php

namespace App\Exports;

use App\Models\Program;
use App\Models\SessionRecord;
use App\Services\ReportService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProgramProgressExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(protected Program $program) {}

    public function collection()
    {
        return SessionRecord::query()
            ->whereIn('assignment_id', $this->program->assignments()->pluck('id'))
            ->with(['facilitator', 'participant', 'session'])
            ->get();
    }

    public function headings(): array
    {
        return ['Facilitador', 'Participante', 'Sesión #', 'Sesión', 'Estado', 'Fecha real', 'Registrada'];
    }

    /** @param SessionRecord $record */
    public function map($record): array
    {
        $locale = app()->getLocale();
        $status = app(ReportService::class)->effectiveStatus($record);

        return [
            $record->facilitator?->name,
            $record->participant?->name,
            $record->session?->number,
            $record->session?->getTranslation('name', $locale),
            $this->statusLabel($status),
            $record->real_session_date?->format('d/m/Y'),
            $record->submitted_at?->format('d/m/Y H:i'),
        ];
    }

    protected function statusLabel(string $status): string
    {
        return match ($status) {
            'completed' => 'Completada', 'pending' => 'Pendiente', 'draft' => 'Borrador',
            'expired' => 'Vencida', 'rescheduled' => 'Reprogramada', 'cancelled' => 'Cancelada',
            default => $status,
        };
    }
}
