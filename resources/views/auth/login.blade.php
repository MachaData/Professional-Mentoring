@php
    use Illuminate\Support\Facades\Storage;
    $tenant = $tenant ?? null;
    $brandName = $tenant?->name ?? 'Professional Mentoring';
    $logo = $tenant?->logo ? Storage::url($tenant->logo) : null;
    $bg = $tenant?->login_background ? Storage::url($tenant->login_background) : null;
    $primary = $tenant?->primary_color ?: '#e30613';
    $welcome = $tenant?->getTranslation('welcome_text', app()->getLocale()) ?: __('Sesiones, materiales y acuerdos claros — acompañamiento con la metodología EPIC.');
    $initials = collect(explode(' ', $brandName))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('') ?: 'PM';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Ingresar') }} · {{ $brandName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>:root { --brand: {{ $primary }}; }</style>
</head>
<body class="min-h-screen">
<div class="grid min-h-screen lg:grid-cols-2">

    {{-- Brand panel --}}
    <div class="relative hidden overflow-hidden lg:block" style="background-color: {{ $primary }}">
        @if($bg)
            <img src="{{ $bg }}" alt="" class="absolute inset-0 h-full w-full object-cover">
            <div class="absolute inset-0" style="background: linear-gradient(135deg, {{ $primary }}e6, {{ $primary }}b3 40%, rgba(11,16,32,.85))"></div>
        @else
            <div class="absolute inset-0" style="background: linear-gradient(135deg, {{ $primary }}, {{ $primary }}cc 45%, #0b1020)"></div>
            <div class="pm-grid-bg absolute inset-0 opacity-40"></div>
            <div class="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-white/10 blur-3xl"></div>
            <div class="absolute -bottom-32 -left-16 h-96 w-96 rounded-full bg-white/5 blur-3xl"></div>
        @endif

        <div class="relative flex h-full flex-col justify-between p-12 text-white">
            <div class="flex items-center gap-2.5">
                @if($logo)
                    <img src="{{ $logo }}" alt="{{ $brandName }}" class="h-10 w-auto max-w-[180px] object-contain">
                @else
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-white/10 font-display font-bold ring-1 ring-white/20">{{ $initials }}</span>
                    <span class="font-display text-lg font-semibold">{{ $brandName }}</span>
                @endif
            </div>

            <div class="max-w-md">
                <h1 class="font-display text-4xl font-bold leading-tight text-white">
                    {{ __('Tu proceso de mentoring, en un solo lugar.') }}
                </h1>
                <p class="mt-4 text-white/70">{{ $welcome }}</p>

                <div class="mt-10 flex flex-wrap gap-2.5">
                    @foreach ([__('Exploración'), __('Planeamiento'), __('Implementación'), __('Cierre')] as $i => $step)
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3.5 py-1.5 text-sm ring-1 ring-white/15">
                            <span class="grid h-5 w-5 place-items-center rounded-full bg-white/20 text-xs font-semibold">{{ $i + 1 }}</span>
                            {{ $step }}
                        </span>
                    @endforeach
                </div>
            </div>

            <p class="text-sm text-white/50">© {{ date('Y') }} {{ $tenant ? 'Professional Mentoring' : 'CrossPartners Group' }}</p>
        </div>
    </div>

    {{-- Form panel --}}
    <div class="flex items-center justify-center px-6 py-12">
        <div class="w-full max-w-sm">
            <div class="mb-8 lg:hidden">
                @if($logo)
                    <img src="{{ $logo }}" alt="{{ $brandName }}" class="h-11 w-auto max-w-[160px] object-contain">
                @else
                    <span class="grid h-11 w-11 place-items-center rounded-xl font-display font-bold text-white" style="background-color: {{ $primary }}">{{ $initials }}</span>
                @endif
            </div>

            <h2 class="font-display text-2xl font-bold text-slate-900">{{ __('Bienvenido de nuevo') }}</h2>
            <p class="mt-1.5 text-sm text-slate-500">
                @if($tenant){{ $brandName }} · @endif{{ __('Accede a tu programa de acompañamiento') }}
            </p>

            @if($errors->any())
                <div class="mt-6 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('portal.login.attempt') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label class="pm-label">{{ __('Correo') }}</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus class="pm-input" placeholder="tu@correo.com">
                </div>
                <div>
                    <label class="pm-label">{{ __('Contraseña') }}</label>
                    <input type="password" name="password" required class="pm-input" placeholder="••••••••">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    {{ __('Recordarme') }}
                </label>
                <button type="submit" class="pm-btn-brand w-full" style="background-color: {{ $primary }}">{{ __('Ingresar') }}</button>
            </form>

            <p class="mt-8 text-center text-xs text-slate-400">
                {{ __('Professional Mentoring © by CrossPartners Group') }}
            </p>
        </div>
    </div>
</div>
</body>
</html>
