@props(['tool'])
{{-- Language of a material, shown as a reference next to its name. Renders
     nothing when the material has no language set. --}}
@if($tool->languageBadge())
    <span {{ $attributes->merge(['class' => 'shrink-0 rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] font-semibold uppercase leading-none tracking-wide text-slate-500']) }}
          title="{{ $tool->languageLabel() }}">{{ $tool->languageBadge() }}</span>
@endif
