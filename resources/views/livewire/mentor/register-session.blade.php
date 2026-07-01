@php $locale = app()->getLocale(); @endphp
<div>
    <form wire:submit="complete" class="space-y-5">
        {{-- Per-dupla join link --}}
        <div class="rounded-xl border border-brand-100 bg-brand-50/50 p-4">
            <label class="pm-label flex items-center gap-1.5">
                <svg class="h-4 w-4 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/></svg>
                {{ __('Link de la sesión') }}
            </label>
            <input type="url" wire:model="meetingUrl" class="pm-input" placeholder="https://meet.google.com/…  ·  Zoom  ·  Teams">
            <p class="mt-1 text-xs text-slate-400">{{ __('El participante verá este enlace para ingresar a la reunión.') }}</p>
        </div>

        @foreach ($fields as $field)
            @php
                $key = 'field_'.$field->id;
                $model = 'data.'.$key;
                $label = $field->getTranslation('label', $locale) ?: $field->name;
                $help = $field->getTranslation('help_text', $locale, false);
                $type = $field->field_type->value;
                $err = 'data.'.$key;
            @endphp

            @if ($type === 'heading')
                <h3 class="pt-2 text-base font-semibold text-slate-900">{{ $label }}</h3>
            @elseif ($type === 'separator')
                <hr class="border-slate-200">
            @elseif ($type === 'readonly')
                <div>
                    <div class="pm-label">{{ $label }}</div>
                    <p class="text-sm text-slate-600">{{ $field->default_value }}</p>
                </div>
            @else
                <div>
                    <label class="pm-label">
                        {{ $label }}
                        @if ($field->is_required) <span class="text-brand-600">*</span> @endif
                        @if ($field->is_internal)
                            <span class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-400">{{ __('interno') }}</span>
                        @endif
                    </label>

                    @switch($type)
                        @case('textarea')
                            <textarea wire:model="{{ $model }}" rows="3" class="pm-input"></textarea>
                            @break

                        @case('date')
                            <input type="date" wire:model="{{ $model }}" class="pm-input">
                            @break

                        @case('time')
                            <input type="time" wire:model="{{ $model }}" class="pm-input">
                            @break

                        @case('number')
                            <input type="number" wire:model="{{ $model }}" class="pm-input">
                            @break

                        @case('url')
                            <input type="url" wire:model="{{ $model }}" class="pm-input" placeholder="https://">
                            @break

                        @case('rating')
                            <select wire:model="{{ $model }}" class="pm-input">
                                <option value="">—</option>
                                @for ($i = 1; $i <= 5; $i++)<option value="{{ $i }}">{{ $i }}</option>@endfor
                            </select>
                            @break

                        @case('select')
                            <select wire:model="{{ $model }}" class="pm-input">
                                <option value="">{{ __('Seleccione…') }}</option>
                                @foreach ($field->resolvedOptions($locale) as $value => $optLabel)
                                    <option value="{{ $value }}">{{ $optLabel }}</option>
                                @endforeach
                            </select>
                            @break

                        @case('multiselect')
                            <select wire:model="{{ $model }}" multiple class="pm-input min-h-24">
                                @foreach ($field->resolvedOptions($locale) as $value => $optLabel)
                                    <option value="{{ $value }}">{{ $optLabel }}</option>
                                @endforeach
                            </select>
                            @break

                        @case('radio')
                            <div class="space-y-1.5">
                                @foreach ($field->resolvedOptions($locale) as $value => $optLabel)
                                    <label class="flex items-center gap-2 text-sm text-slate-700">
                                        <input type="radio" wire:model="{{ $model }}" value="{{ $value }}" class="border-slate-300 text-brand-600 focus:ring-brand-500">
                                        {{ $optLabel }}
                                    </label>
                                @endforeach
                            </div>
                            @break

                        @case('checkbox')
                            <div class="space-y-1.5">
                                @foreach ($field->resolvedOptions($locale) as $value => $optLabel)
                                    <label class="flex items-center gap-2 text-sm text-slate-700">
                                        <input type="checkbox" wire:model="{{ $model }}" value="{{ $value }}" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                        {{ $optLabel }}
                                    </label>
                                @endforeach
                            </div>
                            @break

                        @case('file')
                            <input type="file" wire:model="{{ $model }}" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100">
                            @break

                        @default
                            <input type="text" wire:model="{{ $model }}" class="pm-input">
                    @endswitch

                    @if ($help)
                        <p class="mt-1 text-xs text-slate-400">{{ $help }}</p>
                    @endif
                    @error($err) <p class="mt-1 text-xs text-brand-600">{{ $message }}</p> @enderror
                </div>
            @endif
        @endforeach

        <div class="sticky bottom-0 -mx-6 -mb-6 flex items-center gap-3 border-t border-slate-200 bg-white/90 px-6 py-4 backdrop-blur">
            <button type="submit" class="pm-btn-brand">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                {{ __('Registrar sesión') }}
            </button>
            <button type="button" wire:click="saveDraft" class="pm-btn-ghost">{{ __('Guardar borrador') }}</button>
            <span wire:loading class="text-sm text-slate-400">{{ __('Guardando…') }}</span>
        </div>
    </form>
</div>
