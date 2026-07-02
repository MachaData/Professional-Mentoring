<?php

namespace App\Livewire;

use App\Models\Assignment;
use App\Models\SharedFile;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Private files shared within a dupla. Rendered below "Materiales":
 *  - general scope (sessionId null): shows files not tied to a session
 *  - session scope: shows files tied to that session
 * Both mentor and mentee can add files/links/notes.
 */
class PrivateFiles extends Component
{
    use WithFileUploads;

    public Assignment $assignment;
    public ?int $sessionId = null;

    public bool $showForm = false;
    public string $title = '';
    public string $kind = 'file';        // file | link | text
    public $upload;
    public ?string $externalUrl = null;
    public ?string $bodyText = null;
    public ?string $targetSessionId = null; // only used in general scope

    public function mount(Assignment $assignment, ?int $sessionId = null): void
    {
        abort_unless($assignment->involves(auth()->user()), 403);

        $this->assignment = $assignment;
        $this->sessionId = $sessionId;
    }

    /** @return Collection<int,SharedFile> */
    public function getFilesProperty(): Collection
    {
        return $this->assignment->files()->with('uploader', 'session')
            ->when($this->sessionId, fn ($q) => $q->where('session_id', $this->sessionId))
            ->when(! $this->sessionId, fn ($q) => $q->whereNull('session_id'))
            ->get();
    }

    public function getSessionsProperty(): Collection
    {
        return $this->assignment->program->sessions()->orderBy('sort_order')->get();
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'kind' => ['required', 'in:file,link,text'],
            'upload' => ['nullable', 'file', 'max:10240',
                'mimes:pdf,doc,docx,xls,xlsx,csv,ppt,pptx,jpg,jpeg,png,gif,webp'],
            'externalUrl' => ['nullable', 'url'],
            'bodyText' => ['nullable', 'string', 'max:5000'],
        ]);

        // Session scope is fixed when embedded in a session; otherwise optional.
        $session = $this->sessionId ?: ($this->targetSessionId ?: null);

        $data = [
            'organization_id' => $this->assignment->organization_id,
            'uploaded_by' => auth()->id(),
            'session_id' => $session,
            'title' => $this->title,
        ];

        if ($this->kind === 'link') {
            if (blank($this->externalUrl)) {
                $this->addError('externalUrl', __('Ingresa un enlace.'));

                return;
            }
            $data['type'] = 'link';
            $data['external_url'] = $this->externalUrl;
        } elseif ($this->kind === 'text') {
            if (blank($this->bodyText)) {
                $this->addError('bodyText', __('Escribe el texto.'));

                return;
            }
            $data['type'] = 'text';
            $data['body_text'] = $this->bodyText;
        } else {
            if (! $this->upload) {
                $this->addError('upload', __('Selecciona un archivo.'));

                return;
            }
            $data['type'] = SharedFile::typeFromExtension($this->upload->getClientOriginalExtension());
            $data['file_path'] = $this->upload->store('shared-files', 'public');
            $data['file_name'] = $this->upload->getClientOriginalName();
        }

        $this->assignment->files()->create($data);

        $this->reset('title', 'kind', 'upload', 'externalUrl', 'bodyText', 'targetSessionId', 'showForm');
        $this->kind = 'file';
    }

    public function deleteFile(int $id): void
    {
        $file = $this->assignment->files()->whereKey($id)->first();
        if ($file && $file->uploaded_by === auth()->id()) {
            $file->delete();
        }
    }

    public function render()
    {
        return view('livewire.private-files');
    }
}
