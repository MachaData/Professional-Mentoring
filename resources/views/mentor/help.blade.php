@extends('layouts.portal')
@section('title', __('Ayuda') . ' · ' . __('Mentor'))

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
        ['id' => 'inicio', 'label' => 'Tu rol como mentor'],
        ['id' => 'entrar', 'label' => 'Entrar y tu cuenta'],
        ['id' => 'panel', 'label' => 'Panel del mentor'],
        ['id' => 'participante', 'label' => 'Ver a un participante'],
        ['id' => 'registrar', 'label' => 'Registrar una sesión'],
        ['id' => 'reunion', 'label' => 'Enlaces de reunión'],
        ['id' => 'materiales', 'label' => 'Compartir materiales'],
        ['id' => 'buzon', 'label' => 'Buzón de mensajes'],
        ['id' => 'calendario', 'label' => 'Calendario'],
        ['id' => 'idioma', 'label' => 'Idioma y contraseña'],
        ['id' => 'dudas', 'label' => 'Preguntas frecuentes'],
    ];
@endphp

<div class="mb-8">
    <p class="text-sm font-medium text-brand-600">{{ __('Centro de ayuda') }}</p>
    <h1 class="mt-1 text-3xl font-bold text-slate-900">Manual del mentor</h1>
    <p class="mt-2 max-w-2xl text-slate-500">Guía para acompañar a tus participantes: revisar tu agenda, registrar cada sesión, compartir materiales y comunicarte con cada persona.</p>
</div>

