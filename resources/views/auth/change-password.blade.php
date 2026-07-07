@extends('layouts.portal')
@section('title', __('Cambiar contraseña'))

@section('content')
<div class="mx-auto max-w-md">
    <div class="pm-card p-8">
        <span class="grid h-11 w-11 place-items-center rounded-xl bg-brand-50 text-brand-600">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
        </span>
        <h1 class="mt-4 text-xl font-bold">{{ __('Crea tu nueva contraseña') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ __('Por seguridad, debes definir una contraseña propia antes de continuar.') }}</p>

        @if($errors->any())
            <div class="mt-5 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.change.update') }}" class="mt-6 space-y-4">
            @csrf
            @include('partials.password-field', ['name' => 'password', 'label' => __('Nueva contraseña'), 'placeholder' => ''])
            @include('partials.password-field', ['name' => 'password_confirmation', 'label' => __('Confirmar contraseña'), 'placeholder' => ''])
            <button type="submit" class="pm-btn-brand w-full">{{ __('Guardar y continuar') }}</button>
        </form>
    </div>
</div>
@endsection
