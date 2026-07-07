<?php

namespace Tests\Feature;

use App\Mail\TemplatedMail;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReplyToTest extends TestCase
{
    public function test_reply_to_default_is_the_real_inbox(): void
    {
        $this->assertSame('mentoring@crosspartnersgroup.com', config('mail.reply_to.address'));
    }

    public function test_outgoing_mail_carries_the_reply_to_header(): void
    {
        Mail::to('destino@ejemplo.com')->send(new TemplatedMail('Asunto', 'Cuerpo'));

        $transport = Mail::mailer()->getSymfonyTransport();
        $this->assertInstanceOf(ArrayTransport::class, $transport);

        $message = $transport->messages()->last()->getOriginalMessage();
        $replyTo = $message->getReplyTo();

        $this->assertNotEmpty($replyTo, 'El correo no lleva Reply-To.');
        $this->assertSame('mentoring@crosspartnersgroup.com', $replyTo[0]->getAddress());
    }
}
