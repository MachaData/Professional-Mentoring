<?php

namespace App\Livewire;

use App\Models\Assignment;
use App\Models\Message;
use App\Models\Session;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Email-style mailbox for a dupla: a list of messages (subject, sender, date,
 * read/unread) that you open to read, and a composer with subject, body and an
 * optional attachment.
 */
class Mailbox extends Component
{
    use WithFileUploads;

    public Assignment $assignment;

    public ?int $openId = null;

    public string $filter = 'all';       // all | unread

    // Composer
    public bool $composing = false;

    public string $subject = '';

    public string $bodyText = '';

    public $attachment;

    /** Optional session the new message is about. */
    public ?string $targetSessionId = null;

    public function mount(Assignment $assignment): void
    {
        abort_unless($assignment->involves(auth()->user()), 403);

        $this->assignment = $assignment;
    }

    /** @return Collection<int,Message> */
    public function getMessagesProperty(): Collection
    {
        return $this->assignment->messages()->with('sender', 'session')
            ->when($this->filter === 'unread', fn ($q) => $q
                ->whereNull('read_at')->where('sender_id', '!=', auth()->id()))
            ->latest()
            ->get();
    }

    public function getOpenMessageProperty(): ?Message
    {
        if (! $this->openId) {
            return null;
        }

        return $this->assignment->messages()->with('sender', 'session')->find($this->openId);
    }

    /**
     * Sessions the writer may tie a message to. Same rule as the shared files
     * picker: only sessions they can actually open, so the dropdown never names
     * a session that is hidden from their role or still locked.
     *
     * @return Collection<int,Session>
     */
    public function getSessionsProperty(): Collection
    {
        return $this->assignment->allSessions()
            ->filter(fn (Session $session) => $session->isEnterableBy(auth()->user()))
            ->values();
    }

    public function getUnreadCountProperty(): int
    {
        return $this->assignment->messages()
            ->whereNull('read_at')->where('sender_id', '!=', auth()->id())->count();
    }

    public function open(int $id): void
    {
        $this->openId = $id;
        $message = $this->assignment->messages()->find($id);

        // Mark read when the recipient opens it.
        if ($message && $message->sender_id !== auth()->id() && $message->read_at === null) {
            $message->update(['read_at' => now()]);
        }
    }

    public function startCompose(): void
    {
        $this->reset('subject', 'bodyText', 'attachment', 'targetSessionId');
        $this->composing = true;
        $this->openId = null;
    }

    public function cancelCompose(): void
    {
        $this->composing = false;
    }

    public function send(): void
    {
        $this->validate([
            'subject' => ['required', 'string', 'max:255'],
            'bodyText' => ['nullable', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'max:10240'],
            // The picker already hides the rest, but the value arrives from the client.
            'targetSessionId' => ['nullable', Rule::in($this->sessions->pluck('id')->all())],
        ]);

        $data = [
            'organization_id' => $this->assignment->organization_id,
            'sender_id' => auth()->id(),
            'session_id' => $this->targetSessionId ?: null,
            'subject' => $this->subject,
            'body' => $this->bodyText ?: null,
        ];

        if ($this->attachment) {
            $data['attachment_path'] = $this->attachment->store('messages', 'public');
            $data['attachment_name'] = $this->attachment->getClientOriginalName();
        }

        $message = $this->assignment->messages()->create($data);

        $this->reset('subject', 'bodyText', 'attachment', 'targetSessionId', 'composing');
        $this->openId = $message->id;
    }

    public function render()
    {
        return view('livewire.mailbox');
    }
}
