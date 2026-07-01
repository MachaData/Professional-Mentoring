<?php

namespace App\Livewire\Mentor;

use App\Enums\FieldType;
use App\Models\Assignment;
use App\Models\CustomField;
use App\Models\SessionRecord;
use App\Services\DynamicFormBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithFileUploads;

class RegisterSession extends Component
{
    use WithFileUploads;

    public SessionRecord $record;

    /** @var array<string,mixed> */
    public array $data = [];

    public function mount(SessionRecord $record): void
    {
        abort_unless($record->facilitator_id === auth()->id(), 403);

        $this->record = $record;
        $this->data = app(DynamicFormBuilder::class)->stateFromRecord($record);
    }

    /** @return Collection<int,CustomField> */
    public function getFieldsProperty(): Collection
    {
        return $this->record->session->customFields()->where('status', 'active')->get();
    }

    protected function inputFields(): Collection
    {
        return $this->fields->reject(fn (CustomField $f) => $f->field_type->isLayout());
    }

    /** @return array<string,array<int,string>> */
    protected function rules(): array
    {
        $rules = [];
        foreach ($this->inputFields() as $field) {
            if ($field->is_required) {
                $rules["data.field_{$field->id}"] = ['required'];
            }
        }

        return $rules;
    }

    protected function validationAttributes(): array
    {
        $attrs = [];
        foreach ($this->inputFields() as $field) {
            $attrs["data.field_{$field->id}"] = $field->getTranslation('label', app()->getLocale());
        }

        return $attrs;
    }

    public function saveDraft(): void
    {
        $this->persist(SessionRecord::STATUS_DRAFT);
        session()->flash('status', __('Borrador guardado.'));
    }

    public function complete(): void
    {
        $this->validate();
        $this->persist(SessionRecord::STATUS_COMPLETED);
        session()->flash('status', __('Sesión registrada.'));

        $this->redirectRoute('mentor.participant', $this->record->assignment_id ?? $this->resolveAssignmentId());
    }

    protected function persist(string $status): void
    {
        $fields = $this->fields;
        $state = $this->data;

        // Persist any uploaded files first, replacing the temp upload with a path.
        foreach ($fields as $field) {
            if ($field->field_type === FieldType::File) {
                $key = "field_{$field->id}";
                $upload = $state[$key] ?? null;
                if ($upload && ! is_string($upload)) {
                    $state[$key] = $upload->store('session-records', 'public');
                }
            }
        }

        DB::transaction(function () use ($fields, $state, $status) {
            app(DynamicFormBuilder::class)->saveValues($this->record, $fields, $state);

            $realDateField = $fields->firstWhere('name', 'real_session_date');
            $realDate = $realDateField ? ($state["field_{$realDateField->id}"] ?? null) : null;

            $this->record->update([
                'status' => $status,
                'real_session_date' => $realDate ?: null,
                'submitted_at' => $status === SessionRecord::STATUS_COMPLETED ? now() : $this->record->submitted_at,
            ]);
        });
    }

    protected function resolveAssignmentId(): ?int
    {
        return Assignment::where('program_id', $this->record->session->program_id)
            ->where('facilitator_id', $this->record->facilitator_id)
            ->where('participant_id', $this->record->participant_id)
            ->value('id');
    }

    public function render()
    {
        return view('livewire.mentor.register-session', [
            'fields' => $this->fields,
        ]);
    }
}
