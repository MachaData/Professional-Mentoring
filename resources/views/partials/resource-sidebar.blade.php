@php $locale = app()->getLocale(); @endphp
{{-- Right sidebar: program-wide resources. Expects $sidebarTools, $sidebarSurveys --}}
<aside class="lg:sticky lg:top-20 space-y-4">
    <div class="pm-card p-5">
        <div class="mb-3 flex items-center gap-2">
            <span class="grid h-8 w-8 place-items-center rounded-lg bg-brand-50 text-brand-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
            </span>
            <h3 class="text-sm font-semibold text-slate-900">{{ __('Materiales del programa') }}</h3>
        </div>

        @forelse ($sidebarTools as $tool)
            <a href="{{ $tool->url() }}" target="_blank" rel="noopener"
               class="group -mx-2 flex items-center gap-2.5 rounded-lg px-2 py-2 transition hover:bg-slate-50">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-500 group-hover:bg-brand-50 group-hover:text-brand-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-medium text-slate-800">{{ $tool->getTranslation('name', $locale) }}</span>
                    <span class="block truncate text-xs text-slate-400">{{ ucfirst($tool->type) }}</span>
                </span>
            </a>
        @empty
            <p class="text-sm text-slate-400">{{ __('Sin materiales generales.') }}</p>
        @endforelse
    </div>

    @if($sidebarSurveys->isNotEmpty())
        <div class="pm-card p-5">
            <h3 class="mb-3 text-sm font-semibold text-slate-900">{{ __('Encuestas') }}</h3>
            @foreach ($sidebarSurveys as $survey)
                <a href="{{ $survey->external_url }}" target="_blank" rel="noopener"
                   class="-mx-2 flex items-center justify-between gap-2 rounded-lg px-2 py-2 transition hover:bg-slate-50">
                    <span class="truncate text-sm text-slate-700">{{ $survey->getTranslation('name', $locale) }}</span>
                    <span class="shrink-0 text-xs font-medium text-brand-600">{{ __('Abrir') }} →</span>
                </a>
            @endforeach
        </div>
    @endif
</aside>
