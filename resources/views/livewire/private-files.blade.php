@php $locale = app()->getLocale(); $me = auth()->id(); $typeLabels = App\Models\SharedFile::TYPES; @endphp
<div>
    <div class="mb-3 flex items-center justify-between">
        <h2 class="text-lg font-semibold">{{ __('Archivos privados') }}</h2>
        <button wire:click="$toggle('showForm')" class="pm-btn-ghost !px-3 !py-1.5 text-xs">
            @if($showForm) {{ __('Cerrar') }} @else + {{ __('Agregar') }} @endif
        </button>
    </div>

    @if($showForm)
        <form wire:submit="save" class="pm-card mb-3 space-y-3 p-4">
            <div>
                <label class="pm-label">{{ __('Título') }}</label>
                <input type="text" wire:model="title" class="pm-input">
                @error('title') <p class="mt-1 text-xs text-brand-600">{{ $message }}</p> @enderror
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="pm-label">{{ __('Tipo') }}</label>
                    <select wire:model.live="kind" class="pm-input">
                        <option value="file">{{ __('Archivo') }}</option>
                        <option value="link">{{ __('Enlace') }}</option>
                        <option value="text">{{ __('Texto') }}</option>
                    </select>
                </div>
                @unless($sessionId)
                    <div>
                        <label class="pm-label">{{ __('Sesión (opcional)') }}</label>
                        <select wire:model="targetSessionId" class="pm-input">
                            <option value="">{{ __('General') }}</option>
                            @foreach ($this->sessions as $s)
                                <option value="{{ $s->id }}">{{ $s->getTranslation('name', $locale) }}</option>
                            @endforeach
                        </select>
                    </div>
                @endunless
            </div>

            @if($kind === 'file')
                <input type="file" wire:model="upload"
                       class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100">
                <div wire:loading wire:target="upload" class="text-xs text-slate-400">{{ __('Subiendo…') }}</div>
                @error('upload') <p class="text-xs text-brand-600">{{ $message }}</p> @enderror
            @elseif($kind === 'link')
                <input type="url" wire:model="externalUrl" placeholder="https://" class="pm-input">
                @error('externalUrl') <p class="text-xs text-brand-600">{{ $message }}</p> @enderror
            @else
                <textarea wire:model="bodyText" rows="2" class="pm-input"></textarea>
                @error('bodyText') <p class="text-xs text-brand-600">{{ $message }}</p> @enderror
            @endif

            <button type="submit" class="pm-btn-brand w-full">{{ __('Compartir') }}</button>
        </form>
    @endif

    <div class="space-y-2">
        @forelse ($this->files as $file)
            <div class="pm-card flex items-start justify-between gap-3 p-3">
                <div class="flex min-w-0 items-start gap-2.5">
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-brand-50 text-[10px] font-bold text-brand-600">
                        {{ strtoupper(substr($file->type, 0, 3)) }}
                    </span>
                    <div class="min-w-0">
                        <div class="truncate text-sm font-medium text-slate-900">{{ $file->title }}</div>
                        <div class="flex flex-wrap items-center gap-x-1.5 text-xs text-slate-400">
                            <span>{{ $typeLabels[$file->type] ?? $file->type }}</span>
                            <span>·</span>
                            <span>{{ $file->uploader->name }}</span>
                            @if(! $sessionId && $file->session)
                                <span class="rounded-full bg-slate-100 px-1.5 text-slate-500">{{ $file->session->getTranslation('name', $locale) }}</span>
                            @endif
                        </div>
                        @if($file->type === 'text')
                            <p class="mt-1 whitespace-pre-line text-xs text-slate-600">{{ $file->body_text }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-1">
                    @if($file->url())
                        <a href="{{ $file->url() }}" target="_blank" rel="noopener" class="text-xs font-medium text-brand-600 hover:text-brand-700">{{ __('Abrir') }}</a>
                    @endif
                    @if($file->uploaded_by === $me)
                        <button wire:click="deleteFile({{ $file->id }})" wire:confirm="{{ __('¿Eliminar?') }}" class="grid h-7 w-7 place-items-center rounded-lg text-slate-300 hover:bg-rose-50 hover:text-rose-600" title="{{ __('Eliminar') }}">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165"/></svg>
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <p class="rounded-xl border border-dashed border-slate-200 px-3 py-4 text-center text-xs text-slate-400">{{ __('Sin archivos privados.') }}</p>
        @endforelse
    </div>
</div>
