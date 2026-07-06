<?php

namespace App\Services;

use App\Mail\TemplatedMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Renders an email template with sample data and sends it to an arbitrary address
 * so an editor can validate how it arrives before using it officially. Shared with
 * the live preview, which needs the same URL resolution for uploaded images.
 */
class TestEmailSender
{
    public function __construct(private TemplateRenderer $renderer) {}

    /**
     * @param  array<string,mixed>  $state  The (possibly unsaved) email template form state.
     */
    public function send(string $to, string $locale, array $state): void
    {
        $vars = TemplateRenderer::sampleVariables();

        $subject = '[PRUEBA] '.$this->renderer->render((string) data_get($state, "subject.$locale", ''), $vars);
        $body = $this->renderer->render((string) data_get($state, "body.$locale", ''), $vars);
        $headerUrl = $this->resolveUrl(data_get($state, 'header_image'));

        Mail::to($to)->send(new TemplatedMail($subject, $body, $headerUrl));
    }

    /**
     * Resolve a public URL from a FileUpload state value, which may be a stored path
     * string or a freshly uploaded (not yet persisted) temporary file.
     */
    public function resolveUrl(mixed $value): ?string
    {
        $value = is_array($value) ? collect($value)->first() : $value;

        if ($value instanceof TemporaryUploadedFile) {
            try {
                return $value->temporaryUrl();
            } catch (\Throwable) {
                return null;
            }
        }

        return is_string($value) && $value !== ''
            ? Storage::disk('public')->url($value)
            : null;
    }
}
