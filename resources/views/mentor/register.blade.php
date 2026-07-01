@extends('layouts.portal')
@section('title', __('Registrar sesión'))

@push('styles')
    @filamentStyles
@endpush

@section('content')
@php $locale = app()->getLocale(); @endphp

<a href="{{ route('mentor.participant', $record->assignment_id) }}" class="text-sm text-gray-500 hover:text-gray-900">
    &larr; {{ __('Volver') }}
</a>

<div class="mt-2 mb-6">
    <h1 class="text-2xl font-bold">
        {{ $record->session->getTranslation('name', $locale) }}
    </h1>
    <p class="text-gray-500">{{ $record->session->getTranslation('objective', $locale) }}</p>
</div>

<div class="bg-white rounded-xl border border-gray-200 p-6">
    @livewire('mentor.register-session', ['record' => $record])
</div>
@endsection

@push('scripts')
    @filamentScripts
@endpush
