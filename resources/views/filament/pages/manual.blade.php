<x-filament-panels::page>
    <style>
        .manual h2 { scroll-margin-top: 6rem; }
        .manual ol.steps { counter-reset: mstep; }
        .manual ol.steps > li { counter-increment: mstep; position: relative; padding-left: 2.25rem; }
        .manual ol.steps > li::before {
            content: counter(mstep); position: absolute; left: 0; top: 0;
            display: grid; place-items: center; height: 1.5rem; width: 1.5rem;
            border-radius: 9999px; background: var(--primary-600, #e30613); color: #fff;
            font-size: .75rem; font-weight: 600;
        }
    </style>

    <div class="manual space-y-8 text-sm leading-relaxed text-gray-700 dark:text-gray-300">

        {{-- Intro --}}
        <section class="rounded-xl border border-gray-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Bienvenido al panel de administración</h2>
            <p class="mt-3">Desde aquí configuras y das seguimiento a todo el acompañamiento: organizaciones, personas, programas, sesiones, materiales, comunicaciones y reportes. Esta guía explica, grupo por grupo del menú lateral, para qué sirve cada sección y cómo realizar las tareas más frecuentes.</p>
            <div class="mt-4 rounded-lg bg-primary-50 p-4 text-primary-900 dark:bg-primary-400/10 dark:text-primary-200">
                <p class="font-medium">Quién ve qué</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li><strong>Superadministrador:</strong> acceso total, incluidas las Organizaciones.</li>
                    <li><strong>Administrador de organización:</strong> gestiona todo dentro de su organización.</li>
                    <li><strong>Coordinador:</strong> acceso de solo lectura al contenido y puede enviar notificaciones y gestionar usuarios/asignaciones.</li>
                    <li><strong>Facilitador (mentor)</strong> y <strong>Participante</strong> no entran aquí: usan sus propios portales.</li>
                </ul>
            </div>
        </section>

        {{-- Índice --}}
        <section class="rounded-xl border border-gray-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Contenido</h2>
            <div class="mt-3 grid gap-x-6 gap-y-1.5 sm:grid-cols-2">
                @php
                    $toc = [
                        'flujo' => '★ Cómo montar un programa (paso a paso)',
                        'organizaciones' => 'Organizaciones',
                        'usuarios' => 'Usuarios',
                        'clientes' => 'Clientes',
                        'tipos' => 'Tipos de programa',
                        'programas' => 'Programas (etapas y popups)',
                        'sesiones' => 'Sesiones',
                        'plantillas-formulario' => 'Plantillas de formulario',
                        'asignaciones' => 'Asignaciones (duplas)',
                        'herramientas' => 'Herramientas (subir materiales)',
                        'encuestas' => 'Encuestas',
                        'plantillas-correo' => 'Plantillas de correo',
                        'recordatorios' => 'Recordatorios',
                        'notificacion' => 'Enviar notificación',
                        'reportes' => 'Reportes y Cronograma',
                        'faq' => 'Preguntas frecuentes',
                    ];
                @endphp
                @foreach ($toc as $id => $label)
                    <a href="#{{ $id }}" class="text-primary-600 hover:underline dark:text-primary-400">{{ $label }}</a>
                @endforeach
            </div>
        </section>

        {{-- ★ FLUJO --}}
        <section id="flujo" class="rounded-xl border border-primary-200 bg-primary-50/40 p-6 dark:border-primary-400/20 dark:bg-primary-400/5">
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">★ Cómo montar un programa, de principio a fin</h2>
            <p class="mt-3">Si vas a empezar de cero, este es el orden recomendado. Cada paso se detalla más abajo en su sección.</p>
            <ol class="steps mt-4 space-y-3">
                <li><strong>Organización</strong> — asegúrate de tener tu organización creada (con logo y colores). <em>(Solo superadmin.)</em></li>
                <li><strong>Usuarios</strong> — crea las cuentas de coordinadores, facilitadores (mentores) y participantes.</li>
                <li><strong>Tipo de programa</strong> — define los nombres visibles de los roles (p. ej. «Mentor» y «Mentee»).</li>
                <li><strong>Programa</strong> — créalo eligiendo cliente, tipo, fechas y branding. Añade sus <strong>etapas</strong>.</li>
                <li><strong>Sesiones</strong> — crea las sesiones del programa (número, nombre, objetivo, fechas, encuesta y formulario a registrar).</li>
                <li><strong>Herramientas</strong> — sube los materiales y asócialos al programa, etapa o sesión.</li>
                <li><strong>Asignaciones</strong> — forma las duplas: empareja cada facilitador con su participante.</li>
                <li><strong>Comunicaciones</strong> — configura las plantillas de correo y los recordatorios; envía las invitaciones de acceso.</li>
                <li><strong>Seguimiento</strong> — usa Reportes y Cronograma para monitorear el avance.</li>
            </ol>
        </section>

        {{-- ADMINISTRACIÓN --}}
        <h2 id="organizaciones" class="text-base font-bold uppercase tracking-wide text-gray-400">Grupo · Administración</h2>

        <section class="rounded-xl border border-gray-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Organizaciones</h2>
            <p class="mt-2 text-xs font-medium text-gray-400">Solo superadministrador</p>
            <p class="mt-3">Cada <strong>organización</strong> es un espacio aislado con su propia marca, usuarios y programas. Al crear una defines:</p>
            <ul class="mt-3 list-disc space-y-1 pl-5">
                <li><strong>Datos generales:</strong> nombre, dominio, idioma por defecto (ES/EN), si es «organización operadora» y su estado.</li>
                <li><strong>Branding:</strong> logo, fondo de login, color principal y secundario.</li>
                <li><strong>Textos:</strong> mensaje de bienvenida y pie de página (en español e inglés).</li>
                <li><strong>Popup de bienvenida:</strong> puedes mostrar un popup con un video (YouTube/Vimeo) en el primer ingreso.</li>
            </ul>
            <p class="mt-3 text-gray-500">Las organizaciones eliminadas se pueden <strong>restaurar</strong> (borrado suave).</p>
        </section>

        <section id="usuarios" class="rounded-xl border border-gray-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Usuarios</h2>
            <p class="mt-3">Todas las personas del sistema. Para crear una cuenta pulsa <strong>«Nuevo usuario»</strong> y completa:</p>
            <ul class="mt-3 list-disc space-y-1 pl-5">
                <li><strong>Cuenta:</strong> nombre completo, correo (único), <strong>rol</strong> (Superadministrador, Administrador de organización, Coordinador, Facilitador o Participante), organización y contraseña.</li>
                <li><strong>Perfil:</strong> celular/WhatsApp, fotografía, cargo, área, unidad de negocio, empresa y una breve descripción.</li>
                <li><strong>Preferencias:</strong> zona horaria, idioma y estado (activo/inactivo).</li>
            </ul>
            <div class="mt-3 rounded-lg bg-gray-50 p-3 text-gray-600 dark:bg-white/5 dark:text-gray-400">El administrador de organización solo ve y crea usuarios de <strong>su</strong> organización, y no puede crear superadministradores.</div>
        </section>

        <section id="clientes" class="rounded-xl border border-gray-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Clientes</h2>
            <p class="mt-2 text-xs font-medium text-gray-400">Oculto para el coordinador</p>
            <p class="mt-3">Un <strong>cliente</strong> es una marca o empresa dentro de tu organización. Sirve para agrupar programas y darles su propia identidad (logo, fondo de login, colores y textos de bienvenida en ES/EN). Al crear un programa podrás asociarlo a un cliente.</p>
        </section>

        {{-- CONFIGURACIÓN --}}
        <h2 id="tipos" class="text-base font-bold uppercase tracking-wide text-gray-400">Grupo · Configuración</h2>

        <section class="rounded-xl border border-gray-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Tipos de programa</h2>
            <p class="mt-2 text-xs font-medium text-gray-400">Oculto para el coordinador</p>
            <p class="mt-3">Un <strong>tipo de programa</strong> es una plantilla que define, sobre todo, <strong>cómo se llaman los roles</strong> de cara al usuario. Por ejemplo, un tipo «Mentoría» mostrará «Mentor» y «Mentee»; otro podría usar «Coach» y «Coachee».</p>
            <ul class="mt-3 list-disc space-y-1 pl-5">
                <li>Nombre y descripción (ES/EN).</li>
                <li><strong>Nombres visibles de roles:</strong> etiqueta del facilitador y del participante, en ES/EN.</li>
                <li>Si dejas la organización vacía, el tipo queda como <strong>catálogo global</strong> disponible para todas.</li>
            </ul>
        </section>

        {{-- PROGRAMA --}}
        <h2 id="programas" class="text-base font-bold uppercase tracking-wide text-gray-400">Grupo · Programa</h2>

        <section class="rounded-xl border border-gray-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Programas</h2>
            <p class="mt-2 text-xs font-medium text-gray-400">Coordinador: solo lectura</p>
            <p class="mt-3">El <strong>programa</strong> es el acompañamiento concreto que vivirán las duplas. Al crearlo defines:</p>
            <ul class="mt-3 list-disc space-y-1 pl-5">
                <li>Nombre y descripción (ES/EN) y <strong>estado</strong> (Borrador, Activo, Pausado, Finalizado, Archivado).</li>
                <li>Cliente, <strong>tipo de programa</strong> (del que hereda los nombres de rol) e idioma.</li>
                <li>Fechas de inicio y fin, logo y colores propios.</li>
            </ul>
            <p class="mt-4 font-medium text-gray-900 dark:text-white">Dentro de un programa gestionas además:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li><strong>Etapas:</strong> bloques que agrupan sesiones (nombre, color, orden).</li>
                <li><strong>Sesiones:</strong> el currículo del programa (ver la sección Sesiones).</li>
                <li><strong>Popups de bienvenida:</strong> un mensaje/video que se muestra al entrar al portal, configurable por rol.</li>
            </ul>
            <p class="mt-3 text-gray-500">La tabla ofrece la acción <strong>«Exportar avance»</strong> (Excel).</p>
        </section>

        <section id="sesiones" class="rounded-xl border border-gray-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Sesiones</h2>
            <p class="mt-2 text-xs font-medium text-gray-400">Coordinador: solo lectura</p>
            <p class="mt-3">Cada <strong>sesión</strong> es un encuentro o hito del programa. Al crearla defines:</p>
            <ul class="mt-3 list-disc space-y-1 pl-5">
                <li>Programa, etapa, <strong>número</strong> y estado.</li>
                <li>Nombre y objetivo (ES/EN).</li>
                <li><strong>Link de encuesta</strong> (Google Forms) si la sesión lleva una.</li>
                <li><strong>Plantilla de formulario:</strong> al elegirla se copian sus campos; son los datos que el mentor rellenará al registrar la sesión.</li>
                <li>Fechas de inicio/fin, si <strong>requiere registro</strong> y si es <strong>visible para el participante</strong>.</li>
            </ul>
            <div class="mt-3 rounded-lg bg-gray-50 p-3 text-gray-600 dark:bg-white/5 dark:text-gray-400">Los <strong>campos personalizados</strong> de la sesión son el formulario dinámico del mentor. Puedes marcar algunos como <strong>visibles para el participante</strong>: esos valores aparecerán en «Acuerdos y próximos pasos» del portal del participante una vez completada la sesión.</div>
        </section>

        <section id="plantillas-formulario" class="rounded-xl border border-gray-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Plantillas de formulario</h2>
            <p class="mt-2 text-xs font-medium text-gray-400">Oculto para el coordinador</p>
            <p class="mt-3">Conjuntos <strong>reutilizables de campos</strong> que se copian a las sesiones para no rehacerlos cada vez. Defines el nombre, la organización/programa y cada campo (texto, fecha, número, archivo…). Luego, al crear una sesión, eliges esta plantilla y sus campos se aplican automáticamente.</p>
        </section>

        {{-- PERSONAS --}}
        <h2 id="asignaciones" class="text-base font-bold uppercase tracking-wide text-gray-400">Grupo · Personas</h2>

        <section class="rounded-xl border border-gray-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Asignaciones (duplas)</h2>
            <p class="mt-3">Una <strong>asignación</strong> —o «dupla»— empareja a un <strong>facilitador</strong> con un <strong>participante</strong> dentro de un programa. Es la unidad central del seguimiento.</p>
            <ol class="steps mt-4 space-y-3">
                <li>Pulsa <strong>«Nueva asignación»</strong>.</li>
                <li>Elige el <strong>programa</strong>, el <strong>facilitador</strong> y el <strong>participante</strong>.</li>
                <li>Define estado y fechas. Guarda.</li>
            </ol>
            <p class="mt-3 text-gray-500">Un participante solo puede tener un facilitador por programa. Con <strong>«Ver dupla»</strong> abres su ficha, que incluye cuatro pestañas:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li><strong>Sesiones registradas:</strong> historial de cada registro (estado, asistencia, modalidad, fecha real, enlace de reunión y respuestas del formulario).</li>
                <li><strong>Sesiones adicionales:</strong> sesiones extra propias de esa dupla.</li>
                <li><strong>Archivos compartidos:</strong> los materiales privados de la dupla.</li>
                <li><strong>Buzón de mensajes:</strong> la conversación entre mentor y participante.</li>
            </ul>
        </section>

        {{-- RECURSOS --}}
        <h2 id="herramientas" class="text-base font-bold uppercase tracking-wide text-gray-400">Grupo · Recursos</h2>

        <section class="rounded-xl border border-gray-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Herramientas — subir materiales</h2>
            <p class="mt-2 text-xs font-medium text-gray-400">Coordinador: solo lectura</p>
            <p class="mt-3">Las <strong>Herramientas</strong> son los materiales y recursos del programa (documentos, plantillas, videos, enlaces). Es la forma principal de <strong>subir materiales</strong> que verán mentores y participantes en sus portales.</p>
            <ol class="steps mt-4 space-y-3">
                <li>Ve a <strong>Recursos → Herramientas</strong> y pulsa <strong>«Nueva herramienta»</strong>.</li>
                <li>Escribe el <strong>nombre</strong> y la descripción (ES/EN), el tipo, la categoría y la visibilidad.</li>
                <li>Elige el <strong>recurso</strong>:
                    <ul class="mt-1 list-disc space-y-1 pl-5">
                        <li><strong>Archivo:</strong> súbelo desde tu equipo (PDF, Office, imagen…).</li>
                        <li><strong>Enlace externo:</strong> pega la dirección (Google Drive, Forms, YouTube…).</li>
                    </ul>
                </li>
                <li>En <strong>«Asociaciones»</strong> indica <strong>dónde aparece</strong>: por programa, y opcionalmente acotado a una etapa o a una sesión concreta. Puedes añadir varias asociaciones.</li>
                <li>Guarda. El material aparecerá en los portales del mentor y del participante según lo asociado.</li>
            </ol>
            <div class="mt-3 rounded-lg bg-gray-50 p-3 text-gray-600 dark:bg-white/5 dark:text-gray-400">Además de estos materiales «oficiales», dentro de cada dupla el mentor y el participante pueden subir sus propios <strong>archivos compartidos</strong> desde sus portales.</div>
        </section>

        <section id="encuestas" class="rounded-xl border border-gray-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Encuestas</h2>
            <p class="mt-2 text-xs font-medium text-gray-400">Coordinador: solo lectura</p>
            <p class="mt-3">Encuestas externas de <strong>Google Forms</strong>. Defines el nombre, el <strong>enlace</strong>, para quién es visible, el momento (p. ej. después de la sesión) y el alcance (programa, etapa o sesión). Aparecen como botón «Abrir encuesta» en los portales.</p>
            <div class="mt-3 rounded-lg bg-amber-50 p-3 text-amber-900 dark:bg-amber-400/10 dark:text-amber-200">Las respuestas se recogen <strong>en Google Forms</strong>, no dentro de la plataforma. El estado «encuesta pendiente» es una estimación por diseño.</div>
        </section>

        {{-- COMUNICACIONES --}}
        <h2 id="plantillas-correo" class="text-base font-bold uppercase tracking-wide text-gray-400">Grupo · Comunicaciones</h2>

        <section class="rounded-xl border border-gray-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Plantillas de correo</h2>
            <p class="mt-2 text-xs font-medium text-gray-400">Oculto para el coordinador</p>
            <p class="mt-3">Los correos que envía la plataforma. Cada plantilla tiene un <strong>tipo</strong> (Invitación de acceso, Bienvenida, Recordatorio de sesión, Recordatorio de registro, Sesión vencida, Cierre de programa o Personalizado) y contenido en ES/EN con:</p>
            <ul class="mt-3 list-disc space-y-1 pl-5">
                <li><strong>Asunto</strong> y <strong>cuerpo</strong> con editor enriquecido (negritas, listas, títulos, enlaces, imágenes).</li>
                <li>Imagen de cabecera opcional.</li>
                <li><strong>Variables</strong> como <code class="rounded bg-gray-100 px-1 dark:bg-white/10">&#123;&#123;user_name&#125;&#125;</code> que se reemplazan al enviar.</li>
                <li>Panel de <strong>vista previa</strong> con selector de idioma.</li>
            </ul>
            <p class="mt-3 text-gray-500">Si dejas el programa vacío, la plantilla aplica a toda la organización.</p>
        </section>

        <section id="recordatorios" class="rounded-xl border border-gray-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Recordatorios</h2>
            <p class="mt-2 text-xs font-medium text-gray-400">Oculto para el coordinador</p>
            <p class="mt-3">Correos <strong>automáticos</strong> ligados a las sesiones. Configuras:</p>
            <ul class="mt-3 list-disc space-y-1 pl-5">
                <li><strong>Destinatario:</strong> facilitador, participante, ambos o administrador.</li>
                <li><strong>Programación:</strong> respecto al inicio o fin de la sesión, antes/después/el mismo día, y cuántos días.</li>
                <li><strong>Contenido:</strong> una plantilla de correo, o un asunto y mensaje propios (ES/EN).</li>
            </ul>
        </section>

        <section id="notificacion" class="rounded-xl border border-gray-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Enviar notificación</h2>
            <p class="mt-3">Envío <strong>masivo</strong> de correos puntuales. Pulsa <strong>«Redactar notificación»</strong> y elige:</p>
            <ol class="steps mt-4 space-y-3">
                <li><strong>Destinatarios («Enviar a»):</strong> todos, solo mentores, solo mentees, solo coordinadores, un usuario específico, duplas seleccionadas o duplas por estado (atrasadas, sin inicio, con sesión pendiente, con encuesta pendiente). Puedes filtrar por programa.</li>
                <li><strong>Dentro de cada dupla:</strong> enviar a mentor y mentee, solo mentor o solo mentee.</li>
                <li><strong>Contenido:</strong> una plantilla de correo, o asunto + mensaje (admite Markdown e imágenes) con variables.</li>
                <li>Envía. Verás un panel con los envíos recientes.</li>
            </ol>
            <p class="mt-3 text-gray-500">Disponible también para el <strong>coordinador</strong>.</p>
        </section>

        {{-- REPORTES --}}
        <h2 id="reportes" class="text-base font-bold uppercase tracking-wide text-gray-400">Grupo · Reportes</h2>

        <section class="rounded-xl border border-gray-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Reportes y Cronograma</h2>
            <ul class="mt-3 list-disc space-y-2 pl-5">
                <li><strong>Reportes de avance:</strong> muestra en qué sesión va cada dupla y las agrupa por estado — <strong>dentro</strong> (al día), <strong>fuera</strong> (atrasadas) y <strong>sin inicio</strong>. Incluye <strong>«Exportar Excel»</strong>.</li>
                <li><strong>Cronograma:</strong> calendario mensual con las sesiones de la organización y contadores de duplas. Navega entre meses con las flechas.</li>
            </ul>
            <p class="mt-3 text-gray-500">Ambos están disponibles para superadmin, administrador de organización y coordinador.</p>
        </section>

        {{-- FAQ --}}
        <section id="faq" class="rounded-xl border border-gray-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Preguntas frecuentes</h2>
            <dl class="mt-4 space-y-4">
                <div>
                    <dt class="font-semibold text-gray-900 dark:text-white">¿Cómo doy acceso a un mentor o participante?</dt>
                    <dd class="mt-1 text-gray-600 dark:text-gray-400">Crea su usuario con el rol correcto y envíale la <strong>invitación de acceso</strong> (por correo). Recibirá un enlace y una contraseña temporal que deberá cambiar al entrar.</dd>
                </div>
                <div>
                    <dt class="font-semibold text-gray-900 dark:text-white">¿Dónde subo un documento para que lo vean las duplas?</dt>
                    <dd class="mt-1 text-gray-600 dark:text-gray-400">En <strong>Recursos → Herramientas</strong>: crea la herramienta (archivo o enlace) y asóciala al programa, etapa o sesión donde debe aparecer.</dd>
                </div>
                <div>
                    <dt class="font-semibold text-gray-900 dark:text-white">Un mentor no ve a su participante.</dt>
                    <dd class="mt-1 text-gray-600 dark:text-gray-400">Revisa que exista la <strong>asignación</strong> (dupla) entre ambos en ese programa y que esté activa.</dd>
                </div>
                <div>
                    <dt class="font-semibold text-gray-900 dark:text-white">El participante no ve una sesión.</dt>
                    <dd class="mt-1 text-gray-600 dark:text-gray-400">Comprueba que la sesión esté marcada como <strong>«Visible para el participante»</strong>.</dd>
                </div>
                <div>
                    <dt class="font-semibold text-gray-900 dark:text-white">Soy coordinador y no puedo editar algo.</dt>
                    <dd class="mt-1 text-gray-600 dark:text-gray-400">El coordinador tiene acceso de <strong>solo lectura</strong> a gran parte del contenido; sí puede gestionar usuarios/asignaciones y enviar notificaciones. Para otros cambios, pide apoyo a un administrador.</dd>
                </div>
            </dl>
        </section>

    </div>
</x-filament-panels::page>
