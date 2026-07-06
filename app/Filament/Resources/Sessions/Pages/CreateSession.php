<?php

namespace App\Filament\Resources\Sessions\Pages;

use App\Filament\Resources\Sessions\SessionResource;
use App\Models\FormTemplate;
use App\Services\FormTemplateApplier;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSession extends CreateRecord
{
    protected static string $resource = SessionResource::class;

    protected function afterCreate(): void
    {
        $templateId = $this->record->form_template_id;

        if (! $templateId) {
            return;
        }

        $template = FormTemplate::find($templateId);

        if (! $template) {
            return;
        }

        $count = app(FormTemplateApplier::class)->apply($template, $this->record);

        Notification::make()
            ->success()
            ->title('Plantilla aplicada')
            ->body("Se copiaron {$count} campos desde la plantilla «{$template->name}».")
            ->send();
    }
}
