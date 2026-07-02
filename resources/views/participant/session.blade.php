@extends('layouts.portal')
@section('title', $session->getTranslation('name', app()->getLocale()))

@section('content')
@php
    $locale = app()->getLocale();
    $done = $record && $record->status === 'completed';
    $attendance = $record?->attendance ?? 'pending';
    $attColors = [
        'attended' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'absent' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'rescheduled' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'pending' => 'bg-slate-100 text-slate-600 ring-slate-200',
    ];
@endphp

<a href="{{ route('participant.dashboard') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 transition hover:text-slate-900">
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
    {{ __('Volver') }}
</a>

<div class="mx-auto mt-4 max-w-3xl space-y-6">
    {{-- Header --}}
    <div class="pm-card overflow-hidden">
        <div class="relative bg-gradient-to-br from-brand-600 to-brand-800 p-6 text-white">
            <div class="pm-grid-bg absolute inset-0 opacity-30"></div>
            <div class="relative">
                <div class="flex flex-wrap items-center gap-2 text-sm text-white/70">
                    <span class="rounded-full bg-white/15 px-2 py-0.5 font-display text-xs font-semibold">S{{ $session->number }}</span>
                    @if($session->stage)<span>{{ $session->stage->getTranslation('name', $locale) }}</span>@endif
                </div>
                <h1 class="mt-2 text-2xl font-bold text-white">{{ $session->getTranslation('name', $locale) }}</h1>
                <p class="mt-1 text-white/75">{{ $session->getTranslation('objective', $locale) }}</p>
            </div>
        </div>

        <div class="grid gap-4 p-6 sm:grid-cols-2">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('Fechas') }}</div>
                <div class="mt-1 text-sm text-slate-700">
                    @if($session->start_date)
                        {{ $session->start_date->format('d/m/Y') }} – {{ $session->end_date?->format('d/m/Y') }}
                    @else — @endif
                </div>
                @if($record?->real_session_date)
                    <div class="mt-1 text-xs text-slate-400">{{ __('Sesión realizada') }}: {{ $record->real_session_date->format('d/m/Y') }}</div>
                @endif
            </div>
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('Estado') }}</div>
                <div class="mt-1 flex flex-wrap gap-2">
                    <span class="pm-pill ring-1 {{ $attColors[$attendance] ?? $attColors['pending'] }}">
                        {{ __(App\Models\SessionRecord::ATTENDANCE[$attendance] ?? 'Pendiente') }}
                    </span>
                    @if($record?->modality)
                        <span class="pm-pill bg-slate-100 text-slate-600">{{ __(App\Models\SessionRecord::MODALITY[$record->modality] ?? '') }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Join + survey --}}
    @if($record?->meeting_url || $session->survey_url)
        <div class="pm-card flex flex-wrap items-center gap-3 p-5">
            @if($record?->meeting_url)
                <a href="{{ $record->meeting_url }}" target="_blank" rel="noopener" class="pm-btn-brand">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/></svg>
                    {{ __('Ingresar a la sesión') }}
                </a>
            @endif
            @if($session->survey_url)
                <a href="{{ $session->survey_url }}" target="_blank" rel="noopener" class="pm-btn-ghost">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/></svg>
                    {{ __('Abrir encuesta') }}
                </a>
            @endif
        </div>
    @endif

    {{-- Indications --}}
    @if($session->getTranslation('description', $locale, false))
        <div class="pm-card p-6">
            <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-400">{{ __('Indicaciones') }}</h2>
            <div class="prose prose-sm max-w-none text-slate-600">{!! nl2br(e($session->getTranslation('description', $locale))) !!}</div>
        </div>
    @endif

    {{-- Materials --}}
    @if($tools->isNotEmpty())
        <div class="pm-card p-6">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-400">{{ __('Materiales de la sesión') }}</h2>
            <div class="space-y-2">
                @foreach ($tools as $tool)
                    <a href="{{ $tool->url() }}" target="_blank" rel="noopener" class="group -mx-2 flex items-center gap-2.5 rounded-lg px-2 py-2 transition hover:bg-slate-50">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-500 group-hover:bg-brand-50 group-hover:text-brand-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                        </span>
                        <span class="min-w-0 flex-1 truncate text-sm font-medium text-slate-800">{{ $tool->getTranslation('name', $locale) }}</span>
                        <svg class="h-4 w-4 shrink-0 text-slate-300 group-hover:text-brand-500" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Private files tied to this session --}}
    <div class="pm-card p-6">
        @livewire('private-files', ['assignment' => $assignment, 'sessionId' => $session->id], key('files-s-'.$session->id))
    </div>

    {{-- Agreements (visible fields from completed record) --}}
    @if($visible->isNotEmpty())
        <div class="pm-card p-6">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-400">{{ __('Acuerdos y próximos pasos') }}</h2>
            <dl class="space-y-2">
                @foreach ($visible as $value)
                    <div class="flex gap-2 text-sm">
                        <dt class="shrink-0 font-medium text-slate-500">{{ $value['label'] }}:</dt>
                        <dd class="text-slate-800">{{ $value['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    @endif
</div>
@endsection
