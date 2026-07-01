@extends('layouts.portal')
@section('title', __('Panel del mentor'))

@section('content')
@php $locale = app()->getLocale(); @endphp

<h1 class="text-2xl font-bold mb-6">{{ __('Hola') }}, {{ $facilitator->name }}</h1>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    @foreach ([
        ['label' => __('Participantes'), 'value' => $stats['participants'], 'color' => 'text-gray-900'],
        ['label' => __('Pendientes'), 'value' => $stats['pending'], 'color' => 'text-amber-600'],
        ['label' => __('Completadas'), 'value' => $stats['completed'], 'color' => 'text-green-600'],
        ['label' => __('Vencidas'), 'value' => $stats['expired'], 'color' => 'text-red-600'],
    ] as $card)
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-3xl font-bold {{ $card['color'] }}">{{ $card['value'] }}</div>
            <div class="text-sm text-gray-500 mt-1">{{ $card['label'] }}</div>
        </div>
    @endforeach
</div>

<h2 class="text-lg font-semibold mb-3">{{ __('Mis participantes') }}</h2>

<div class="bg-white rounded-xl border border-gray-200 divide-y">
    @forelse ($assignments as $assignment)
        @php
            $total = $assignment->records->count();
            $done = $assignment->records->where('status', 'completed')->count();
        @endphp
        <a href="{{ route('mentor.participant', $assignment) }}"
           class="flex items-center justify-between px-5 py-4 hover:bg-gray-50 transition">
            <div>
                <div class="font-medium">{{ $assignment->participant->name }}</div>
                <div class="text-sm text-gray-500">{{ $assignment->program->getTranslation('name', $locale) }}</div>
            </div>
            <div class="text-right">
                <div class="text-sm font-medium">{{ $done }}/{{ $total }} {{ __('sesiones') }}</div>
                <div class="w-32 bg-gray-100 rounded-full h-2 mt-1">
                    <div class="h-2 rounded-full" style="width: {{ $total ? round($done / $total * 100) : 0 }}%; background: var(--brand)"></div>
                </div>
            </div>
        </a>
    @empty
        <div class="px-5 py-8 text-center text-gray-500 text-sm">{{ __('Aún no tienes participantes asignados.') }}</div>
    @endforelse
</div>
@endsection
