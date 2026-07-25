@extends('layouts.portal')
@section('title', __('Ayuda') . ' · ' . __('Participante'))

@push('styles')
<style>
    .doc h2 { scroll-margin-top: 5rem; }
    .doc-toc a { display:block; padding:.35rem .75rem; border-radius:.6rem; color:#475569; font-size:.875rem; }
    .doc-toc a:hover { background:#f1f5f9; color:#0f172a; }
    .doc-step { counter-increment: step; }
    .doc-step-num::before { content: counter(step); }
</style>
@endpush

@section('content')
@php
    $sections = [
        ['id' => 'inicio', 'label' => 'Qué es la plataforma'],
        ['id' => 'entrar', 'label' => 'Entrar y tu cuenta'],
        ['id' => 'panel', 'label' => 'Tu panel de inicio'],
        ['id' => 'sesiones', 'label' => 'Tus sesiones'],
        ['id' => 'reunion', 'label' => 'Unirte a la reunión'],
        ['id' => 'encuestas', 'label' => 'Encuestas'],
        ['id' => 'materiales', 'label' => 'Materiales y recursos'],
        ['id' => 'subir', 'label' => 'Subir tus archivos'],
        ['id' => 'buzon', 'label' => 'Buzón: mensajes'],
        ['id' => 'calendario', 'label' => 'Calendario'],
        ['id' => 'idioma', 'label' => 'Idioma y contraseña'],
        ['id' => 'dudas', 'label' => 'Preguntas frecuentes'],
    ];
@endphp

<div class="mb-8">
    <p class="text-sm font-medium text-brand-600">{{ __('Centro de ayuda') }}</p>
    <h1 class="mt-1 text-3xl font-bold text-slate-900">Manual del participante</h1>
    <p class="mt-2 max-w-2xl text-slate-500">Todo lo que necesitas para aprovechar tu proceso de acompañamiento: unirte a tus sesiones, revisar materiales, guardar archivos y comunicarte con tu mentor.</p>
</div>

<div class="grid gap-8 lg:grid-cols-[220px_1fr]">
    {{-- Índice --}}
    <aside class="hidden lg:block">
        <div class="doc-toc sticky top-24">
            <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Contenido</p>
            @foreach ($sections as $s)
                <a href="#{{ $s['id'] }}">{{ $s['label'] }}</a>
            @endforeach
        </div>
    </aside>

    {{-- Cuerpo --}}
    <div class="doc space-y-10 text-slate-700 leading-relaxed">

        <section id="inicio" class="pm-card p-6">
            <h2 class="text-xl font-semibold text-slate-900">Qué es la plataforma</h2>
            <p class="mt-3">Es el espacio donde vives tu programa de acompañamiento profesional. Aquí encuentras tu agenda de <strong>sesiones</strong>, los <strong>materiales</strong> que tu mentor comparte, un <strong>espacio privado</strong> para guardar archivos y notas, y un <strong>buzón de mensajes</strong> para hablar con tu mentor.</p>
            <p class="mt-3">Tu proceso se organiza en <strong>sesiones</strong> (encuentros con tu mentor). Cada sesión puede tener un enlace de reunión, materiales y, a veces, una encuesta.</p>
        </section>

        <section id="entrar" class="pm-card p-6">
            <h2 class="text-xl font-semibold text-slate-900">Entrar y tu cuenta</h2>
            <ol class="mt-4 space-y-3" style="counter-reset: step">
                <x-doc-step>Recibirás un <strong>correo de invitación</strong> con el enlace para entrar y una contraseña temporal.</x-doc-step>
                <x-doc-step>Abre la página de acceso, escribe tu <strong>correo</strong> y tu <strong>contraseña</strong> y pulsa <em>Entrar</em>.</x-doc-step>
                <x-doc-step>La primera vez el sistema te pedirá <strong>cambiar la contraseña</strong> por una tuya, personal.</x-doc-step>
                <x-doc-step>¿Olvidaste la contraseña? En la pantalla de acceso usa <strong>«¿Olvidaste tu contraseña?»</strong> y sigue el enlace que llega a tu correo.</x-doc-step>
            </ol>
            <p class="mt-4 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-500">Para salir de forma segura usa el icono de <strong>salida</strong> (arriba a la derecha), sobre todo en computadoras compartidas.</p>
        </section>

        <section id="panel" class="pm-card p-6">
            <h2 class="text-xl font-semibold text-slate-900">Tu panel de inicio</h2>
            <p class="mt-3">Al entrar verás tu <strong>panel</strong> con:</p>
            <ul class="mt-3 list-disc space-y-1.5 pl-5">
                <li>Un saludo y el nombre de tu <strong>mentor</strong> asignado.</li>
                <li>La lista de tus <strong>sesiones</strong>, con su estado (pendiente, completada) y fechas.</li>
                <li>Un acceso al <strong>Cronograma / calendario</strong>.</li>
                <li>Una zona lateral con los <strong>materiales</strong> del programa.</li>
            </ul>
        </section>

        <section id="sesiones" class="pm-card p-6">
            <h2 class="text-xl font-semibold text-slate-900">Tus sesiones</h2>
            <p class="mt-3">Cada tarjeta de sesión te lleva a su detalle. Ahí puedes ver:</p>
            <ul class="mt-3 list-disc space-y-1.5 pl-5">
                <li>El <strong>título</strong> y el objetivo de la sesión.</li>
                <li>El <strong>enlace de reunión</strong> (si tu mentor lo cargó).</li>
                <li>Los <strong>materiales</strong> de esa sesión.</li>
                <li>Las <strong>Indicaciones</strong> de la sesión.</li>
                <li>Una <strong>encuesta</strong>, si aplica.</li>
                <li>Cuando la sesión ya se registró, la sección <strong>«Acuerdos y próximos pasos»</strong>: las notas que tu mentor marcó como visibles para ti.</li>
            </ul>
        </section>

        <section id="reunion" class="pm-card p-6">
            <h2 class="text-xl font-semibold text-slate-900">Unirte a la reunión</h2>
            <ol class="mt-4 space-y-3" style="counter-reset: step">
                <x-doc-step>Entra al <strong>detalle de la sesión</strong> correspondiente.</x-doc-step>
                <x-doc-step>Pulsa <strong>«Ingresar a la sesión»</strong>. Se abrirá en una pestaña nueva (Zoom, Meet, Teams, etc., según lo que use tu mentor).</x-doc-step>
                <x-doc-step>Si no ves el botón, la reunión aún no tiene enlace: contacta a tu mentor por el <strong>buzón</strong>.</x-doc-step>
            </ol>
        </section>

        <section id="encuestas" class="pm-card p-6">
            <h2 class="text-xl font-semibold text-slate-900">Encuestas</h2>
            <p class="mt-3">Algunas sesiones incluyen una <strong>encuesta</strong>. Pulsa el botón de la encuesta, respóndela en la pestaña que se abre y listo. Contestarla ayuda a tu mentor a ajustar el acompañamiento.</p>
        </section>

        <section id="materiales" class="pm-card p-6">
            <h2 class="text-xl font-semibold text-slate-900">Materiales y recursos</h2>
            <p class="mt-3">Los <strong>materiales</strong> son documentos, plantillas, videos o enlaces que tu mentor o la organización comparten contigo. Los encuentras:</p>
            <ul class="mt-3 list-disc space-y-1.5 pl-5">
                <li>En la <strong>zona lateral</strong> de tu panel (materiales de todo el programa).</li>
                <li>Dentro del <strong>detalle de cada sesión</strong> (materiales de esa sesión).</li>
            </ul>
            <p class="mt-3">Pulsa cualquier material para <strong>abrirlo o descargarlo</strong>. Los enlaces se abren en una pestaña nueva.</p>
        </section>

        <section id="subir" class="pm-card p-6">
            <h2 class="text-xl font-semibold text-slate-900">Subir tus archivos</h2>
            <p class="mt-3">Además de los materiales que recibes, tú también puedes <strong>subir tus propios archivos</strong>, enlaces o notas. Se hace <strong>dentro del detalle de cada sesión</strong>, en la zona <strong>«Materiales de la sesión»</strong>. Quedan compartidos solo entre tú y tu mentor.</p>
            <ol class="mt-4 space-y-3" style="counter-reset: step">
                <x-doc-step>Abre el <strong>detalle de la sesión</strong> a la que quieras adjuntar algo.</x-doc-step>
                <x-doc-step>En <strong>«Materiales de la sesión»</strong> pulsa <strong>«Agregar»</strong>.</x-doc-step>
                <x-doc-step>Escribe un <strong>título</strong> y elige el tipo:
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-slate-600">
                        <li><strong>Archivo:</strong> PDF, Word, Excel, PowerPoint, CSV o imagen (máximo 10 MB).</li>
                        <li><strong>Enlace:</strong> pega una dirección web (por ejemplo un video o un documento en la nube).</li>
                        <li><strong>Texto / nota:</strong> escribe una nota breve.</li>
                    </ul>
                </x-doc-step>
                <x-doc-step>Guarda. Tu material queda visible para ti y para tu mentor.</x-doc-step>
            </ol>
            <p class="mt-3 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-500">Solo <strong>quien subió</strong> un archivo puede eliminarlo. Si te equivocaste, bórralo tú mismo y vuelve a subirlo.</p>
        </section>

        <section id="buzon" class="pm-card p-6">
            <h2 class="text-xl font-semibold text-slate-900">Buzón: mensajes con tu mentor</h2>
            <p class="mt-3">El <strong>Buzón</strong> (botón «Buzón» en tu panel) es una mensajería privada con tu mentor, parecida al correo.</p>
            <ol class="mt-4 space-y-3" style="counter-reset: step">
                <x-doc-step>Abre el <strong>Buzón</strong> desde tu panel.</x-doc-step>
                <x-doc-step>Para escribir, redacta un <strong>asunto</strong> y tu <strong>mensaje</strong> (hasta 5000 caracteres) y, si quieres, añade un <strong>adjunto</strong> (archivo de hasta 10 MB).</x-doc-step>
                <x-doc-step>Si tu mensaje es sobre una sesión concreta, elígela en <strong>«Sesión relacionada»</strong>. Es opcional.</x-doc-step>
                <x-doc-step>Envía. Tu mentor lo verá y podrá responderte. Puedes filtrar por <strong>«No leídos»</strong> y verás un contador de mensajes sin leer.</x-doc-step>
            </ol>
            <p class="mt-3">Úsalo para dudas, coordinar horarios o compartir avances entre sesiones.</p>
        </section>

        <section id="calendario" class="pm-card p-6">
            <h2 class="text-xl font-semibold text-slate-900">Calendario</h2>
            <p class="mt-3">El <strong>Calendario</strong> (o Cronograma) muestra tus sesiones ordenadas por fecha para que veas de un vistazo qué viene. Puedes moverte entre meses con las flechas.</p>
        </section>

        <section id="idioma" class="pm-card p-6">
            <h2 class="text-xl font-semibold text-slate-900">Idioma y contraseña</h2>
            <ul class="mt-3 list-disc space-y-1.5 pl-5">
                <li><strong>Idioma:</strong> arriba a la derecha puedes cambiar entre <strong>ES</strong> (español) e <strong>EN</strong> (inglés).</li>
                <li><strong>Contraseña:</strong> puedes cambiarla cuando quieras desde la opción de cambio de contraseña.</li>
            </ul>
        </section>

        <section id="dudas" class="pm-card p-6">
            <h2 class="text-xl font-semibold text-slate-900">Preguntas frecuentes</h2>
            <dl class="mt-4 space-y-4">
                <div>
                    <dt class="font-semibold text-slate-900">No veo ninguna sesión.</dt>
                    <dd class="mt-1 text-slate-600">Puede que tu mentor aún no las haya publicado. Escríbele por el buzón.</dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-900">No puedo abrir un material.</dt>
                    <dd class="mt-1 text-slate-600">Revisa tu conexión y prueba de nuevo. Si es un enlace, se abre en otra pestaña; asegúrate de no bloquear ventanas emergentes.</dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-900">Subí un archivo por error.</dt>
                    <dd class="mt-1 text-slate-600">Elimínalo desde tu espacio (solo tú puedes borrar lo que subiste) y vuelve a cargarlo.</dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-900">Olvidé mi contraseña.</dt>
                    <dd class="mt-1 text-slate-600">Usa «¿Olvidaste tu contraseña?» en la pantalla de acceso.</dd>
                </div>
            </dl>
        </section>

    </div>
</div>

<div class="mt-10">
    <a href="{{ route('participant.dashboard') }}" class="pm-btn-ghost">← {{ __('Volver al panel') }}</a>
</div>
@endsection
