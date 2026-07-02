<?php

namespace Tests\Feature;

use App\Livewire\DuplaSpace;
use App\Models\Assignment;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DuplaSpaceTest extends TestCase
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

    public function test_mentor_and_mentee_can_open_the_space(): void
    {
        $a = $this->assignment();
        $this->actingAs($this->mentor())->get(route('mentor.space', $a))->assertSuccessful();
        $this->actingAs($this->mentee())->get(route('participant.space'))->assertSuccessful();
    }

    public function test_outsider_cannot_open_the_space(): void
    {
        $a = $this->assignment();
        $intruder = User::factory()->create(['organization_id' => $a->organization_id, 'role' => User::ROLE_FACILITATOR]);
        $this->actingAs($intruder)->get(route('mentor.space', $a))->assertForbidden();
    }

    public function test_mentee_sends_a_message_with_attachment(): void
    {
        Storage::fake('public');
        $a = $this->assignment();

        Livewire::actingAs($this->mentee())
            ->test(DuplaSpace::class, ['assignment' => $a])
            ->set('body', 'Adjunto mi tarea')
            ->set('messageAttachment', UploadedFile::fake()->create('tarea.pdf', 40, 'application/pdf'))
            ->call('sendMessage')
            ->assertHasNoErrors();

        $msg = Message::where('assignment_id', $a->id)->where('sender_id', $this->mentee()->id)->latest()->first();
        $this->assertNotNull($msg);
        $this->assertSame('Adjunto mi tarea', $msg->body);
        $this->assertNotNull($msg->attachment_path);
        Storage::disk('public')->assertExists($msg->attachment_path);
    }

    public function test_opening_the_space_marks_counterpart_messages_read(): void
    {
        $a = $this->assignment();
        // A fresh unread message from the mentee.
        $unread = $a->messages()->create([
            'organization_id' => $a->organization_id,
            'sender_id' => $this->mentee()->id,
            'body' => 'Pregunta pendiente',
        ]);
        $this->assertNull($unread->read_at);

        // Mentor opens the space -> counterpart (mentee) messages become read.
        Livewire::actingAs($this->mentor())->test(DuplaSpace::class, ['assignment' => $a]);

        $this->assertNotNull($unread->fresh()->read_at);
    }

    public function test_mentor_shares_a_text_note_tied_to_a_session(): void
    {
        $a = $this->assignment();
        $session = $a->program->sessions()->where('number', 2)->firstOrFail();

        Livewire::actingAs($this->mentor())
            ->test(DuplaSpace::class, ['assignment' => $a])
            ->set('fileTitle', 'Instrucciones S2')
            ->set('fileKind', 'text')
            ->set('bodyText', 'Trae tu mapa de vida')
            ->set('fileSessionId', (string) $session->id)
            ->call('shareFile')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('shared_files', [
            'assignment_id' => $a->id,
            'title' => 'Instrucciones S2',
            'type' => 'text',
            'session_id' => $session->id,
        ]);
    }
}
