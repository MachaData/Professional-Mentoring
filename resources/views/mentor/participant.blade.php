@extends('layouts.portal')
@section('title', $assignment->participant->name)

@section('content')
@php
    $locale = app()->getLocale();
    $badges = [
        'pending' => ['bg-amber-100 text-amber-700', __('Pendiente')],
        'draft' => ['bg-blue-100 text-blue-700', __('Borrador')],
        'completed' => ['bg-green-100 text-green-700', __('Completada')],
        'expired' => ['bg-red-100 text-red-700', __('Vencida')],
        'rescheduled' => ['bg-purple-100 text-purple-700', __('Reprogramada')],
        'cancelled' => ['bg-gray-100 text-gray-600', __('Cancelada')],
    ];
@endphp

<a href="{{ route('mentor.dashboard') }}" class="text-sm text-gray-500 hover:text-gray-900">&larr; {{ __('Volver') }}</a>

<div class="mt-2 mb-6">
    <h1 class="text-2xl font-bold">{{ $assignment->participant->name }}</h1>
    <p class="text-gray-500">{{ $assignment->program->getTranslation('name', $locale) }}</p>
    @if($assignment->participant->email)
        <p class="text-sm text-gray-400">{{ $assignment->participant->email }} · {{ $assignment->participant->phone }}</p>
    @endif
</div>

<div class="bg-white rounded-xl border border-gray-200 divide-y">
    @foreach ($sessions as $row)
        @php
            $session = $row['session'];
            $status = $row['status'];
            [$badgeClass, $badgeLabel] = $badges[$status] ?? $badges['pending'];
        @endphp
        <div class="flex items-center justify-between px-5 py-4">
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-gray-400">#{{ $session->number }}</span>
                    <span class="font-medium">{{ $session->getTranslation('name', $locale) }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $badgeClass }}">{{ $badgeLabel }}</span>
                </div>
                <div class="text-sm text-gray-500 truncate">{{ $session->getTranslation('objective', $locale) }}</div>
                @if($session->start_date)
                    <div class="text-xs text-gray-400 mt-0.5">
                        {{ $session->start_date->format('d/m/Y') }} – {{ $session->end_date?->format('d/m/Y') }}
                    </div>
                @endif
            </div>
            @if($row['record'])
                <a href="{{ route('mentor.register', $row['record']) }}"
                   class="shrink-0 rounded-lg px-4 py-2 text-sm font-medium text-white transition hover:opacity-90"
                   style="background: var(--brand)">
                    {{ $status === 'completed' ? __('Ver / editar') : __('Registrar') }}
                </a>
            @endif
        </div>
    @endforeach
</div>
@endsection
