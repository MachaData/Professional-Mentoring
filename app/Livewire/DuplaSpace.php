<?php

namespace App\Livewire;

use App\Models\Assignment;
use App\Models\Message;
use App\Models\SharedFile;
use Illuminate\Support\Collection;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Shared space for a dupla (mentor <-> mentee): a mailbox (messages with
 * attachments and read status) and a files area (upload / link / text note,
 * optionally tied to a session). Used by both the mentor and mentee portals.
 */
class DuplaSpace extends Component
{
    use WithFileUploads;

    public Assignment $assignment;

    public string $tab = 'messages';

    // Message composer
    public string $body = '';
    public $messageAttachment;

    // File sharing
    public string $fileTitle = '';
    public string $fileKind = 'file'; // file | link | text
    public $upload;
    public ?string $externalUrl = null;
    public ?string $bodyText = null;
    public ?string $fileSessionId = null;

    // Filters
    public string $filterSession = '';

    public function mount(Assignment $assignment): void
    {
        abort_unless($assignment->involves(auth()->user()), 403);

        $this->assignment = $assignment;
        $this->markCounterpartMessagesRead();
    }

    protected function markCounterpartMessagesRead(): void
    {
        $this->assignment->messages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', auth()->id())
            ->update(['read_at' => now()]);
    }

    /** @return Collection<int,Message> */
    public function getMessagesProperty(): Collection
    {
        return $this->assignment->messages()->with('sender')->get();
    }

    /** @return Collection<int,SharedFile> */
    public function getFilesProperty(): Collection
    {
        return $this->assignment->files()->with(['uploader', 'session'])
            ->when($this->filterSession !== '', function ($q) {
                $this->filterSession === 'general'
                    ? $q->whereNull('session_id')
                    : $q->where('session_id', $this->filterSession);
            })
            ->get();
    }

    public function getSessionsProperty(): Collection
    {
        return $this->assignment->program->sessions()->orderBy('sort_order')->get();
    }

    public function sendMessage(): void
    {
        $this->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'messageAttachment' => ['nullable', 'file', 'max:10240'],
        ]);

        if (blank($this->body) && ! $this->messageAttachment) {
            $this->addError('body', __('Escribe un mensaje o adjunta un archivo.'));

            return;
        }

        $data = [
            'organization_id' => $this->assignment->organization_id,
            'sender_id' => auth()->id(),
            'body' => $this->body ?: null,
        ];

        if ($this->messageAttachment) {
            $data['attachment_path'] = $this->messageAttachment->store('messages', 'public');
            $data['attachment_name'] = $this->messageAttachment->getClientOriginalName();
        }

        $this->assignment->messages()->create($data);

        $this->reset('body', 'messageAttachment');
    }

    public function shareFile(): void
    {
        $this->validate([
            'fileTitle' => ['required', 'string', 'max:255'],
            'fileKind' => ['required', 'in:file,link,text'],
            'upload' => ['nullable', 'file', 'max:10240',
                'mimes:pdf,doc,docx,xls,xlsx,csv,ppt,pptx,jpg,jpeg,png,gif,webp'],
            'externalUrl' => ['nullable', 'url'],
            'bodyText' => ['nullable', 'string', 'max:5000'],
            'fileSessionId' => ['nullable'],
        ]);

        $data = [
            'organization_id' => $this->assignment->organization_id,
            'uploaded_by' => auth()->id(),
            'session_id' => $this->fileSessionId ?: null,
            'title' => $this->fileTitle,
        ];

        if ($this->fileKind === 'link') {
            if (blank($this->externalUrl)) {
                $this->addError('externalUrl', __('Ingresa un enlace.'));

                return;
            }
            $data['type'] = 'link';
            $data['external_url'] = $this->externalUrl;
        } elseif ($this->fileKind === 'text') {
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

        $this->reset('fileTitle', 'fileKind', 'upload', 'externalUrl', 'bodyText', 'fileSessionId');
        $this->fileKind = 'file';
    }

    public function deleteFile(int $id): void
    {
        $file = $this->assignment->files()->whereKey($id)->first();
        // Only the uploader can remove their own item.
        if ($file && $file->uploaded_by === auth()->id()) {
            $file->delete();
        }
    }

    public function render()
    {
        return view('livewire.dupla-space');
    }
}
