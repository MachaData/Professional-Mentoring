@extends('layouts.portal')
@section('title', __('Cronograma'))

@section('content')
@php $locale = app()->getLocale(); @endphp

<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <p class="text-sm font-medium text-brand-600">{{ __('Mi programa') }}</p>
        <h1 class="mt-1 text-3xl font-bold">{{ __('Cronograma') }}</h1>
    </div>
    <a href="{{ route('participant.dashboard') }}" class="pm-btn-ghost">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
        {{ __('Volver') }}
    </a>
</div>

@if(! $assignment)
    <div class="pm-card grid place-items-center gap-2 p-12 text-center">
        <p class="text-sm text-slate-500">{{ __('Aún no estás asignado a un programa.') }}</p>
    </div>
@else
    {{-- Indicadores básicos --}}
    @php
        $cards = [
            ['label' => __('Sesiones'), 'value' => $stats['total'], 'tone' => 'slate'],
            ['label' => __('Completadas'), 'value' => $stats['completed'], 'tone' => 'emerald'],
            ['label' => __('Pendientes'), 'value' => $stats['pending'], 'tone' => 'indigo'],
            ['label' => __('Vencidas'), 'value' => $stats['expired'], 'tone' => 'rose'],
        ];
        $tones = ['slate' => 'text-slate-900', 'emerald' => 'text-emerald-600', 'indigo' => 'text-indigo-600', 'rose' => 'text-rose-600'];
    @endphp
    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
        @foreach ($cards as $card)
            <div class="pm-card p-4">
                <div class="text-2xl font-bold {{ $tones[$card['tone']] }}">{{ $card['value'] }}</div>
                <div class="text-sm text-slate-500">{{ $card['label'] }}</div>
            </div>
        @endforeach
    </div>

    @if($next)
        <div class="mb-6 flex items-center gap-3 rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-900">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
            <span>
                <span class="font-semibold">{{ __('Próxima sesión') }}:</span>
                S{{ $next->number }} · {{ $next->getTranslation('name', $locale) }}
                @if($next->start_date) — {{ $next->start_date->format('d/m/Y') }} @endif
            </span>
            <a href="{{ route('participant.session', $next) }}" class="ml-auto shrink-0 font-medium hover:underline">{{ __('Ver') }} →</a>
        </div>
    @endif

    @include('partials.calendar', ['month' => $month, 'events' => $events, 'prevUrl' => $prevUrl, 'nextUrl' => $nextUrl, 'todayUrl' => $todayUrl])
@endif
@endsection
