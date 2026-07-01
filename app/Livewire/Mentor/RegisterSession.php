<?php

namespace App\Livewire\Mentor;

use App\Models\Assignment;
use App\Models\SessionRecord;
use App\Services\DynamicFormBuilder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class RegisterSession extends Component implements HasForms
{
    use InteractsWithForms;

    public SessionRecord $record;

    /** @var array<string,mixed> */
    public ?array $data = [];

    public function mount(SessionRecord $record): void
    {
        abort_unless($record->facilitator_id === auth()->id(), 403);

        $this->record = $record;
        $this->form->fill(app(DynamicFormBuilder::class)->stateFromRecord($record));
    }

    public function form(Schema $schema): Schema
    {
        $fields = $this->record->session->customFields()->where('status', 'active')->get();

        return $schema
            ->components(app(DynamicFormBuilder::class)->components($fields))
            ->statePath('data');
    }

    public function saveDraft(): void
    {
        $this->persist(SessionRecord::STATUS_DRAFT);
        session()->flash('status', __('Borrador guardado.'));
    }

    public function complete(): void
    {
        $this->form->validate();
        $this->persist(SessionRecord::STATUS_COMPLETED);
        session()->flash('status', __('Sesión registrada.'));

        $this->redirectRoute('mentor.participant', $this->record->assignment_id ?? $this->resolveAssignmentId());
    }

    protected function persist(string $status): void
    {
        $fields = $this->record->session->customFields()->where('status', 'active')->get();
        $state = $this->form->getState();

        DB::transaction(function () use ($fields, $state, $status) {
            app(DynamicFormBuilder::class)->saveValues($this->record, $fields, $state);

            $realDateField = $fields->firstWhere('name', 'real_session_date');
            $realDate = $realDateField ? ($state["field_{$realDateField->id}"] ?? null) : null;

            $this->record->update([
                'status' => $status,
                'real_session_date' => $realDate,
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
        return view('livewire.mentor.register-session');
    }
}
