@extends('layouts.portal')
@section('title', $assignment->participant->name)

@section('content')
@php
    $locale = app()->getLocale();
    $badges = [
        'pending' => ['bg-amber-50 text-amber-700 ring-amber-200', __('Pendiente')],
        'draft' => ['bg-blue-50 text-blue-700 ring-blue-200', __('Borrador')],
        'completed' => ['bg-emerald-50 text-emerald-700 ring-emerald-200', __('Completada')],
        'expired' => ['bg-rose-50 text-rose-700 ring-rose-200', __('Vencida')],
        'rescheduled' => ['bg-purple-50 text-purple-700 ring-purple-200', __('Reprogramada')],
        'cancelled' => ['bg-slate-100 text-slate-600 ring-slate-200', __('Cancelada')],
    ];
    $initials = collect(explode(' ', $assignment->participant->name))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('');
    $grouped = $sessions->groupBy(fn ($row) => optional($row['session']->stage)->getTranslation('name', $locale) ?? '—');
    $completed = $sessions->where('status', 'completed')->count();
@endphp

<a href="{{ route('mentor.dashboard') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 transition hover:text-slate-900">
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
    {{ __('Volver') }}
</a>

{{-- Header --}}
<div class="pm-card mt-4 flex flex-col gap-4 p-6 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex items-center gap-4">
        <span class="grid h-14 w-14 place-items-center rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 text-lg font-semibold text-white">{{ $initials }}</span>
        <div>
            <h1 class="text-2xl font-bold">{{ $assignment->participant->name }}</h1>
            <p class="text-sm text-slate-500">{{ $assignment->program->getTranslation('name', $locale) }}</p>
            @if($assignment->participant->email)
                <p class="mt-0.5 text-xs text-slate-400">{{ $assignment->participant->email }}{{ $assignment->participant->phone ? ' · '.$assignment->participant->phone : '' }}</p>
            @endif
        </div>
    </div>
    <div class="text-right">
        <div class="font-display text-2xl font-bold text-slate-900">{{ $completed }}<span class="text-slate-300">/{{ $sessions->count() }}</span></div>
        <div class="text-xs text-slate-400">{{ __('sesiones') }}</div>
    </div>
</div>

{{-- Timeline by stage --}}
<div class="mt-8 space-y-8">
    @foreach ($grouped as $stageName => $rows)
        <div>
            <div class="mb-3 flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-brand-600"></span>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ $stageName }}</h2>
                <span class="text-xs text-slate-300">·</span>
                <span class="text-xs text-slate-400">{{ $rows->where('status', 'completed')->count() }}/{{ $rows->count() }}</span>
            </div>

            <div class="relative space-y-3 border-l border-slate-200 pl-5">
                @foreach ($rows as $row)
                    @php
                        $session = $row['session'];
                        $status = $row['status'];
                        [$badgeClass, $badgeLabel] = $badges[$status] ?? $badges['pending'];
                        $dot = $status === 'completed' ? 'bg-emerald-500' : ($status === 'expired' ? 'bg-rose-500' : 'bg-slate-300');
                    @endphp
                    <div class="relative">
                        <span class="absolute -left-[27px] top-5 h-3 w-3 rounded-full ring-4 ring-slate-50 {{ $dot }}"></span>
                        <div class="pm-card pm-card-hover flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-display text-xs font-bold text-slate-400">S{{ $session->number }}</span>
                                    <span class="font-semibold text-slate-900">{{ $session->getTranslation('name', $locale) }}</span>
                                    <span class="pm-pill ring-1 {{ $badgeClass }}">{{ $badgeLabel }}</span>
                                </div>
                                <p class="mt-1 text-sm text-slate-500">{{ $session->getTranslation('objective', $locale) }}</p>
                                @if($session->start_date)
                                    <p class="mt-1.5 flex items-center gap-1.5 text-xs text-slate-400">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                                        {{ $session->start_date->format('d/m/Y') }} – {{ $session->end_date?->format('d/m/Y') }}
                                    </p>
                                @endif
                            </div>
                            @if($row['record'])
                                <a href="{{ route('mentor.register', $row['record']) }}"
                                   class="{{ $status === 'completed' ? 'pm-btn-ghost' : 'pm-btn-brand' }} shrink-0">
                                    {{ $status === 'completed' ? __('Ver / editar') : __('Registrar') }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>

<div class="mt-10">
    @include('partials.resources', ['tools' => $tools, 'surveys' => $surveys])
</div>
@endsection
