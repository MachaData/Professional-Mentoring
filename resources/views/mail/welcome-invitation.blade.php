@component('mail::message')
# {{ __('¡Bienvenido/a, :name!', ['name' => $name]) }}

{{ __('Se ha creado tu acceso a la plataforma :app.', ['app' => $appName]) }}

{{ __('Estos son tus datos de acceso:') }}

- **{{ __('Correo') }}:** {{ $email }}
- **{{ __('Contraseña temporal') }}:** {{ $temporaryPassword }}

{{ __('Por seguridad, se te pedirá cambiar la contraseña en tu primer ingreso.') }}

@component('mail::button', ['url' => $loginUrl])
{{ __('Ingresar a la plataforma') }}
@endcomponent

{{ __('Si tienes problemas para ingresar, responde a este correo.') }}

{{ __('Saludos') }},<br>
{{ $appName }}
@endcomponent
