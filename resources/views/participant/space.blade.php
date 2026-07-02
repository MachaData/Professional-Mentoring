@extends('layouts.portal')
@section('title', __('Mensajes y archivos'))

@section('content')
@php $locale = app()->getLocale(); @endphp

<a href="{{ route('participant.dashboard') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 transition hover:text-slate-900">
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
    {{ __('Volver') }}
</a>

<div class="mt-2 mb-6">
    <h1 class="text-2xl font-bold">{{ __('Mensajes y archivos') }}</h1>
    <p class="text-sm text-slate-500">{{ __('Con tu mentor') }} {{ $assignment->facilitator->name }}</p>
</div>

@livewire('dupla-space', ['assignment' => $assignment])
@endsection
