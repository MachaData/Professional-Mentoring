<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dominio base
    |--------------------------------------------------------------------------
    |
    | El dominio raíz sobre el que se montan los subdominios de cada cliente.
    | Ej: 'pro-mentoring.com' → lasbambas.pro-mentoring.com resuelve el tenant
    | cuyo slug es "lasbambas". En local puede ser 'professional-mentoring.test'.
    | Si queda vacío, la resolución por subdominio se desactiva (login genérico).
    |
    */
    'base_domain' => env('APP_BASE_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Subdominios centrales (sin tenant)
    |--------------------------------------------------------------------------
    |
    | Subdominios reservados para la plataforma/operadora que NO corresponden a
    | un cliente. Muestran el login genérico de Professional Mentoring.
    |
    */
    'central_subdomains' => ['www', 'app', 'admin', 'operador', 'panel'],

];
