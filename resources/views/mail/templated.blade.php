@component('mail::message')
@if (! empty($headerImageUrl))
<p style="text-align:center;margin:0 0 16px;">
<img src="{{ $headerImageUrl }}" alt="" style="max-width:100%;height:auto;border-radius:6px;">
</p>
@endif
{!! \Illuminate\Support\Str::markdown($body) !!}
@endcomponent
