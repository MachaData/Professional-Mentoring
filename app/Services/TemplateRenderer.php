<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Program;
use App\Models\Session;
use App\Models\User;

/**
 * Builds the variable bag for email copy and interpolates {{placeholders}}.
 * Central list of supported variables lives in availableVariables().
 */
class TemplateRenderer
{
    /** @return array<int,string> */
    public static function availableVariables(): array
    {
        return [
            'user_name', 'program_name', 'session_name', 'stage_name',
            'start_date', 'end_date', 'facilitator_name', 'participant_name',
            'platform_link', 'survey_link',
        ];
    }

    /** @return array<string,string> */
    public function variables(array $context): array
    {
        $locale = app()->getLocale();
        $user = $context['user'] ?? null;
        $program = $context['program'] ?? null;
        $session = $context['session'] ?? null;
        $assignment = $context['assignment'] ?? null;

        return array_map(fn ($v) => (string) $v, [
            'user_name' => $user?->name ?? '',
            'program_name' => $program instanceof Program ? $program->getTranslation('name', $locale) : '',
            'session_name' => $session instanceof Session ? $session->getTranslation('name', $locale) : '',
            'stage_name' => $session?->stage instanceof \App\Models\Stage ? $session->stage->getTranslation('name', $locale) : '',
            'start_date' => $session?->start_date?->format('d/m/Y') ?? $program?->start_date?->format('d/m/Y') ?? '',
            'end_date' => $session?->end_date?->format('d/m/Y') ?? $program?->end_date?->format('d/m/Y') ?? '',
            'facilitator_name' => $assignment instanceof Assignment ? $assignment->facilitator?->name : ($context['facilitator_name'] ?? ''),
            'participant_name' => $assignment instanceof Assignment ? $assignment->participant?->name : ($context['participant_name'] ?? ''),
            'platform_link' => $context['platform_link'] ?? route('portal.login'),
            'survey_link' => $context['survey_link'] ?? '',
        ]);
    }

    public function render(string $text, array $variables): string
    {
        return preg_replace_callback('/\{\{\s*(\w+)\s*\}\}/', function ($m) use ($variables) {
            return $variables[$m[1]] ?? $m[0];
        }, $text);
    }
}
