@component('mail::message')
{!! \Illuminate\Support\Str::markdown($body) !!}
@endcomponent
