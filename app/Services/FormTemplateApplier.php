<?php

namespace App\Services;

use App\Models\FormTemplate;
use App\Models\Session;
use Illuminate\Support\Facades\DB;

/**
 * Copies a form template's field definitions (CustomField rows) into a session,
 * preserving label, type, order, required/optional and visibility. New fields are
 * detached from the template (form_template_id = null) and scoped to the session.
 */
class FormTemplateApplier
{
    /**
     * @param  bool  $replace  Delete the session's current fields before copying.
     * @return int Number of fields copied.
     */
    public function apply(FormTemplate $template, Session $session, bool $replace = false): int
    {
        return DB::transaction(function () use ($template, $session, $replace) {
            if ($replace) {
                $session->customFields()->delete();
            }

            $offset = $replace ? 0 : (int) $session->customFields()->max('sort_order');
            $copied = 0;

            foreach ($template->fields()->orderBy('sort_order')->get() as $index => $field) {
                $new = $field->replicate([
                    'form_template_id', 'session_id', 'program_id', 'stage_id',
                ]);
                $new->organization_id = $session->organization_id;
                $new->session_id = $session->id;
                $new->form_template_id = null;
                $new->program_id = null;
                $new->stage_id = null;
                $new->sort_order = $offset + $index + 1;
                $new->save();

                $copied++;
            }

            return $copied;
        });
    }
}
