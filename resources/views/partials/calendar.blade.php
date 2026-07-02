@php
    use Illuminate\Support\Carbon;

    /** @var \Illuminate\Support\Carbon $month */
    $month = $month->copy()->startOfMonth();
    $gridStart = $month->copy()->startOfWeek(Carbon::MONDAY);
    $gridEnd = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
    $today = Carbon::today();

    $days = [];
    for ($c = $gridStart->copy(); $c->lte($gridEnd); $c->addDay()) {
        $days[] = $c->copy();
    }

    $events = $events ?? [];
    $dow = [__('Lun'), __('Mar'), __('Mié'), __('Jue'), __('Vie'), __('Sáb'), __('Dom')];
    $months = [1=>'Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    $monthLabel = __($months[(int) $month->format('n')]).' '.$month->format('Y');

    $tones = [
        'done'     => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
        'overdue'  => 'bg-rose-100 text-rose-800 ring-rose-200',
        'open'     => 'bg-indigo-100 text-indigo-800 ring-indigo-200',
        'upcoming' => 'bg-slate-100 text-slate-700 ring-slate-200',
    ];
@endphp

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    {{-- Header: month + nav --}}
    <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 sm:px-5">
        <h3 class="text-base font-semibold text-slate-900">{{ $monthLabel }}</h3>
        <div class="flex items-center gap-1.5">
            <a href="{{ $prevUrl }}" aria-label="{{ __('Mes anterior') }}"
               class="grid h-8 w-8 place-items-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-800">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            </a>
            @isset($todayUrl)
                <a href="{{ $todayUrl }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50">{{ __('Hoy') }}</a>
            @endisset
            <a href="{{ $nextUrl }}" aria-label="{{ __('Mes siguiente') }}"
               class="grid h-8 w-8 place-items-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-800">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            </a>
        </div>
    </div>

    {{-- Weekday headings --}}
    <div class="grid grid-cols-7 border-b border-slate-100 bg-slate-50/60 text-center text-[11px] font-semibold uppercase tracking-wide text-slate-400">
        @foreach ($dow as $d)
            <div class="py-2">{{ $d }}</div>
        @endforeach
    </div>

    {{-- Day cells --}}
    <div class="grid grid-cols-7">
        @foreach ($days as $day)
            @php
                $key = $day->format('Y-m-d');
                $dayEvents = $events[$key] ?? [];
                $inMonth = $day->month === $month->month;
                $isToday = $day->isSameDay($today);
            @endphp
            <div class="min-h-[92px] border-b border-r border-slate-100 p-1.5 last:border-r-0 [&:nth-child(7n)]:border-r-0 {{ $inMonth ? 'bg-white' : 'bg-slate-50/40' }}">
                <div class="mb-1 flex justify-end">
                    <span class="grid h-6 w-6 place-items-center rounded-full text-xs font-medium {{ $isToday ? 'bg-slate-900 text-white' : ($inMonth ? 'text-slate-500' : 'text-slate-300') }}">
                        {{ $day->day }}
                    </span>
                </div>
                <div class="space-y-1">
                    @foreach ($dayEvents as $ev)
                        @php $cls = $tones[$ev['tone']] ?? $tones['upcoming']; @endphp
                        <a @if($ev['url'])href="{{ $ev['url'] }}"@endif
                           title="{{ $ev['label'] }} · {{ ucfirst($ev['kind']) }}"
                           class="block truncate rounded-md px-1.5 py-1 text-[11px] font-medium ring-1 ring-inset {{ $cls }} {{ $ev['url'] ? 'hover:opacity-80' : 'cursor-default' }}">
                            <span class="opacity-70">{{ $ev['kind'] === 'cierre' ? '◒' : '●' }}</span>
                            {{ $ev['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    {{-- Legend --}}
    <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 border-t border-slate-100 px-4 py-2.5 text-[11px] text-slate-500 sm:px-5">
        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-indigo-400"></span>{{ __('En curso') }}</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>{{ __('Completada') }}</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span>{{ __('Vencida') }}</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span>{{ __('Próxima') }}</span>
        <span class="ml-auto inline-flex items-center gap-2"><span>● {{ __('Inicio') }}</span><span>◒ {{ __('Cierre') }}</span></span>
    </div>
</div>
