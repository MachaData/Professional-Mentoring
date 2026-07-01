@extends('layouts.portal')
@section('title', __('Mi programa'))

@section('content')
@php $locale = app()->getLocale(); @endphp

<div class="mb-8">
    <p class="text-sm font-medium text-brand-600">{{ __('Mi programa') }}</p>
    <h1 class="mt-1 text-3xl font-bold">{{ __('Hola') }}, {{ explode(' ', $participant->name)[0] }} 👋</h1>
</div>

@if(! $assignment)
    <div class="pm-card grid place-items-center gap-2 p-12 text-center">
        <span class="grid h-12 w-12 place-items-center rounded-full bg-slate-100 text-slate-400">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
        </span>
        <p class="text-sm text-slate-500">{{ __('Aún no estás asignado a un programa.') }}</p>
    </div>
@else
    {{-- Program + mentor hero --}}
    <div class="grid gap-4 md:grid-cols-3">
        <div class="pm-card relative overflow-hidden p-6 md:col-span-2">
            <div class="absolute -right-16 -top-16 h-48 w-48 rounded-full bg-brand-50"></div>
            <div class="relative">
                <span class="pm-pill bg-brand-50 text-brand-700">{{ __('Programa') }}</span>
                <h2 class="mt-3 text-2xl font-bold">{{ $assignment->program->getTranslation('name', $locale) }}</h2>
                @if($assignment->program->start_date)
                    <p class="mt-2 flex items-center gap-1.5 text-sm text-slate-500">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                        {{ $assignment->program->start_date->format('d/m/Y') }} – {{ $assignment->program->end_date?->format('d/m/Y') }}
                    </p>
                @endif
            </div>
        </div>
        <div class="pm-card p-6">
            <span class="pm-pill bg-slate-100 text-slate-600">{{ __('Mi mentor') }}</span>
            <div class="mt-3 flex items-center gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-full bg-gradient-to-br from-slate-700 to-slate-900 text-sm font-semibold text-white">
                    {{ collect(explode(' ', $assignment->facilitator->name))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('') }}
                </span>
                <div class="min-w-0">
                    <div class="truncate font-semibold text-slate-900">{{ $assignment->facilitator->name }}</div>
                    <div class="truncate text-xs text-slate-400">{{ $assignment->facilitator->email }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Two-column: sessions + sidebar --}}
    <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_320px]">
        <div>
            <h2 class="mb-4 text-lg font-semibold">{{ __('Mis sesiones') }}</h2>
            <div class="space-y-3">
                @foreach ($sessions as $row)
                    @php $session = $row['session']; $record = $row['record']; $done = $record && $record->status === 'completed'; @endphp
                    <div class="pm-card p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl font-display text-xs font-bold {{ $done ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $done ? '✓' : 'S'.$session->number }}
                                </span>
                                <div>
                                    <div class="font-semibold text-slate-900">{{ $session->getTranslation('name', $locale) }}</div>
                                    <p class="text-sm text-slate-500">{{ $session->getTranslation('objective', $locale) }}</p>
                                </div>
                            </div>
                            @if($done)
                                <span class="pm-pill bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">{{ __('Realizada') }}</span>
                            @elseif($session->start_date)
                                <span class="whitespace-nowrap text-xs text-slate-400">{{ $session->start_date->format('d/m') }} – {{ $session->end_date?->format('d/m') }}</span>
                            @endif
                        </div>

                        @include('partials.session-resources', [
                            'meetingUrl' => $row['meeting_url'],
                            'sessTools' => $row['tools'],
                            'surveyUrl' => $row['survey_url'],
                            'mode' => 'mentee',
                        ])

                        @if($row['visible_values']->isNotEmpty())
                            <div class="mt-4 rounded-xl bg-slate-50 p-4">
                                <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('Acuerdos y próximos pasos') }}</div>
                                <dl class="space-y-1.5">
                                    @foreach ($row['visible_values'] as $value)
                                        <div class="flex gap-2 text-sm">
                                            <dt class="shrink-0 text-slate-500">{{ $value['label'] }}:</dt>
                                            <dd class="text-slate-800">{{ $value['value'] }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        @include('partials.resource-sidebar', ['sidebarTools' => $sidebarTools])
    </div>
@endif
@endsection
