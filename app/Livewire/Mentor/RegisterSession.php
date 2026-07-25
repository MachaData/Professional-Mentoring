<?php

namespace App\Livewire\Mentor;

use App\Enums\FieldType;
use App\Models\Assignment;
use App\Models\CustomField;
use App\Models\SessionRecord;
use App\Services\DynamicFormBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

class RegisterSession extends Component
{
    use WithFileUploads;

    public SessionRecord $record;

    /** Structured fields tied to this dupla's record. */
    public ?string $realSessionDate = null;

    public string $attendance = 'pending';

    public ?string $modality = null;

    public ?string $meetingUrl = null;

    /** @var array<string,mixed> Dynamic custom-field values. */
    public array $data = [];

    public function mount(SessionRecord $record): void
    {
        $this->authorizeRecord($record);

        $this->record = $record;
        // Format the date as a plain Y-m-d string so <input type=date> shows it.
        $this->realSessionDate = $record->real_session_date?->format('Y-m-d');
        $this->attendance = $record->attendance ?: 'pending';
        $this->modality = $record->modality;
        $this->meetingUrl = $record->meeting_url;
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
        $rules = [
            'realSessionDate' => ['nullable', 'date'],
            'attendance' => ['required', 'in:'.implode(',', array_keys(SessionRecord::ATTENDANCE))],
            'modality' => ['nullable', 'in:'.implode(',', array_keys(SessionRecord::MODALITY))],
            'meetingUrl' => ['nullable', 'url'],
        ];

        foreach ($this->inputFields() as $field) {
            if ($field->is_required) {
                $rules["data.field_{$field->id}"] = ['required'];
            }
        }

        return $rules;
    }

    protected function validationAttributes(): array
    {
        $attrs = [
            'realSessionDate' => __('Fecha real de la sesión'),
            'attendance' => __('Asistencia'),
            'meetingUrl' => __('Link de la reunión'),
        ];
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

    /**
     * Guard both on mount and on every write: Livewire requests never re-enter
     * the controller, so a page opened before a session was locked could
     * otherwise still submit against it.
     */
    protected function authorizeRecord(SessionRecord $record): void
    {
        abort_unless($record->facilitator_id === auth()->id(), 403);
        abort_unless($record->session->isEnterableBy(auth()->user()), 403);
    }

    protected function persist(string $status): void
    {
        $this->authorizeRecord($this->record);

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

            $this->record->update([
                'status' => $status,
                'attendance' => $this->attendance,
                'modality' => $this->modality ?: null,
                'real_session_date' => $this->realSessionDate ?: null,
                'meeting_url' => $this->meetingUrl ?: null,
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
