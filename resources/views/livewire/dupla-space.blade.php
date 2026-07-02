@php
    $locale = app()->getLocale();
    $me = auth()->id();
    $typeLabels = App\Models\SharedFile::TYPES;
@endphp
<div>
    {{-- Tabs --}}
    <div class="mb-5 inline-flex rounded-xl border border-slate-200 bg-white p-1 text-sm font-medium">
        <button wire:click="$set('tab','messages')"
                class="rounded-lg px-4 py-1.5 transition {{ $tab === 'messages' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:text-slate-800' }}">
            {{ __('Mensajes') }}
        </button>
        <button wire:click="$set('tab','files')"
                class="rounded-lg px-4 py-1.5 transition {{ $tab === 'files' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:text-slate-800' }}">
            {{ __('Archivos') }}
        </button>
    </div>

    {{-- ============ MESSAGES ============ --}}
    @if($tab === 'messages')
        <div class="pm-card flex flex-col" style="height: 60vh">
            <div class="flex-1 space-y-3 overflow-y-auto p-5">
                @forelse ($this->messages as $message)
                    @php $mine = $message->sender_id === $me; @endphp
                    <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[80%] rounded-2xl px-4 py-2.5 text-sm {{ $mine ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-800' }}">
                            <div class="mb-0.5 flex items-center gap-2 text-xs {{ $mine ? 'text-white/70' : 'text-slate-400' }}">
                                <span class="font-medium">{{ $message->sender->name }}</span>
                                <span>·</span>
                                <span>{{ $message->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            @if($message->body)<p class="whitespace-pre-line">{{ $message->body }}</p>@endif
                            @if($message->attachment_path)
                                <a href="{{ $message->attachmentUrl() }}" target="_blank" rel="noopener"
                                   class="mt-1.5 inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-xs {{ $mine ? 'bg-white/15' : 'bg-white' }}">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/></svg>
                                    {{ $message->attachment_name }}
                                </a>
                            @endif
                            @if($mine)
                                <div class="mt-0.5 text-right text-[11px] text-white/60">
                                    {{ $message->isRead() ? '✓✓ '.__('Leído') : '✓ '.__('Enviado') }}
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="grid h-full place-items-center text-center text-sm text-slate-400">
                        {{ __('Aún no hay mensajes. Inicia la conversación.') }}
                    </div>
                @endforelse
            </div>

            <form wire:submit="sendMessage" class="border-t border-slate-200 p-3">
                @if($messageAttachment)
                    <div class="mb-2 flex items-center gap-2 text-xs text-slate-500">
                        <span class="rounded bg-slate-100 px-2 py-1">{{ $messageAttachment->getClientOriginalName() }}</span>
                        <button type="button" wire:click="$set('messageAttachment', null)" class="text-brand-600">✕</button>
                    </div>
                @endif
                <div class="flex items-end gap-2">
                    <label class="grid h-10 w-10 shrink-0 cursor-pointer place-items-center rounded-xl border border-slate-200 text-slate-500 hover:bg-slate-50">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/></svg>
                        <input type="file" wire:model="messageAttachment" class="hidden">
                    </label>
                    <textarea wire:model="body" rows="1" placeholder="{{ __('Escribe un mensaje…') }}"
                              class="pm-input flex-1 resize-none"></textarea>
                    <button type="submit" class="pm-btn-brand h-10 shrink-0">
                        <span wire:loading.remove wire:target="sendMessage">{{ __('Enviar') }}</span>
                        <span wire:loading wire:target="sendMessage">…</span>
                    </button>
                </div>
                @error('body') <p class="mt-1 text-xs text-brand-600">{{ $message }}</p> @enderror
                @error('messageAttachment') <p class="mt-1 text-xs text-brand-600">{{ $message }}</p> @enderror
            </form>
        </div>
    @endif

    {{-- ============ FILES ============ --}}
    @if($tab === 'files')
        <div class="grid gap-6 lg:grid-cols-[1fr_340px]">
            {{-- List --}}
            <div>
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-700">{{ __('Archivos compartidos') }}</h3>
                    <select wire:model.live="filterSession" class="pm-input !w-auto !py-1.5 text-xs">
                        <option value="">{{ __('Todas las sesiones') }}</option>
                        <option value="general">{{ __('Generales') }}</option>
                        @foreach ($this->sessions as $s)
                            <option value="{{ $s->id }}">{{ $s->getTranslation('name', $locale) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2">
                    @forelse ($this->files as $file)
                        <div class="pm-card flex items-start justify-between gap-3 p-4">
                            <div class="flex items-start gap-3">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-600 text-xs font-semibold">
                                    {{ strtoupper(substr($file->type, 0, 3)) }}
                                </span>
                                <div class="min-w-0">
                                    <div class="font-medium text-slate-900">{{ $file->title }}</div>
                                    <div class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-slate-400">
                                        <span>{{ $typeLabels[$file->type] ?? $file->type }}</span>
                                        <span>·</span>
                                        <span>{{ $file->uploader->name }}</span>
                                        <span>·</span>
                                        <span>{{ $file->created_at->format('d/m/Y H:i') }}</span>
                                        @if($file->session)
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-slate-500">{{ $file->session->getTranslation('name', $locale) }}</span>
                                        @endif
                                    </div>
                                    @if($file->type === 'text')
                                        <p class="mt-1.5 whitespace-pre-line text-sm text-slate-600">{{ $file->body_text }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                @if($file->url())
                                    <a href="{{ $file->url() }}" target="_blank" rel="noopener" class="pm-btn-ghost !px-3 !py-1.5 text-xs">{{ __('Abrir') }}</a>
                                @endif
                                @if($file->uploaded_by === $me)
                                    <button wire:click="deleteFile({{ $file->id }})" wire:confirm="{{ __('¿Eliminar este archivo?') }}"
                                            class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 hover:bg-rose-50 hover:text-rose-600">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="pm-card p-8 text-center text-sm text-slate-400">{{ __('Aún no hay archivos compartidos.') }}</div>
                    @endforelse
                </div>
            </div>

            {{-- Share form --}}
            <div class="pm-card h-fit p-5">
                <h3 class="mb-3 text-sm font-semibold text-slate-700">{{ __('Compartir') }}</h3>
                <form wire:submit="shareFile" class="space-y-3">
                    <div>
                        <label class="pm-label">{{ __('Título') }}</label>
                        <input type="text" wire:model="fileTitle" class="pm-input">
                        @error('fileTitle') <p class="mt-1 text-xs text-brand-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="pm-label">{{ __('Tipo') }}</label>
                        <select wire:model.live="fileKind" class="pm-input">
                            <option value="file">{{ __('Archivo (PDF, Word, Excel, PPT, imagen)') }}</option>
                            <option value="link">{{ __('Enlace externo') }}</option>
                            <option value="text">{{ __('Solo texto') }}</option>
                        </select>
                    </div>

                    @if($fileKind === 'file')
                        <div>
                            <input type="file" wire:model="upload"
                                   class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100">
                            <div wire:loading wire:target="upload" class="mt-1 text-xs text-slate-400">{{ __('Subiendo…') }}</div>
                            @error('upload') <p class="mt-1 text-xs text-brand-600">{{ $message }}</p> @enderror
                        </div>
                    @elseif($fileKind === 'link')
                        <div>
                            <input type="url" wire:model="externalUrl" placeholder="https://" class="pm-input">
                            @error('externalUrl') <p class="mt-1 text-xs text-brand-600">{{ $message }}</p> @enderror
                        </div>
                    @else
                        <div>
                            <textarea wire:model="bodyText" rows="3" class="pm-input"></textarea>
                            @error('bodyText') <p class="mt-1 text-xs text-brand-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div>
                        <label class="pm-label">{{ __('Asociar a sesión (opcional)') }}</label>
                        <select wire:model="fileSessionId" class="pm-input">
                            <option value="">{{ __('General') }}</option>
                            @foreach ($this->sessions as $s)
                                <option value="{{ $s->id }}">{{ $s->getTranslation('name', $locale) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="pm-btn-brand w-full">
                        <span wire:loading.remove wire:target="shareFile">{{ __('Compartir') }}</span>
                        <span wire:loading wire:target="shareFile">…</span>
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>
