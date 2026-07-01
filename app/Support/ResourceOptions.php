<?php

namespace App\Support;

/**
 * Shared option lists for tools and surveys (kept in one place so the admin
 * forms and any future API stay consistent).
 */
class ResourceOptions
{
    /** @return array<string,string> */
    public static function toolTypes(): array
    {
        return [
            'pdf' => 'PDF',
            'word' => 'Word',
            'excel' => 'Excel',
            'powerpoint' => 'PowerPoint',
            'image' => 'Imagen',
            'video' => 'Video',
            'external_link' => 'Enlace externo',
            'google_drive' => 'Google Drive',
            'google_sheet' => 'Google Sheet',
            'google_form' => 'Google Form',
            'workbook' => 'Workbook',
            'guide' => 'Guía',
            'template' => 'Plantilla',
            'test' => 'Test',
            'evaluation' => 'Evaluación',
            'other' => 'Otro',
        ];
    }

    /** @return array<string,string> */
    public static function visibility(): array
    {
        return [
            'admin' => 'Solo administradores',
            'facilitator' => 'Facilitador',
            'participant' => 'Participante',
            'both' => 'Facilitador y participante',
            'all' => 'Todos',
        ];
    }

    /** @return array<string,string> */
    public static function displayMoment(): array
    {
        return [
            'always' => 'Siempre',
            'after_session' => 'Después de la sesión',
            'after_stage' => 'Después de la etapa',
            'program_end' => 'Al cierre del programa',
            'manual' => 'Manual',
        ];
    }
}
