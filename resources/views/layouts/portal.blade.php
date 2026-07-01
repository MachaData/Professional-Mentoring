@php
    $user = auth()->user();
    $org = $user?->organization;
    $primary = $org?->primary_color ?? '#E30613';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>:root { --brand: {{ $primary }}; }</style>
    @stack('styles')
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
    <nav class="bg-white border-b border-gray-200">
        <div class="mx-auto max-w-6xl px-4 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                @if($org?->logo)
                    <img src="{{ Storage::url($org->logo) }}" alt="{{ $org->name }}" class="h-8 w-auto">
                @endif
                <span class="font-semibold" style="color: var(--brand)">{{ config('app.name') }}</span>
            </div>
            <div class="flex items-center gap-4 text-sm">
                <div class="flex items-center gap-1 text-xs">
                    @foreach (['es' => 'ES', 'en' => 'EN'] as $code => $label)
                        <a href="{{ route('locale.switch', $code) }}"
                           class="px-2 py-1 rounded {{ app()->getLocale() === $code ? 'text-white' : 'text-gray-400 hover:text-gray-700' }}"
                           @if(app()->getLocale() === $code) style="background: var(--brand)" @endif>
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
                @auth
                    <span class="text-gray-600 hidden sm:inline">{{ $user->name }}</span>
                    <form method="POST" action="{{ route('portal.logout') }}">
                        @csrf
                        <button class="text-gray-500 hover:text-gray-900">{{ __('Salir') }}</button>
                    </form>
                @endauth
            </div>
        </div>
    </nav>

    <main class="mx-auto max-w-6xl px-4 py-8">
        @if(session('status'))
            <div class="mb-6 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                {{ session('status') }}
            </div>
        @endif

        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
