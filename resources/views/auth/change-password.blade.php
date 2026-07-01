@extends('layouts.portal')
@section('title', __('Cambiar contraseña'))

@section('content')
<div class="max-w-md mx-auto">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
        <h1 class="text-xl font-bold mb-1">{{ __('Crea tu nueva contraseña') }}</h1>
        <p class="text-sm text-gray-500 mb-6">{{ __('Por seguridad, debes definir una contraseña propia antes de continuar.') }}</p>

        @if($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.change.update') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Nueva contraseña') }}</label>
                <input type="password" name="password" required
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Confirmar contraseña') }}</label>
                <input type="password" name="password_confirmation" required
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
            </div>
            <button type="submit"
                class="w-full rounded-lg bg-red-600 hover:bg-red-700 text-white font-medium py-2.5 transition">
                {{ __('Guardar y continuar') }}
            </button>
        </form>
    </div>
</div>
@endsection