<div class="grid gap-8 lg:grid-cols-[220px_1fr]">
    <aside class="hidden lg:block">
        <div class="doc-toc sticky top-24">
            <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Contenido</p>
            @foreach ($sections as $s)
                <a href="#{{ $s['id'] }}">{{ $s['label'] }}</a>
            @endforeach
        </div>
    </aside>

    <div class="doc space-y-10 text-slate-700 leading-relaxed">

        <section id="inicio" class="pm-card p-6">
            <h2 class="text-xl font-semibold text-slate-900">Tu rol como mentor</h2>
            <p class="mt-3">Como mentor (facilitador) acompañas a una o varias personas a lo largo de su programa. Tu trabajo en la plataforma consiste en: <strong>llevar el seguimiento de cada sesión</strong>, <strong>registrar lo ocurrido</strong>, <strong>compartir materiales</strong> y <strong>mantener el contacto</strong> con cada participante.</p>
            <p class="mt-3">Cada persona que acompañas forma contigo una <strong>dupla</strong> dentro de un programa. Un programa se compone de varias <strong>sesiones</strong>.</p>
        </section>

        <section id="entrar" class="pm-card p-6">
            <h2 class="text-xl font-semibold text-slate-900">Entrar y tu cuenta</h2>
            <ol class="mt-4 space-y-3" style="counter-reset: step">
                <x-doc-step>Recibirás un <strong>correo de invitación</strong> con el enlace de acceso y una contraseña temporal.</x-doc-step>
                <x-doc-step>Escribe tu <strong>correo</strong> y <strong>contraseña</strong> y pulsa <em>Entrar</em>.</x-doc-step>
                <x-doc-step>La primera vez deberás <strong>cambiar la contraseña</strong> por una personal.</x-doc-step>
                <x-doc-step>¿Olvidaste tu contraseña? Usa <strong>«¿Olvidaste tu contraseña?»</strong> en la pantalla de acceso.</x-doc-step>
            </ol>
        </section>

        <section id="panel" class="pm-card p-6">
            <h2 class="text-xl font-semibold text-slate-900">Panel del mentor</h2>
            <p class="mt-3">Al entrar verás tu <strong>panel</strong> con un resumen y tus participantes:</p>
            <ul class="mt-3 list-disc space-y-1.5 pl-5">
                <li><strong>Participantes:</strong> cuántas personas acompañas.</li>
                <li><strong>Pendientes:</strong> sesiones que aún debes registrar.</li>
                <li><strong>Completadas:</strong> sesiones ya registradas.</li>
                <li><strong>Vencidas:</strong> sesiones cuya fecha ya pasó sin registrarse.</li>
            </ul>
            <p class="mt-3">Debajo aparece la lista <strong>«Mis participantes»</strong>. Pulsa una tarjeta para abrir su seguimiento. Arriba a la derecha tienes acceso al <strong>Cronograma</strong>.</p>
        </section>

        <section id="participante" class="pm-card p-6">
            <h2 class="text-xl font-semibold text-slate-900">Ver a un participante</h2>
            <p class="mt-3">Al abrir un participante verás <strong>todas sus sesiones</strong> con su estado. De cada sesión puedes:</p>
            <ul class="mt-3 list-disc space-y-1.5 pl-5">
                <li><strong>Registrar</strong> lo ocurrido (ver la sección siguiente).</li>
                <li>Abrir el <strong>enlace de reunión</strong>.</li>
                <li>Consultar los <strong>materiales</strong> y la <strong>encuesta</strong> de la sesión.</li>
            </ul>
            <p class="mt-3">A un lado tienes los materiales del <strong>programa completo</strong>, y acceso al <strong>Espacio / Buzón</strong> con esa persona.</p>
        </section>

        <section id="registrar" class="pm-card p-6">
            <h2 class="text-xl font-semibold text-slate-900">Registrar una sesión</h2>
            <p class="mt-3">Registrar una sesión es dejar constancia de que ocurrió y capturar sus datos. Es tu tarea principal.</p>
            <ol class="mt-4 space-y-3" style="counter-reset: step">
                <x-doc-step>Desde el participante, pulsa <strong>«Registrar»</strong> en la sesión correspondiente.</x-doc-step>
                <x-doc-step>Indica la <strong>fecha real</strong> en que se realizó.</x-doc-step>
                <x-doc-step>Marca la <strong>asistencia</strong> (por ejemplo asistió / no asistió) y la <strong>modalidad</strong> (presencial, virtual…).</x-doc-step>
                <x-doc-step>Si la hubo, pega el <strong>enlace de la reunión</strong>.</x-doc-step>
                <x-doc-step>Completa los <strong>campos del formulario</strong> de la sesión (notas, objetivos, compromisos… varían según el programa).</x-doc-step>
                <x-doc-step>Elige una opción:
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-slate-600">
                        <li><strong>Guardar borrador:</strong> conserva lo escrito para terminarlo después. La sesión sigue pendiente.</li>
                        <li><strong>Completar / marcar como completada:</strong> cierra el registro. Cuenta como sesión completada.</li>
                    </ul>
                </x-doc-step>
            </ol>
            <p class="mt-4 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-500">Algunos campos pueden estar marcados como <strong>visibles para el participante</strong>: lo que escribas ahí aparecerá en «Acuerdos y próximos pasos» del detalle de la sesión de esa persona una vez completada. Los demás campos son solo para ti y la coordinación.</p>
            <p class="mt-3">En esta misma pantalla, si la sesión tiene <strong>encuesta</strong>, verás los botones <strong>«Abrir encuesta»</strong> y <strong>«Copiar encuesta»</strong> (para copiar el enlace y reenviárselo al participante).</p>
        </section>

        <section id="reunion" class="pm-card p-6">
            <h2 class="text-xl font-semibold text-slate-900">Enlaces de reunión</h2>
            <p class="mt-3">Al registrar una sesión puedes guardar el <strong>enlace de la videollamada</strong> (Zoom, Meet, Teams…). Una vez guardado, tanto tú como el participante lo verán como botón para <strong>unirse</strong> desde la sesión.</p>
        </section>

        <section id="materiales" class="pm-card p-6">
            <h2 class="text-xl font-semibold text-slate-900">Compartir materiales</h2>
            <p class="mt-3">Puedes subir materiales privados que solo ve esa persona. Se hace desde la pantalla de <strong>registrar la sesión</strong>, en la zona <strong>«Materiales de la sesión»</strong>:</p>
            <ol class="mt-4 space-y-3" style="counter-reset: step">
                <x-doc-step>Abre una sesión del participante y entra a <strong>«Registrar»</strong>.</x-doc-step>
                <x-doc-step>Baja hasta <strong>«Materiales de la sesión»</strong> y pulsa <strong>«Agregar»</strong>.</x-doc-step>
                <x-doc-step>Escribe un <strong>título</strong> y elige el tipo:
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-slate-600">
                        <li><strong>Archivo:</strong> PDF, Word, Excel, PowerPoint, CSV o imagen (máx. 10 MB).</li>
                        <li><strong>Enlace:</strong> una dirección web (video, documento en la nube…).</li>
                        <li><strong>Texto / nota:</strong> una nota breve.</li>
                    </ul>
                </x-doc-step>
                <x-doc-step>Guarda. El material queda visible para ti y para el participante en esa sesión.</x-doc-step>
            </ol>
            <p class="mt-3 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-500">Solo <strong>quien subió</strong> un material puede eliminarlo. Los materiales generales de todo el programa (que aparecen en la barra lateral) los carga la coordinación desde el panel de administración, en <strong>Herramientas</strong>.</p>
        </section>

        <section id="buzon" class="pm-card p-6">
            <h2 class="text-xl font-semibold text-slate-900">Buzón de mensajes</h2>
            <p class="mt-3">Cada dupla tiene un <strong>buzón</strong> privado (botón <strong>«Buzón»</strong> / Espacio). Es una mensajería parecida al correo.</p>
            <ol class="mt-4 space-y-3" style="counter-reset: step">
                <x-doc-step>Abre el <strong>Buzón</strong> del participante.</x-doc-step>
                <x-doc-step>Redacta un <strong>asunto</strong> y un <strong>mensaje</strong> (hasta 5000 caracteres); si quieres, añade un <strong>adjunto</strong> (archivo de hasta 10 MB).</x-doc-step>
                <x-doc-step>Envía. Puedes filtrar por <strong>«No leídos»</strong> y ver el contador de mensajes sin leer.</x-doc-step>
            </ol>
            <p class="mt-3">Úsalo para coordinar horarios, resolver dudas o dar seguimiento entre sesiones.</p>
        </section>

        <section id="calendario" class="pm-card p-6">
            <h2 class="text-xl font-semibold text-slate-900">Calendario</h2>
            <p class="mt-3">El <strong>Cronograma / Calendario</strong> reúne todas tus sesiones ordenadas por fecha. Te ayuda a ver qué se acerca y a no dejar sesiones vencidas. Navega entre meses con las flechas.</p>
        </section>

        <section id="idioma" class="pm-card p-6">
            <h2 class="text-xl font-semibold text-slate-900">Idioma y contraseña</h2>
            <ul class="mt-3 list-disc space-y-1.5 pl-5">
                <li><strong>Idioma:</strong> arriba a la derecha cambias entre <strong>ES</strong> e <strong>EN</strong>.</li>
                <li><strong>Contraseña:</strong> puedes cambiarla cuando quieras desde la opción de cambio de contraseña.</li>
            </ul>
        </section>

        <section id="dudas" class="pm-card p-6">
            <h2 class="text-xl font-semibold text-slate-900">Preguntas frecuentes</h2>
            <dl class="mt-4 space-y-4">
                <div>
                    <dt class="font-semibold text-slate-900">Una sesión aparece como «vencida».</dt>
                    <dd class="mt-1 text-slate-600">Su fecha pasó sin registrarse. Puedes registrarla igual: ábrela y complétala.</dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-900">¿Puedo corregir un registro ya completado?</dt>
                    <dd class="mt-1 text-slate-600">Vuelve a abrir la sesión para revisar sus datos. Si necesitas un cambio que no puedes hacer, contacta a la coordinación.</dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-900">No veo a un participante que debería tener.</dt>
                    <dd class="mt-1 text-slate-600">La asignación de duplas la hace la coordinación desde el panel de administración. Avísales.</dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-900">Subí un archivo equivocado.</dt>
                    <dd class="mt-1 text-slate-600">Elimínalo desde el espacio (solo tú puedes borrar lo que subiste) y vuelve a cargarlo.</dd>
                </div>
            </dl>
        </section>

    </div>
</div>

<div class="mt-10">
    <a href="{{ route('mentor.dashboard') }}" class="pm-btn-ghost">← {{ __('Volver al panel') }}</a>
</div>
@endsection
