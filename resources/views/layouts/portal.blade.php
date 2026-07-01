@php
    $user = auth()->user();
    $org = $user?->organization;
    $primary = $org?->primary_color ?? '#e30613';
    $initials = collect(explode(' ', trim($user?->name ?? '')))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>:root { --brand: {{ $primary }}; }</style>
    @stack('styles')
</head>
<body class="min-h-screen">
    <div class="pointer-events-none fixed inset-x-0 top-0 h-64 bg-gradient-to-b from-brand-50/60 to-transparent"></div>

    <nav class="sticky top-0 z-30 border-b border-slate-200/70 bg-white/80 backdrop-blur-md">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5">
                @if($org?->logo)
                    <img src="{{ Storage::url($org->logo) }}" alt="{{ $org->name }}" class="h-8 w-auto">
                @else
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-600 font-display text-sm font-bold text-white">PM</span>
                @endif
                <span class="font-display text-[15px] font-semibold text-slate-900">Professional <span class="text-brand-600">Mentoring</span></span>
            </a>

            <div class="flex items-center gap-3">
                <div class="hidden items-center rounded-full bg-slate-100 p-0.5 text-xs font-medium sm:flex">
                    @foreach (['es' => 'ES', 'en' => 'EN'] as $code => $label)
                        <a href="{{ route('locale.switch', $code) }}"
                           class="rounded-full px-2.5 py-1 transition {{ app()->getLocale() === $code ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>

                @auth
                    <div class="flex items-center gap-2.5">
                        <div class="hidden text-right sm:block">
                            <div class="text-sm font-medium leading-tight text-slate-900">{{ $user->name }}</div>
                            <div class="text-xs leading-tight text-slate-400">{{ $user->email }}</div>
                        </div>
                        <span class="grid h-9 w-9 place-items-center rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-xs font-semibold text-white">
                            {{ $initials ?: 'U' }}
                        </span>
                        <form method="POST" action="{{ route('portal.logout') }}">
                            @csrf
                            <button title="{{ __('Salir') }}" class="grid h-9 w-9 place-items-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l3 3m0 0l-3 3m3-3H2.25"/></svg>
                            </button>
                        </form>
                    </div>
                @endauth
            </div>
        </div>
    </nav>

    <main class="relative mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">
        @if(session('status'))
            <div class="mb-6 flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('status') }}
            </div>
        @endif

        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
