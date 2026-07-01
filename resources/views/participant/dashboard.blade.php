@extends('layouts.portal')
@section('title', __('Mi programa'))

@section('content')
@php $locale = app()->getLocale(); @endphp

<h1 class="text-2xl font-bold mb-6">{{ __('Hola') }}, {{ $participant->name }}</h1>

@if(! $assignment)
    <div class="bg-white rounded-xl border border-gray-200 px-5 py-8 text-center text-gray-500">
        {{ __('Aún no estás asignado a un programa.') }}
    </div>
@else
    <div class="grid md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-5 md:col-span-2">
            <div class="text-sm text-gray-500">{{ __('Programa') }}</div>
            <div class="text-lg font-semibold">{{ $assignment->program->getTranslation('name', $locale) }}</div>
            @if($assignment->program->start_date)
                <div class="text-sm text-gray-400 mt-1">
                    {{ $assignment->program->start_date->format('d/m/Y') }} –
                    {{ $assignment->program->end_date?->format('d/m/Y') }}
                </div>
            @endif
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-sm text-gray-500">{{ $assignment->program->participantLabel($locale) === 'Mentee' ? __('Mi mentor') : __('Mi facilitador') }}</div>
            <div class="text-lg font-semibold">{{ $assignment->facilitator->name }}</div>
            <div class="text-sm text-gray-400">{{ $assignment->facilitator->email }}</div>
        </div>
    </div>

    @include('partials.resources', ['tools' => $tools, 'surveys' => $surveys])

    <h2 class="text-lg font-semibold mb-3">{{ __('Mis sesiones') }}</h2>
    <div class="space-y-4">
        @foreach ($sessions as $row)
            @php $session = $row['session']; $record = $row['record']; @endphp
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-gray-400">#{{ $session->number }}</span>
                        <span class="font-medium ml-1">{{ $session->getTranslation('name', $locale) }}</span>
                    </div>
                    @if($record && $record->status === 'completed')
                        <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700">{{ __('Realizada') }}</span>
                    @elseif($session->start_date)
                        <span class="text-xs text-gray-400">
                            {{ $session->start_date->format('d/m/Y') }} – {{ $session->end_date?->format('d/m/Y') }}
                        </span>
                    @endif
                </div>
                <p class="text-sm text-gray-500 mt-1">{{ $session->getTranslation('objective', $locale) }}</p>

                @if($row['visible_values']->isNotEmpty())
                    <div class="mt-4 border-t border-gray-100 pt-3 space-y-2">
                        <div class="text-xs font-semibold text-gray-400 uppercase">{{ __('Acuerdos y próximos pasos') }}</div>
                        @foreach ($row['visible_values'] as $value)
                            <div class="text-sm">
                                <span class="text-gray-500">{{ $value['label'] }}:</span>
                                <span class="text-gray-800">{{ $value['value'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endif
@endsection
