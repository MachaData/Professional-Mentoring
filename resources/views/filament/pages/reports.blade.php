@php
    $locale = app()->getLocale();
    $duplaUrl = fn ($assignment) => \App\Filament\Resources\Assignments\AssignmentResource::getUrl('view', ['record' => $assignment]);
    // Shared formatting so every list below reads the same way.
    $sLabel = fn ($session) => $session ? 'S'.$session->number : '—';
    $activity = fn ($row) => $row['last_activity']
        ? $row['last_activity']->format('d/m/Y').' · '.$row['last_activity_type']
        : 'Sin actividad';
    $stateColor = fn ($row) => match (true) {
        $row['finished'] => 'bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-400',
        $row['category'] === 'fuera' => 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400',
        $row['category'] === 'sin_inicio' => 'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300',
        default => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400',
    };
@endphp

<x-filament-panels::page>
    <div class="space-y-8">

        {{-- ============ AVANCE POR SESIÓN (realizadas / pendientes / vencidas por dupla) ============ --}}
        <section>
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Avance por sesión</h2>
            <p class="text-sm text-gray-500">Por cada sesión del programa: total de duplas, realizadas, pendientes y vencidas (fuera de fecha). Gráfico y números.</p>

            {{-- Leyenda --}}
            <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-1 text-xs text-gray-600 dark:text-gray-300">
                <span class="flex items-center gap-1.5"><span class="inline-block h-3 w-3 rounded-sm bg-emerald-500"></span> Realizadas</span>
                <span class="flex items-center gap-1.5"><span class="inline-block h-3 w-3 rounded-sm bg-amber-400"></span> Pendientes</span>
                <span class="flex items-center gap-1.5"><span class="inline-block h-3 w-3 rounded-sm bg-rose-500"></span> Vencidas (fuera de fecha)</span>
            </div>

            <div class="mt-3 space-y-5">
                @forelse ($sessionBreakdown as $prog)
                    @php
                        $totComp = $prog['sessions']->sum('completed');
                        $totPend = $prog['sessions']->sum('pending');
                        $totExp  = $prog['sessions']->sum('expired');
                    @endphp
                    <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
                        {{-- Encabezado del programa con total de duplas --}}
                        <div class="flex flex-wrap items-center justify-between gap-2 bg-gray-50 px-4 py-2.5 dark:bg-white/5">
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $prog['program']?->getTranslation('name', $locale) }}</span>
                            <span class="text-xs text-gray-500">Total de duplas: <span class="font-semibold text-gray-900 dark:text-white">{{ $prog['total_duplas'] }}</span></span>
                        </div>

                        {{-- Una fila por sesión: barra apilada + cifras --}}
                        <div class="divide-y divide-gray-100 dark:divide-white/5">
                            @foreach ($prog['sessions'] as $row)
                                @php
                                    $total = max(1, $row['total']);
                                    $pc = fn ($n) => $total ? round($n / $total * 100, 2) : 0;
                                @endphp
                                <div class="px-4 py-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="truncate text-sm font-medium text-gray-900 dark:text-white">
                                            S{{ $row['session']->number }} · {{ $row['session']->getTranslation('name', $locale) }}
                                        </span>
                                        <span class="shrink-0 text-xs text-gray-500">Total {{ $row['total'] }}</span>
                                    </div>

                                    {{-- Gráfico: barra apilada 100% --}}
                                    <div class="mt-2 flex h-5 w-full overflow-hidden rounded-md bg-gray-100 dark:bg-white/10" role="img"
                                         aria-label="Realizadas {{ $row['completed'] }}, pendientes {{ $row['pending'] }}, vencidas {{ $row['expired'] }} de {{ $row['total'] }}">
                                        @if ($row['completed'])
                                            <div class="h-full bg-emerald-500" style="width: {{ $pc($row['completed']) }}%" title="Realizadas: {{ $row['completed'] }}"></div>
                                        @endif
                                        @if ($row['pending'])
                                            <div class="h-full bg-amber-400" style="width: {{ $pc($row['pending']) }}%" title="Pendientes: {{ $row['pending'] }}"></div>
                                        @endif
                                        @if ($row['expired'])
                                            <div class="h-full bg-rose-500" style="width: {{ $pc($row['expired']) }}%" title="Vencidas: {{ $row['expired'] }}"></div>
                                        @endif
                                    </div>

                                    {{-- Números claros --}}
                                    <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-xs">
                                        <span class="text-gray-500">Total: <span class="font-semibold text-gray-900 dark:text-white">{{ $row['total'] }}</span></span>
                                        <span class="text-emerald-600 dark:text-emerald-400">Realizadas: <span class="font-semibold">{{ $row['completed'] }}</span></span>
                                        <span class="text-amber-600 dark:text-amber-400">Pendientes: <span class="font-semibold">{{ $row['pending'] }}</span></span>
                                        <span class="text-rose-600 dark:text-rose-400">Vencidas: <span class="font-semibold">{{ $row['expired'] }}</span></span>
                                        <span class="text-gray-400">({{ $row['percent'] }}% completado)</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Totales del programa --}}
                        <div class="flex flex-wrap gap-x-6 gap-y-1 border-t border-gray-200 bg-gray-50/60 px-4 py-2.5 text-xs dark:border-white/10 dark:bg-white/5">
                            <span class="font-semibold text-gray-700 dark:text-gray-200">Totales del programa:</span>
                            <span class="text-emerald-600 dark:text-emerald-400">Realizadas <span class="font-semibold">{{ $totComp }}</span></span>
                            <span class="text-amber-600 dark:text-amber-400">Pendientes <span class="font-semibold">{{ $totPend }}</span></span>
                            <span class="text-rose-600 dark:text-rose-400">Vencidas <span class="font-semibold">{{ $totExp }}</span></span>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-gray-200 px-4 py-6 text-center text-gray-400 dark:border-white/10">Sin sesiones o sin duplas para mostrar.</div>
                @endforelse
            </div>
        </section>

        {{-- ============ #1 AVANCE GENERAL POR SESIÓN (duplas en cada sesión) ============ --}}
        <section>
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Avance general por sesión</h2>
            <p class="text-sm text-gray-500">Cantidad y listado de duplas que se encuentran actualmente en cada sesión.</p>

            <div class="mt-3 space-y-3">
                @forelse ($currentGroups as $group)
                    <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
                        <div class="flex items-center justify-between bg-gray-50 px-4 py-2.5 dark:bg-white/5">
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $group['label'] }}</span>
                            <span class="rounded-full bg-primary-600 px-2.5 py-0.5 text-xs font-medium text-white">{{ $group['count'] }} {{ Str::plural('dupla', $group['count']) }}</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50/60 text-left text-xs uppercase text-gray-500 dark:bg-white/5">
                                    <tr>
                                        <th class="px-4 py-2">Mentor</th>
                                        <th class="px-4 py-2">Mentee</th>
                                        <th class="px-4 py-2">Programa</th>
                                        <th class="px-4 py-2 text-center">Avance</th>
                                        <th class="px-4 py-2 text-center">Estado</th>
                                        <th class="px-4 py-2">Última actividad</th>
                                        <th class="px-4 py-2 text-right">Informe</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                    @foreach ($group['duplas'] as $row)
                                        <tr>
                                            <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $row['assignment']->facilitator?->name }}</td>
                                            <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $row['assignment']->participant?->name }}</td>
                                            <td class="px-4 py-2 text-gray-500">{{ $row['assignment']->program?->getTranslation('name', $locale) }}</td>
                                            <td class="px-4 py-2 text-center text-gray-500">{{ $row['completed'] }}/{{ $row['total'] }}</td>
                                            <td class="px-4 py-2 text-center">
                                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $stateColor($row) }}">{{ $row['state_label'] }}</span>
                                            </td>
                                            <td class="px-4 py-2 text-gray-500">{{ $activity($row) }}</td>
                                            <td class="px-4 py-2 text-right"><a href="{{ $duplaUrl($row['assignment']) }}" class="text-primary-600 hover:underline">Ver informe →</a></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-gray-200 px-4 py-6 text-center text-gray-400 dark:border-white/10">Sin duplas.</div>
                @endforelse
            </div>
        </section>

        {{-- ============ DUPLAS POR CRONOGRAMA ============ --}}
        <section>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-500/20 dark:bg-emerald-500/10">
                    <div class="text-2xl font-bold text-emerald-700 dark:text-emerald-400">{{ $counts['dentro'] }}</div>
                    <div class="text-sm text-emerald-700/80">Dentro del cronograma</div>
                </div>
                <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-500/20 dark:bg-rose-500/10">
                    <div class="text-2xl font-bold text-rose-700 dark:text-rose-400">{{ $counts['fuera'] }}</div>
                    <div class="text-sm text-rose-700/80">Fuera del cronograma</div>
                </div>
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                    <div class="text-2xl font-bold text-gray-700 dark:text-gray-300">{{ $counts['sin_inicio'] }}</div>
                    <div class="text-sm text-gray-500">Sin inicio</div>
                </div>
            </div>
        </section>

        @php
            $lists = [
                ['dentro', 'Duplas dentro del cronograma', 'Iniciadas y sin sesiones vencidas.'],
                ['fuera', 'Duplas fuera del cronograma', 'Con una o más sesiones vencidas.'],
                ['sin_inicio', 'Duplas sin inicio', 'Aún sin sesiones completadas.'],
            ];
        @endphp

        @foreach ($lists as [$cat, $heading, $desc])
            @php $rows = $this->duplasIn($cat); @endphp
            <section>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-base font-semibold text-gray-950 dark:text-white">{{ $heading }}</h2>
                        <p class="text-sm text-gray-500">{{ $desc }}</p>
                    </div>
                    <span class="rounded-full bg-primary-600 px-3 py-1 text-xs font-medium text-white">
                        Total: {{ $rows->count() }} {{ Str::plural('dupla', $rows->count()) }}
                    </span>
                </div>

                <div class="mt-3 overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500 dark:bg-white/5">
                                <tr>
                                    <th class="px-4 py-2.5">Mentor</th>
                                    <th class="px-4 py-2.5">Mentee</th>
                                    <th class="px-4 py-2.5">Programa</th>
                                    <th class="px-4 py-2.5 text-center">Sesión actual</th>
                                    <th class="px-4 py-2.5 text-center">Sesión esperada</th>
                                    <th class="px-4 py-2.5 text-center">Avance</th>
                                    <th class="px-4 py-2.5 text-center">Estado</th>
                                    <th class="px-4 py-2.5">Última actividad</th>
                                    <th class="px-4 py-2.5 text-center">Días de atraso</th>
                                    <th class="px-4 py-2.5 text-right">Informe</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                @forelse ($rows as $row)
                                    <tr>
                                        <td class="px-4 py-2.5 text-gray-900 dark:text-white">{{ $row['assignment']->facilitator?->name }}</td>
                                        <td class="px-4 py-2.5 text-gray-900 dark:text-white">{{ $row['assignment']->participant?->name }}</td>
                                        <td class="px-4 py-2.5 text-gray-500">{{ $row['assignment']->program?->getTranslation('name', $locale) }}</td>
                                        <td class="px-4 py-2.5 text-center text-gray-900 dark:text-white">{{ $sLabel($row['current']) }}</td>
                                        <td class="px-4 py-2.5 text-center text-gray-500">{{ $sLabel($row['expected']) }}</td>
                                        <td class="px-4 py-2.5 text-center">{{ $row['completed'] }}/{{ $row['total'] }}</td>
                                        <td class="px-4 py-2.5 text-center">
                                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $stateColor($row) }}">{{ $row['state_label'] }}</span>
                                        </td>
                                        <td class="px-4 py-2.5 text-gray-500">{{ $activity($row) }}</td>
                                        <td class="px-4 py-2.5 text-center {{ $row['days_behind'] ? 'font-semibold text-rose-600' : 'text-gray-400' }}">
                                            {{ $row['days_behind'] ?: '—' }}
                                        </td>
                                        <td class="px-4 py-2.5 text-right">
                                            <a href="{{ $duplaUrl($row['assignment']) }}" class="text-primary-600 hover:underline">Ver informe →</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="10" class="px-4 py-6 text-center text-gray-400">Ninguna dupla en esta categoría.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @endforeach
    </div>
</x-filament-panels::page>
