@extends('layouts.portal')
@section('title', __('Cronograma'))

@section('content')
@php $locale = app()->getLocale(); @endphp

<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <p class="text-sm font-medium text-brand-600">{{ __('Panel del mentor') }}</p>
        <h1 class="mt-1 text-3xl font-bold">{{ __('Cronograma') }}</h1>
    </div>
    <a href="{{ route('mentor.dashboard') }}" class="pm-btn-ghost">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
        {{ __('Volver') }}
    </a>
</div>

@include('partials.calendar', ['month' => $month, 'events' => $events, 'prevUrl' => $prevUrl, 'nextUrl' => $nextUrl, 'todayUrl' => $todayUrl])

{{-- Indicadores básicos por dupla --}}
<div class="mt-8">
    <h2 class="mb-4 text-lg font-semibold">{{ __('Indicadores por dupla') }}</h2>

    @if($rows->isEmpty())
        <div class="pm-card p-8 text-center text-sm text-slate-500">{{ __('Aún no tienes participantes asignados.') }}</div>
    @else
        <div class="pm-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/60 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                            <th class="px-4 py-3">{{ __('Participante') }}</th>
                            <th class="px-4 py-3">{{ __('Programa') }}</th>
                            <th class="px-4 py-3">{{ __('Avance') }}</th>
                            <th class="px-4 py-3">{{ __('Sesión actual') }}</th>
                            <th class="px-4 py-3">{{ __('Estado') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($rows as $row)
                            @php
                                $badge = [
                                    'done' => ['bg-emerald-50 text-emerald-700 ring-emerald-200', __('Finalizado')],
                                    'dentro' => ['bg-indigo-50 text-indigo-700 ring-indigo-200', __('Al día')],
                                    'fuera' => ['bg-rose-50 text-rose-700 ring-rose-200', __('Atrasado')],
                                    'sin_inicio' => ['bg-slate-100 text-slate-600 ring-slate-200', __('Sin iniciar')],
                                ][$row['state']];
                            @endphp
                            <tr class="transition hover:bg-slate-50/60">
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $row['assignment']->participant?->name }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $row['assignment']->program->getTranslation('name', $locale) }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="h-1.5 w-20 overflow-hidden rounded-full bg-slate-100">
                                            <div class="h-full rounded-full bg-brand-600" style="width: {{ $row['percent'] }}%"></div>
                                        </div>
                                        <span class="text-xs text-slate-400">{{ $row['completed'] }}/{{ $row['total'] }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ $row['current'] ? 'S'.$row['current']->number.' · '.$row['current']->getTranslation('name', $locale) : '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="pm-pill ring-1 ring-inset {{ $badge[0] }}">{{ $badge[1] }}</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('mentor.participant', $row['assignment']) }}" class="text-xs font-medium text-brand-600 hover:text-brand-700">{{ __('Ver') }} →</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
