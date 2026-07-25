@extends('layouts.portal')
@section('title', __('Panel del mentor'))

@section('content')
@php $locale = app()->getLocale(); @endphp

<div class="mb-8 flex flex-wrap items-end justify-between gap-3">
    <div>
        <p class="text-sm font-medium text-brand-600">{{ __('Panel del mentor') }}</p>
        <h1 class="mt-1 text-3xl font-bold">{{ __('Hola') }}, {{ explode(' ', $facilitator->name)[0] }} 👋</h1>
    </div>
    <a href="{{ route('mentor.calendar') }}" class="pm-btn-ghost">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
        {{ __('Cronograma') }}
    </a>
</div>

<div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
    @php
        $cards = [
            ['label' => __('Participantes'), 'value' => $stats['participants'], 'tone' => 'slate', 'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
            ['label' => __('Pendientes'), 'value' => $stats['pending'], 'tone' => 'amber', 'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['label' => __('Completadas'), 'value' => $stats['completed'], 'tone' => 'emerald', 'icon' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['label' => __('Vencidas'), 'value' => $stats['expired'], 'tone' => 'rose', 'icon' => 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z'],
        ];
        $tones = [
            'slate' => 'bg-slate-100 text-slate-600', 'amber' => 'bg-amber-100 text-amber-600',
            'emerald' => 'bg-emerald-100 text-emerald-600', 'rose' => 'bg-rose-100 text-rose-600',
        ];
    @endphp
    @foreach ($cards as $card)
        <div class="pm-card p-5">
            <span class="grid h-10 w-10 place-items-center rounded-xl {{ $tones[$card['tone']] }}">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}"/></svg>
            </span>
            <div class="mt-3 text-3xl font-bold text-slate-900">{{ $card['value'] }}</div>
            <div class="text-sm text-slate-500">{{ $card['label'] }}</div>
        </div>
    @endforeach
</div>

<div class="mt-10 flex items-center justify-between">
    <h2 class="text-lg font-semibold">{{ __('Mis participantes') }}</h2>
    <span class="text-sm text-slate-400">{{ $assignments->count() }}</span>
</div>

<div class="mt-4 grid gap-3 sm:grid-cols-2">
    @forelse ($assignments as $assignment)
        @php
            // Counts already exclude sessions hidden from the mentor or still locked.
            ['total' => $total, 'done' => $done, 'percent' => $pct] = $progress[$assignment->id];
            $initials = collect(explode(' ', $assignment->participant->name))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('');
        @endphp
        <a href="{{ route('mentor.participant', $assignment) }}" class="pm-card pm-card-hover group flex items-center gap-4 p-5">
            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-gradient-to-br from-slate-700 to-slate-900 text-sm font-semibold text-white">{{ $initials }}</span>
            <div class="min-w-0 flex-1">
                <div class="truncate font-semibold text-slate-900">{{ $assignment->participant->name }}</div>
                <div class="truncate text-sm text-slate-500">{{ $assignment->program->getTranslation('name', $locale) }}</div>
                <div class="mt-2 flex items-center gap-2">
                    <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-brand-600 transition-all" style="width: {{ $pct }}%"></div>
                    </div>
                    <span class="text-xs font-medium text-slate-400">{{ $done }}/{{ $total }}</span>
                </div>
            </div>
            <svg class="h-5 w-5 shrink-0 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-brand-500" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        </a>
    @empty
        <div class="pm-card col-span-full grid place-items-center gap-2 p-12 text-center">
            <span class="grid h-12 w-12 place-items-center rounded-full bg-slate-100 text-slate-400">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
            </span>
            <p class="text-sm text-slate-500">{{ __('Aún no tienes participantes asignados.') }}</p>
        </div>
    @endforelse
</div>
@endsection
