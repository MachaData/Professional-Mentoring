<?php

namespace Tests\Feature;

use App\Mail\TemplatedMail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class BrevoApiTransportTest extends TestCase
{
    public function test_it_posts_the_message_to_the_brevo_api(): void
    {
        config()->set('services.brevo.key', 'test-key');
        config()->set('mail.from.address', 'no-reply@plataforma.pro-mentoring.com');
        config()->set('mail.from.name', 'Professional Mentoring');

        Http::fake([
            'api.brevo.com/*' => Http::response(['messageId' => '<abc@brevo>'], 201),
        ]);

        Mail::mailer('brevo')->to('destino@ejemplo.com')
            ->send(new TemplatedMail('Hola Ana', 'Contenido **en negrita**'));

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->url() === 'https://api.brevo.com/v3/smtp/email'
                && $request->hasHeader('api-key', 'test-key')
                && $body['subject'] === 'Hola Ana'
                && $body['sender']['email'] === 'no-reply@plataforma.pro-mentoring.com'
                && $body['to'][0]['email'] === 'destino@ejemplo.com'
                && str_contains($body['htmlContent'], 'en negrita'); // markdown body rendered into the HTML
        });
    }

    public function test_it_raises_a_transport_exception_on_api_error(): void
    {
        config()->set('services.brevo.key', 'bad-key');
        config()->set('mail.from.address', 'no-reply@plataforma.pro-mentoring.com');

        Http::fake([
            'api.brevo.com/*' => Http::response(['message' => 'Key not found'], 401),
        ]);

        $this->expectException(TransportException::class);

        Mail::mailer('brevo')->to('destino@ejemplo.com')
            ->send(new TemplatedMail('Asunto', 'Cuerpo'));
    }
}
