@php $locale = app()->getLocale(); @endphp

@if($tools->isNotEmpty())
    <h2 class="mb-3 text-lg font-semibold">{{ __('Materiales y herramientas') }}</h2>
    <div class="mb-8 grid gap-3 sm:grid-cols-2">
        @foreach ($tools as $tool)
            <a href="{{ $tool->url() }}" target="_blank" rel="noopener" class="pm-card pm-card-hover group flex items-center gap-3 p-4">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                </span>
                <div class="min-w-0 flex-1">
                    <div class="truncate font-medium text-slate-900">{{ $tool->getTranslation('name', $locale) }}</div>
                    <div class="truncate text-sm text-slate-500">{{ $tool->getTranslation('description', $locale) }}</div>
                </div>
                <svg class="h-4 w-4 shrink-0 text-slate-300 transition group-hover:text-brand-500" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
            </a>
        @endforeach
    </div>
@endif

@if($surveys->isNotEmpty())
    <h2 class="mb-3 text-lg font-semibold">{{ __('Encuestas') }}</h2>
    <div class="mb-8 space-y-3">
        @foreach ($surveys as $survey)
            <a href="{{ $survey->external_url }}" target="_blank" rel="noopener" class="pm-card pm-card-hover flex items-center justify-between gap-3 p-4">
                <div class="min-w-0">
                    <div class="truncate font-medium text-slate-900">{{ $survey->getTranslation('name', $locale) }}</div>
                    <div class="truncate text-sm text-slate-500">{{ $survey->getTranslation('description', $locale) }}</div>
                </div>
                <span class="pm-btn-brand shrink-0 !py-2 !px-3.5 text-xs">{{ __('Abrir') }}</span>
            </a>
        @endforeach
    </div>
@endif
