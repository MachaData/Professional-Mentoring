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
            </div>
        </div>

        <div class="p-6">
            @livewire('mentor.register-session', ['record' => $record])
        </div>
    </div>
</div>
@endsection
