<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

/**
 * Sends mail through Brevo's transactional HTTP API (port 443) instead of SMTP.
 * Cloud hosts often block/throttle outbound SMTP ports; HTTPS always works, and
 * the request returns fast so a slow send can't hang the web process.
 */
class BrevoApiTransport extends AbstractTransport
{
    private const ENDPOINT = 'https://api.brevo.com/v3/smtp/email';

    public function __construct(private string $apiKey)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $envelope = $message->getEnvelope();

        $response = Http::withHeaders([
            'api-key' => $this->apiKey,
            'accept' => 'application/json',
        ])->asJson()->post(self::ENDPOINT, $this->payload($email, $envelope));

        if ($response->failed()) {
            throw new TransportException('Brevo API error ('.$response->status().'): '.$response->body());
        }
    }

    /** @return array<string,mixed> */
    private function payload(Email $email, Envelope $envelope): array
    {
        $from = $email->getFrom()[0] ?? $envelope->getSender();

        $html = $email->getHtmlBody();
        $text = $email->getTextBody();

        $payload = [
            'sender' => $this->address($from),
            'to' => $this->addresses($email->getTo() ?: $envelope->getRecipients()),
            'subject' => $email->getSubject() ?? '',
            'htmlContent' => is_string($html) && $html !== ''
                ? $html
                : nl2br(e((string) ($text ?? ' '))),
        ];

        if (is_string($text) && $text !== '') {
            $payload['textContent'] = $text;
        }
        if ($cc = $email->getCc()) {
            $payload['cc'] = $this->addresses($cc);
        }
        if ($bcc = $email->getBcc()) {
            $payload['bcc'] = $this->addresses($bcc);
        }
        if ($replyTo = $email->getReplyTo()) {
            $payload['replyTo'] = $this->address($replyTo[0]);
        }

        return $payload;
    }

    /** @param array<int,Address> $addresses */
    private function addresses(array $addresses): array
    {
        return array_map(fn (Address $a) => $this->address($a), $addresses);
    }

    /** @return array<string,string> */
    private function address(Address $address): array
    {
        $data = ['email' => $address->getAddress()];
        if ($address->getName() !== '') {
            $data['name'] = $address->getName();
        }

        return $data;
    }

    public function __toString(): string
    {
        return 'brevo+api://'.parse_url(self::ENDPOINT, PHP_URL_HOST);
    }
}
