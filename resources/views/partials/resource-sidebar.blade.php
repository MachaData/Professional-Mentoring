@php
    $locale = app()->getLocale();
    // General program materials only — per-session files live on their session
    // card. Grouped by category so the list stays readable as it grows;
    // uncategorised ones fall into a single trailing group.
    $groups = $sidebarTools->groupBy(fn ($tool) => $tool->category ?: '');
    // Language chips only make sense once there is something to choose between:
    // one language (or none at all) leaves the list as it was.
    $languages = $sidebarTools->pluck('language')->filter()->unique()->sort()->values();
    $showLanguageFilter = $languages->count() > 1
        || ($languages->count() === 1 && $sidebarTools->contains(fn ($t) => blank($t->language)));
@endphp
{{-- Right sidebar: program-wide materials only. Expects $sidebarTools --}}
<aside class="lg:sticky lg:top-20">
    <h2 class="mb-4 text-lg font-semibold">{{ __('Materiales') }}</h2>

    <div class="pm-card p-4" data-materials>
        @if($showLanguageFilter)
            <div class="mb-3 flex flex-wrap gap-1.5 border-b border-slate-100 pb-3">
                <button type="button" data-lang-filter="" aria-pressed="true"
                        class="rounded-full border border-brand-200 bg-brand-50 px-2.5 py-1 text-[11px] font-semibold text-brand-700 transition">
                    {{ __('Todos') }}
                </button>
                @foreach ($languages as $language)
                    <button type="button" data-lang-filter="{{ $language }}" aria-pressed="false"
                            class="rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-semibold uppercase text-slate-500 transition hover:border-brand-200 hover:text-brand-600">
                        {{ strtoupper($language) }}
                    </button>
                @endforeach
            </div>
        @endif

        @forelse ($groups as $category => $tools)
            <div data-lang-group>
                @if($category !== '' && $groups->count() > 1)
                    <p class="mb-1 mt-3 px-2 text-xs font-semibold uppercase tracking-wide text-slate-400 first:mt-0">
                        {{ ucfirst(str_replace('_', ' ', $category)) }}
                    </p>
                @endif
                @foreach ($tools as $tool)
                    <a href="{{ $tool->url() }}" target="_blank" rel="noopener" data-lang="{{ $tool->language }}"
                       class="group -mx-2 flex items-center gap-2.5 rounded-lg px-2 py-2 transition hover:bg-slate-50">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-500 group-hover:bg-brand-50 group-hover:text-brand-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center gap-1.5">
                                <span class="min-w-0 truncate text-sm font-medium text-slate-800">{{ $tool->getTranslation('name', $locale) }}</span>
                                <x-lang-badge :tool="$tool" />
                            </span>
                            <span class="block truncate text-xs text-slate-400">{{ ucfirst(str_replace('_', ' ', $tool->type)) }}</span>
                        </span>
                        <svg class="h-4 w-4 shrink-0 text-slate-300 transition group-hover:text-brand-500" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                    </a>
                @endforeach
            </div>
        @empty
            <p class="px-2 py-4 text-center text-sm text-slate-400">{{ __('Sin materiales generales.') }}</p>
        @endforelse

        @if($showLanguageFilter)
            <p class="hidden px-2 py-4 text-center text-sm text-slate-400" data-lang-empty>
                {{ __('Sin materiales en ese idioma.') }}
            </p>
        @endif
    </div>

    {{-- Private files (dupla), general ones, below Materiales --}}
    @isset($assignment)
        <div class="mt-6">
            @livewire('private-files', ['assignment' => $assignment], key('files-general-'.$assignment->id))
        </div>
    @endisset
</aside>

@once
    @push('scripts')
        <script>
            // Language chips for the general materials list. Filtering happens in
            // the browser: the list is short and already loaded, so there is no
            // reason to round-trip. A material with no language always shows —
            // it is not tied to any language in particular.
            document.querySelectorAll('[data-materials]').forEach((box) => {
                const chips = box.querySelectorAll('[data-lang-filter]');
                if (!chips.length) return;

                const on = ['border-brand-200', 'bg-brand-50', 'text-brand-700'];
                const off = ['border-slate-200', 'bg-white', 'text-slate-500'];

                chips.forEach((chip) => chip.addEventListener('click', () => {
                    const wanted = chip.dataset.langFilter;

                    chips.forEach((other) => {
                        const active = other === chip;
                        other.setAttribute('aria-pressed', active ? 'true' : 'false');
                        other.classList.remove(...(active ? off : on));
                        other.classList.add(...(active ? on : off));
                    });

                    let visible = 0;
                    box.querySelectorAll('[data-lang]').forEach((item) => {
                        const show = !wanted || !item.dataset.lang || item.dataset.lang === wanted;
                        item.classList.toggle('hidden', !show);
                        if (show) visible++;
                    });

                    // Hide a category heading whose materials were all filtered out.
                    box.querySelectorAll('[data-lang-group]').forEach((group) => {
                        const any = group.querySelector('[data-lang]:not(.hidden)');
                        group.classList.toggle('hidden', !any);
                    });

                    const empty = box.querySelector('[data-lang-empty]');
                    if (empty) empty.classList.toggle('hidden', visible > 0);
                }));
            });
        </script>
    @endpush
@endonce
