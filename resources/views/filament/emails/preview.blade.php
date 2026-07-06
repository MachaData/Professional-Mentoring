@php
    use App\Services\TemplateRenderer;
    use App\Services\TestEmailSender;
    use Illuminate\Support\Str;

    $vars = TemplateRenderer::sampleVariables();
    $renderer = app(TemplateRenderer::class);
    $sender = app(TestEmailSender::class);

    $locale = $get('preview_locale') ?: 'es';

    $subject = $renderer->render((string) ($get("subject.$locale") ?? ''), $vars);
    $body = $renderer->render((string) ($get("body.$locale") ?? ''), $vars);
    $bodyHtml = trim($body) !== '' ? Str::markdown($body) : '<em style="color:#9ca3af;">(cuerpo vacío)</em>';

    $headerUrl = $sender->resolveUrl($get('header_image'));

    $imageUrls = collect((array) $get('images'))
        ->map(fn ($img) => $sender->resolveUrl($img))
        ->filter()
        ->values();
@endphp

<div style="font-size:.8rem;color:#6b7280;margin-bottom:.5rem;">
    Vista previa con datos de ejemplo — {{ strtoupper($locale) }}
</div>

<div style="border:1px solid #e5e7eb;border-radius:.5rem;overflow:hidden;background:#fff;color:#111827;">
    <div style="background:#f3f4f6;padding:.75rem 1rem;border-bottom:1px solid #e5e7eb;">
        <div style="font-size:.7rem;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">Asunto</div>
        <div style="font-weight:600;">{{ $subject !== '' ? $subject : '(sin asunto)' }}</div>
    </div>

    <div style="padding:1.25rem;line-height:1.6;">
        @if ($headerUrl)
            <p style="text-align:center;margin:0 0 1rem;">
                <img src="{{ $headerUrl }}" alt="" style="max-width:100%;height:auto;border-radius:6px;">
            </p>
        @endif

        <div class="prose-email">{!! $bodyHtml !!}</div>
    </div>
</div>

@if ($imageUrls->isNotEmpty())
    <div style="margin-top:1rem;font-size:.8rem;">
        <div style="font-weight:600;margin-bottom:.35rem;">Imágenes subidas — copia el Markdown en el cuerpo:</div>
        @foreach ($imageUrls as $url)
            <code style="display:block;background:#f3f4f6;color:#111827;padding:.35rem .5rem;border-radius:.35rem;margin-bottom:.35rem;word-break:break-all;">![imagen]({{ $url }})</code>
        @endforeach
    </div>
@elseif (filled($get('images')))
    <div style="margin-top:1rem;font-size:.8rem;color:#9ca3af;">
        Guarda la plantilla para obtener las URLs definitivas de las imágenes.
    </div>
@endif
