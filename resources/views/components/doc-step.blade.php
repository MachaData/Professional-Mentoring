{{-- Numbered step for the in-app manuals. Wrap in <ol style="counter-reset: step">. --}}
<li {{ $attributes->merge(['class' => 'doc-step flex gap-3']) }}>
    <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-brand-600 text-xs font-semibold text-white doc-step-num"></span>
    <div class="pt-0.5">{{ $slot }}</div>
</li>
