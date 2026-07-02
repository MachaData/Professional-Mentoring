@extends('layouts.portal')
@section('title', __('Registrar sesión'))

@section('content')
@php $locale = app()->getLocale(); @endphp

<a href="{{ route('mentor.participant', $record->assignment_id) }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 transition hover:text-slate-900">
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
    {{ __('Volver') }}
</a>

<div class="mx-auto mt-4 max-w-3xl">
    {{-- Session header --}}
    <div class="pm-card overflow-hidden">
        <div class="relative bg-gradient-to-br from-brand-600 to-brand-800 p-6 text-white">
            <div class="pm-grid-bg absolute inset-0 opacity-30"></div>
            <div class="relative">
                <div class="flex items-center gap-2 text-sm text-white/70">
                    <span class="rounded-full bg-white/15 px-2 py-0.5 font-display text-xs font-semibold">S{{ $record->session->number }}</span>
                    @if($record->session->stage)
                        <span>{{ $record->session->stage->getTranslation('name', $locale) }}</span>
                    @endif
                </div>
                <h1 class="mt-2 text-2xl font-bold text-white">{{ $record->session->getTranslation('name', $locale) }}</h1>
                <p class="mt-1 text-white/75">{{ $record->session->getTranslation('objective', $locale) }}</p>
                @if($record->session->start_date)
                    <p class="mt-2 flex items-center gap-1.5 text-sm text-white/70">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                        {{ $record->session->start_date->format('d/m/Y') }} – {{ $record->session->end_date?->format('d/m/Y') }}
                    </p>
                @endif
            </div>
        </div>

        {{-- Session materials + survey (copy to send to the mentee) --}}
        @if($tools->isNotEmpty() || $surveyUrl)
            <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 bg-slate-50/60 px-6 py-4">
                @foreach ($tools as $tool)
                    <a href="{{ $tool->url() }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-brand-200 hover:text-brand-600">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                        {{ $tool->getTranslation('name', $locale) }}
                    </a>
                @endforeach
                @if($surveyUrl)
                    <a href="{{ $surveyUrl }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-100">
                        {{ __('Abrir encuesta') }}
                    </a>
                    <button type="button" data-copied="{{ __('¡Copiado!') }}" onclick="pmCopy(@js($surveyUrl), this)"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-brand-200 hover:text-brand-600">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m11.25 2.25h-3.375c-.621 0-1.125.504-1.125 1.125v3.375"/></svg>
                        <span data-label>{{ __('Copiar encuesta') }}</span>
                    </button>
                @endif
            </div>
        @endif

        <div class="p-6">
            @livewire('mentor.register-session', ['record' => $record])
        </div>
    </div>

    @if($assignment)
        <div class="pm-card mt-6 p-6">
            @livewire('private-files', ['assignment' => $assignment, 'sessionId' => $record->session_id], key('files-s-'.$record->session_id))
        </div>
    @endif
</div>
@endsection
