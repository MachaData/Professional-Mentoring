@php $locale = app()->getLocale(); @endphp
{{-- Right sidebar: program-wide materials only. Expects $sidebarTools --}}
<aside class="lg:sticky lg:top-20">
    <h2 class="mb-4 text-lg font-semibold">{{ __('Materiales') }}</h2>

    <div class="pm-card p-4">
        @forelse ($sidebarTools as $tool)
            <a href="{{ $tool->url() }}" target="_blank" rel="noopener"
               class="group -mx-2 flex items-center gap-2.5 rounded-lg px-2 py-2 transition hover:bg-slate-50">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-500 group-hover:bg-brand-50 group-hover:text-brand-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-medium text-slate-800">{{ $tool->getTranslation('name', $locale) }}</span>
                    <span class="block truncate text-xs text-slate-400">{{ ucfirst(str_replace('_', ' ', $tool->type)) }}</span>
                </span>
                <svg class="h-4 w-4 shrink-0 text-slate-300 transition group-hover:text-brand-500" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
            </a>
        @empty
            <p class="px-2 py-4 text-center text-sm text-slate-400">{{ __('Sin materiales generales.') }}</p>
        @endforelse
    </div>
</aside>
