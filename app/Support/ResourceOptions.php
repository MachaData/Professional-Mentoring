<?php

namespace App\Support;

use App\Models\Tool;
use App\Models\User;

/**
 * Shared option lists for tools and surveys (kept in one place so the admin
 * forms and any future API stay consistent).
 */
class ResourceOptions
{
    /**
     * Business units to pick from, taken from the units people are actually
     * tagged with. Units already assigned to a tool are folded in as well, so
     * an existing selection never disappears from the form when the last user
     * of that unit is removed.
     *
     * @return array<string,string>
     */
    public static function businessUnits(): array
    {
        $currentUser = auth()->user();

        $fromUsers = User::query()
            ->when(
                $currentUser && ! $currentUser->isSuperadmin() && $currentUser->organization_id,
                fn ($q) => $q->where('organization_id', $currentUser->organization_id),
            )
            ->whereNotNull('business_unit')
            ->distinct()
            ->pluck('business_unit');

        // Tool is organization-scoped globally, so this needs no extra filter.
        $fromTools = Tool::query()->whereNotNull('business_unit')->distinct()->pluck('business_unit');

        return $fromUsers->concat($fromTools)
            ->map(fn ($unit) => trim((string) $unit))
            ->filter()
            ->unique()
            ->sort()
            ->mapWithKeys(fn (string $unit) => [$unit => $unit])
            ->all();
    }

    /**
     * Languages a material can be written in. Unlike the business unit, this
     * never hides anything: it labels the document and lets people filter.
     *
     * @return array<string,string>
     */
    public static function languages(): array
    {
        return [
            'es' => 'Español',
            'en' => 'English',
            'pt' => 'Português',
            'fr' => 'Français',
        ];
    }

    /** Full name of a language code, or null when the material has none. */
    public static function languageLabel(?string $code): ?string
    {
        return $code ? (static::languages()[$code] ?? strtoupper($code)) : null;
    }

    /**
     * Where a material shows up, derived from its relations rather than stored:
     * a row with a session (or a stage) belongs inside that session, a row with
     * only a program is general.
     *
     * @return array<string,string>
     */
    public static function placements(): array
    {
        return [
            'general' => 'General del programa',
            'stage' => 'De etapa',
            'session' => 'De sesión',
        ];
    }

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
