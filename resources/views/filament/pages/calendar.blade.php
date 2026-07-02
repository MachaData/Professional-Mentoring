<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Indicadores básicos --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            @php
                $cards = [
                    ['label' => 'Duplas', 'value' => $counts['duplas'], 'cls' => 'text-gray-950 dark:text-white'],
                    ['label' => 'Al día', 'value' => $counts['dentro'], 'cls' => 'text-indigo-600'],
                    ['label' => 'Atrasadas', 'value' => $counts['fuera'], 'cls' => 'text-rose-600'],
                    ['label' => 'Sin inicio', 'value' => $counts['sin_inicio'], 'cls' => 'text-gray-500'],
                ];
            @endphp
            @foreach ($cards as $card)
                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                    <div class="text-2xl font-bold {{ $card['cls'] }}">{{ $card['value'] }}</div>
                    <div class="text-sm text-gray-500">{{ $card['label'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- Calendario mensual (partial compartido con los portales) --}}
        @include('partials.calendar', [
            'month' => $month,
            'events' => $events,
            'prevUrl' => $prevUrl,
            'nextUrl' => $nextUrl,
            'todayUrl' => $todayUrl,
        ])
    </div>
</x-filament-panels::page>
