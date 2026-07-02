@php
    $locale = app()->getLocale();
    $duplaUrl = fn ($assignment) => \App\Filament\Resources\Assignments\AssignmentResource::getUrl('view', ['record' => $assignment]);
@endphp

<x-filament-panels::page>
    <div class="space-y-8">

        {{-- ============ AVANCE POR SESIÓN ============ --}}
        <section>
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Avance general por sesión</h2>
            <p class="text-sm text-gray-500">Duplas por estado en cada sesión.</p>

            <div class="mt-3 overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-2.5">Sesión</th>
                            <th class="px-4 py-2.5">Programa</th>
                            <th class="px-4 py-2.5 text-center">Total</th>
                            <th class="px-4 py-2.5 text-center">Completadas</th>
                            <th class="px-4 py-2.5 text-center">Pendientes</th>
                            <th class="px-4 py-2.5 text-center">Vencidas</th>
                            <th class="px-4 py-2.5">Avance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($this->sessionProgress as $row)
                            <tr>
                                <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-white">
                                    S{{ $row['session']->number }} · {{ $row['session']->getTranslation('name', $locale) }}
                                </td>
                                <td class="px-4 py-2.5 text-gray-500">{{ $row['session']->program?->getTranslation('name', $locale) }}</td>
                                <td class="px-4 py-2.5 text-center">{{ $row['total'] }}</td>
                                <td class="px-4 py-2.5 text-center text-emerald-600">{{ $row['completed'] }}</td>
                                <td class="px-4 py-2.5 text-center text-amber-600">{{ $row['pending'] }}</td>
                                <td class="px-4 py-2.5 text-center text-rose-600">{{ $row['expired'] }}</td>
                                <td class="px-4 py-2.5">
                                    <div class="flex items-center gap-2">
                                        <div class="h-1.5 w-24 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                                            <div class="h-full rounded-full bg-primary-600" style="width: {{ $row['percent'] }}%"></div>
                                        </div>
                                        <span class="text-xs text-gray-500">{{ $row['percent'] }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">Sin sesiones.</td></tr>
                        @endforelse
                    </tbody>
                </table>
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
            <section>
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">{{ $heading }}</h2>
                <p class="text-sm text-gray-500">{{ $desc }}</p>

                <div class="mt-3 overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500 dark:bg-white/5">
                            <tr>
                                <th class="px-4 py-2.5">Facilitador</th>
                                <th class="px-4 py-2.5">Participante</th>
                                <th class="px-4 py-2.5">Programa</th>
                                <th class="px-4 py-2.5 text-center">Avance</th>
                                @if($cat === 'fuera')<th class="px-4 py-2.5 text-center">Vencidas</th>@endif
                                <th class="px-4 py-2.5 text-right">Informe</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @forelse ($this->duplasIn($cat) as $row)
                                <tr>
                                    <td class="px-4 py-2.5 text-gray-900 dark:text-white">{{ $row['assignment']->facilitator?->name }}</td>
                                    <td class="px-4 py-2.5 text-gray-900 dark:text-white">{{ $row['assignment']->participant?->name }}</td>
                                    <td class="px-4 py-2.5 text-gray-500">{{ $row['assignment']->program?->getTranslation('name', $locale) }}</td>
                                    <td class="px-4 py-2.5 text-center">{{ $row['completed'] }}/{{ $row['total'] }}</td>
                                    @if($cat === 'fuera')<td class="px-4 py-2.5 text-center font-medium text-rose-600">{{ $row['expired'] }}</td>@endif
                                    <td class="px-4 py-2.5 text-right">
                                        <a href="{{ $duplaUrl($row['assignment']) }}" class="text-primary-600 hover:underline">Ver informe →</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">Ninguna dupla en esta categoría.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach
    </div>
</x-filament-panels::page>
