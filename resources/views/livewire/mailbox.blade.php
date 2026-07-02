@php $me = auth()->id(); @endphp
<div class="grid gap-4 lg:grid-cols-[360px_1fr]">
    {{-- Inbox list --}}
    <div class="pm-card flex flex-col overflow-hidden" style="max-height: 68vh">
        <div class="flex items-center justify-between gap-2 border-b border-slate-200 p-3">
            <div class="inline-flex rounded-lg bg-slate-100 p-0.5 text-xs font-medium">
                <button wire:click="$set('filter','all')" class="rounded-md px-2.5 py-1 {{ $filter === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500' }}">{{ __('Todos') }}</button>
                <button wire:click="$set('filter','unread')" class="rounded-md px-2.5 py-1 {{ $filter === 'unread' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500' }}">
                    {{ __('No leídos') }}@if($this->unreadCount) <span class="ml-1 rounded-full bg-brand-600 px-1.5 text-[10px] text-white">{{ $this->unreadCount }}</span>@endif
                </button>
            </div>
            <button wire:click="startCompose" class="pm-btn-brand !px-3 !py-1.5 text-xs">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                {{ __('Redactar') }}
            </button>
        </div>

        <div class="flex-1 divide-y divide-slate-100 overflow-y-auto">
            @forelse ($this->messages as $message)
                @php
                    $mine = $message->sender_id === $me;
                    $unread = ! $mine && $message->read_at === null;
                    $active = $openId === $message->id;
                @endphp
                <button wire:click="open({{ $message->id }})"
                        class="flex w-full flex-col gap-0.5 px-4 py-3 text-left transition hover:bg-slate-50 {{ $active ? 'bg-brand-50/60' : '' }}">
                    <div class="flex items-center justify-between gap-2">
                        <span class="flex items-center gap-1.5 truncate text-sm {{ $unread ? 'font-bold text-slate-900' : 'font-medium text-slate-700' }}">
                            @if($unread)<span class="h-2 w-2 shrink-0 rounded-full bg-brand-600"></span>@endif
                            {{ $mine ? __('Para').' '.($message->assignment->facilitator_id === $me ? $message->assignment->participant->name : $message->assignment->facilitator->name) : $message->sender->name }}
                        </span>
                        <span class="shrink-0 text-[11px] text-slate-400">{{ $message->created_at->format('d/m H:i') }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        @if($message->attachment_path)
                            <svg class="h-3.5 w-3.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32"/></svg>
                        @endif
                        <span class="truncate text-sm {{ $unread ? 'font-semibold text-slate-800' : 'text-slate-500' }}">{{ $message->subject ?: '(sin asunto)' }}</span>
                    </div>
                </button>
            @empty
                <div class="grid h-40 place-items-center px-4 text-center text-sm text-slate-400">{{ __('No hay mensajes.') }}</div>
            @endforelse
        </div>
    </div>

    {{-- Reading / compose pane --}}
    <div class="pm-card p-6" style="min-height: 40vh">
        @if($composing)
            <form wire:submit="send" class="space-y-4">
                <h3 class="text-lg font-semibold">{{ __('Nuevo mensaje') }}</h3>
                <div>
                    <label class="pm-label">{{ __('Asunto') }} <span class="text-brand-600">*</span></label>
                    <input type="text" wire:model="subject" class="pm-input">
                    @error('subject') <p class="mt-1 text-xs text-brand-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="pm-label">{{ __('Mensaje') }}</label>
                    <textarea wire:model="bodyText" rows="6" class="pm-input"></textarea>
                </div>
                <div>
                    <label class="pm-label">{{ __('Adjuntar archivo') }}</label>
                    <input type="file" wire:model="attachment"
                           class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100">
                    <div wire:loading wire:target="attachment" class="mt-1 text-xs text-slate-400">{{ __('Subiendo…') }}</div>
                    @error('attachment') <p class="mt-1 text-xs text-brand-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit" class="pm-btn-brand">{{ __('Enviar') }}</button>
                    <button type="button" wire:click="cancelCompose" class="pm-btn-ghost">{{ __('Cancelar') }}</button>
                </div>
            </form>
        @elseif($this->openMessage)
            @php $msg = $this->openMessage; $mine = $msg->sender_id === $me; @endphp
            <div>
                <h2 class="text-xl font-bold text-slate-900">{{ $msg->subject ?: '(sin asunto)' }}</h2>
                <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                    <span class="font-medium text-slate-700">{{ $msg->sender->name }}</span>
                    <span>·</span>
                    <span>{{ $msg->created_at->format('d/m/Y H:i') }}</span>
                    @if($mine)
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ $msg->read_at ? '✓✓ '.__('Leído') : '✓ '.__('Enviado') }}</span>
                    @endif
                </div>
                <div class="mt-4 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $msg->body ?: '—' }}</div>
                @if($msg->attachment_path)
                    <a href="{{ $msg->attachmentUrl() }}" target="_blank" rel="noopener"
                       class="mt-5 inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-100">
                        <svg class="h-4 w-4 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32"/></svg>
                        {{ $msg->attachment_name }}
                    </a>
                @endif
                <div class="mt-6">
                    <button wire:click="startCompose" class="pm-btn-ghost text-sm">{{ __('Escribir mensaje') }}</button>
                </div>
            </div>
        @else
            <div class="grid h-full place-items-center text-center">
                <div>
                    <p class="text-sm text-slate-400">{{ __('Selecciona un mensaje para leerlo') }}</p>
                    <button wire:click="startCompose" class="pm-btn-brand mt-3">{{ __('Redactar mensaje') }}</button>
                </div>
            </div>
        @endif
    </div>
</div>
