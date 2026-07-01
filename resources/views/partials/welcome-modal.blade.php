@php
    $user = auth()->user();
    $org = $user?->organization;
    $locale = app()->getLocale();
    $show = $user && ! $user->onboarding_seen_at && $org && $org->welcome_enabled
        && ($org->getTranslation('welcome_text', $locale, false) || $org->welcome_video_url);
    // Normalize a YouTube/Vimeo link to an embeddable URL.
    $embed = null;
    if ($show && $org->welcome_video_url) {
        $u = $org->welcome_video_url;
        if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/)([\w-]+)~', $u, $m)) {
            $embed = 'https://www.youtube.com/embed/'.$m[1];
        } elseif (preg_match('~vimeo\.com/(\d+)~', $u, $m)) {
            $embed = 'https://player.vimeo.com/video/'.$m[1];
        } else {
            $embed = $u;
        }
    }
@endphp

@if($show)
    <div id="pm-welcome" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
        <div class="pm-card relative w-full max-w-lg overflow-hidden p-0 shadow-xl">
            <div class="relative bg-gradient-to-br from-brand-600 to-brand-800 px-6 py-5 text-white">
                <div class="pm-grid-bg absolute inset-0 opacity-30"></div>
                <div class="relative">
                    <p class="text-sm text-white/70">{{ config('app.name') }}</p>
                    <h2 class="mt-0.5 text-xl font-bold text-white">
                        {{ __('¡Te damos la bienvenida!') }}
                    </h2>
                </div>
            </div>

            <div class="max-h-[70vh] overflow-y-auto p-6">
                @if($embed)
                    <div class="mb-4 aspect-video overflow-hidden rounded-xl bg-slate-100">
                        <iframe src="{{ $embed }}" class="h-full w-full" frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen></iframe>
                    </div>
                @endif

                @if($org->getTranslation('welcome_text', $locale, false))
                    <div class="prose prose-sm max-w-none text-slate-600">
                        {!! nl2br(e($org->getTranslation('welcome_text', $locale))) !!}
                    </div>
                @endif
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                <button type="button" onclick="pmDismissWelcome()" class="pm-btn-brand">
                    {{ __('Empezar') }}
                </button>
            </div>
        </div>
    </div>

    <script>
        function pmDismissWelcome() {
            document.getElementById('pm-welcome')?.remove();
            fetch('{{ route('onboarding.seen') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                },
            });
        }
    </script>
@endif
