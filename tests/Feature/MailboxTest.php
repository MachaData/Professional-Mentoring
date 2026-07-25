<?php

namespace Tests\Feature;

use App\Filament\Resources\Assignments\Pages\ViewAssignment;
use App\Filament\Resources\Assignments\RelationManagers\MessagesRelationManager;
use App\Livewire\Mailbox;
use App\Livewire\PrivateFiles;
use App\Models\Assignment;
use App\Models\Message;
use App\Models\Session;
use App\Models\SharedFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MailboxTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function assignment(): Assignment
    {
        return Assignment::firstOrFail();
    }

    protected function mentor(): User
    {
        return User::where('email', 'mentor@demo.test')->firstOrFail();
    }

    protected function mentee(): User
    {
        return User::where('email', 'mentee@demo.test')->firstOrFail();
    }

    public function test_space_pages_open_for_the_dupla(): void
    {
        $a = $this->assignment();
        $this->actingAs($this->mentor())->get(route('mentor.space', $a))->assertSuccessful();
        $this->actingAs($this->mentee())->get(route('participant.space'))->assertSuccessful();
    }

    public function test_outsider_cannot_open_mailbox(): void
    {
        $a = $this->assignment();
        $intruder = User::factory()->create(['organization_id' => $a->organization_id, 'role' => User::ROLE_FACILITATOR]);
        Livewire::actingAs($intruder)->test(Mailbox::class, ['assignment' => $a])->assertForbidden();
    }

    public function test_send_email_with_subject_and_attachment(): void
    {
        Storage::fake('public');
        $a = $this->assignment();

        Livewire::actingAs($this->mentee())
            ->test(Mailbox::class, ['assignment' => $a])
            ->set('subject', 'Entrega de tarea')
            ->set('bodyText', 'Adjunto el documento')
            ->set('attachment', UploadedFile::fake()->create('tarea.pdf', 30, 'application/pdf'))
            ->call('send')
            ->assertHasNoErrors();

        $msg = Message::where('assignment_id', $a->id)->where('subject', 'Entrega de tarea')->first();
        $this->assertNotNull($msg);
        $this->assertSame($this->mentee()->id, $msg->sender_id);
        $this->assertNotNull($msg->attachment_path);
    }

    public function test_subject_is_required(): void
    {
        $a = $this->assignment();
        Livewire::actingAs($this->mentee())
            ->test(Mailbox::class, ['assignment' => $a])
            ->set('subject', '')
            ->set('bodyText', 'sin asunto')
            ->call('send')
            ->assertHasErrors(['subject']);
    }

    public function test_opening_a_message_marks_it_read(): void
    {
        $a = $this->assignment();
        $unread = $a->messages()->create([
            'organization_id' => $a->organization_id,
            'sender_id' => $this->mentee()->id,
            'subject' => 'Pregunta',
            'body' => 'Una consulta',
        ]);
        $this->assertNull($unread->read_at);

        Livewire::actingAs($this->mentor())
            ->test(Mailbox::class, ['assignment' => $a])
            ->call('open', $unread->id);

        $this->assertNotNull($unread->fresh()->read_at);
    }

    // ---- Optional session on a message ------------------------------------

    public function test_a_message_can_be_tied_to_a_session_or_stay_general(): void
    {
        $a = $this->assignment();
        $session = $a->program->sessions()->where('number', 3)->firstOrFail();

        Livewire::actingAs($this->mentor())
            ->test(Mailbox::class, ['assignment' => $a])
            ->set('subject', 'Sobre la sesión 3')
            ->set('bodyText', 'Revisa el material antes')
            ->set('targetSessionId', (string) $session->id)
            ->call('send')
            ->assertHasNoErrors();

        Livewire::actingAs($this->mentor())
            ->test(Mailbox::class, ['assignment' => $a])
            ->set('subject', 'Saludo general')
            ->call('send')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('messages', [
            'assignment_id' => $a->id, 'subject' => 'Sobre la sesión 3', 'session_id' => $session->id,
        ]);
        $this->assertDatabaseHas('messages', [
            'assignment_id' => $a->id, 'subject' => 'Saludo general', 'session_id' => null,
        ]);
    }

    public function test_a_message_cannot_point_to_a_session_outside_the_program(): void
    {
        $a = $this->assignment();
        // A session id that is not among the dupla's own sessions: the picker
        // never offers it, but the value travels from the browser.
        $foreign = (int) Session::query()->max('id') + 1;

        Livewire::actingAs($this->mentor())
            ->test(Mailbox::class, ['assignment' => $a])
            ->set('subject', 'Intento')
            ->set('targetSessionId', (string) $foreign)
            ->call('send')
            ->assertHasErrors(['targetSessionId']);

        $this->assertDatabaseMissing('messages', ['assignment_id' => $a->id, 'subject' => 'Intento']);
    }

    public function test_supervisors_read_the_mailbox_with_its_session_and_read_state(): void
    {
        $a = $this->assignment();
        $session = $a->program->sessions()->where('number', 2)->firstOrFail();

        $a->messages()->create([
            'organization_id' => $a->organization_id,
            'sender_id' => $this->mentee()->id,
            'session_id' => $session->id,
            'subject' => 'Consulta de la sesión 2',
            'body' => 'Tengo una duda con la actividad',
        ]);

        $coordinator = User::where('email', 'coordinador@demo.test')->firstOrFail();

        Livewire::actingAs($coordinator)
            ->test(MessagesRelationManager::class, ['ownerRecord' => $a, 'pageClass' => ViewAssignment::class])
            ->assertOk()
            ->assertSee('Consulta de la sesión 2')
            ->assertSee('S'.$session->number);
    }

    public function test_private_file_general_vs_session_scope(): void
    {
        $a = $this->assignment();
        $session = $a->program->sessions()->where('number', 3)->firstOrFail();

        // General note (no session)
        Livewire::actingAs($this->mentor())
            ->test(PrivateFiles::class, ['assignment' => $a])
            ->set('title', 'Nota general')->set('kind', 'text')->set('bodyText', 'hola')
            ->call('save')->assertHasNoErrors();

        // Session-scoped note
        Livewire::actingAs($this->mentee())
            ->test(PrivateFiles::class, ['assignment' => $a, 'sessionId' => $session->id])
            ->set('title', 'Nota S3')->set('kind', 'text')->set('bodyText', 'para la sesión 3')
            ->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('shared_files', ['assignment_id' => $a->id, 'title' => 'Nota general', 'session_id' => null]);
        $this->assertDatabaseHas('shared_files', ['assignment_id' => $a->id, 'title' => 'Nota S3', 'session_id' => $session->id]);

        // The general view lists only general files.
        $generalTitles = SharedFile::where('assignment_id', $a->id)->whereNull('session_id')->pluck('title');
        $this->assertTrue($generalTitles->contains('Nota general'));
        $this->assertFalse($generalTitles->contains('Nota S3'));
    }
}
