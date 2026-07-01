<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Ingresar') }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
            <h1 class="text-2xl font-bold text-center" style="color:#E30613">{{ config('app.name') }}</h1>
            <p class="text-center text-gray-500 text-sm mt-1 mb-6">{{ __('Accede a tu programa de acompañamiento') }}</p>

            @if($errors->any())
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('portal.login.attempt') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Correo') }}</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Contraseña') }}</label>
                    <input type="password" name="password" required
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="remember" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                    {{ __('Recordarme') }}
                </label>
                <button type="submit"
                    class="w-full rounded-lg bg-red-600 hover:bg-red-700 text-white font-medium py-2.5 transition">
                    {{ __('Ingresar') }}
                </button>
            </form>
        </div>
        <p class="text-center text-xs text-gray-400 mt-6">
            {{ __('Professional Mentoring © by CrossPartners Group') }}
        </p>
    </div>
</body>
</html>
