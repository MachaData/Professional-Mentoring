@php
    use Illuminate\Support\Facades\Storage;
    $tenant = $tenant ?? null;
    $brandName = $tenant?->name ?? 'Professional Mentoring';
    $logo = $tenant?->logo ? Storage::url($tenant->logo) : null;
    $primary = $tenant?->primary_color ?: '#e30613';
    $initials = collect(explode(' ', $brandName))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('') ?: 'PM';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Recuperar contraseña') }} · {{ $brandName }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>:root { --brand: {{ $primary }}; }</style>
</head>
<body class="min-h-screen bg-slate-50">
<div class="flex min-h-screen items-center justify-center px-6 py-12">
    <div class="w-full max-w-sm">
        <div class="mb-8 flex justify-center">
            @if($logo)
                <img src="{{ $logo }}" alt="{{ $brandName }}" class="h-11 w-auto max-w-[160px] object-contain">
            @else
                <span class="grid h-11 w-11 place-items-center rounded-xl font-display font-bold text-white" style="background-color: {{ $primary }}">{{ $initials }}</span>
            @endif
        </div>

        <div class="pm-card p-8">
            <h1 class="font-display text-xl font-bold text-slate-900">{{ __('¿Olvidaste tu contraseña?') }}</h1>
            <p class="mt-1.5 text-sm text-slate-500">{{ __('Ingresa tu correo y te enviaremos un enlace para crear una nueva.') }}</p>

            @if(session('status'))
                <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif
            @if($errors->any())
                <div class="mt-5 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label class="pm-label">{{ __('Correo') }}</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus class="pm-input" placeholder="tu@correo.com">
                </div>
                <button type="submit" class="pm-btn-brand w-full" style="background-color: {{ $primary }}">{{ __('Enviar enlace') }}</button>
            </form>

            <a href="{{ route('portal.login') }}" class="mt-6 block text-center text-sm font-medium text-brand-600 hover:text-brand-700">
                ← {{ __('Volver a ingresar') }}
            </a>
        </div>
    </div>
</div>
</body>
</html>
