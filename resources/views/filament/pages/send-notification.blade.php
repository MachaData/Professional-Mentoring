<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-white/5">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Enviar un correo a tu audiencia</h2>
            <p class="mt-1 text-sm text-gray-500">
                Usa «Redactar notificación» para enviar a todos, solo mentores, solo mentees, coordinadores,
                un usuario o dupla concreta, o a duplas filtradas por su estado (atrasadas, sin inicio, con
                sesión o encuesta pendiente). Puedes usar una plantilla de correo o escribir el mensaje.
            </p>
        </div>

        @php $recent = $this->recentBroadcasts(); @endphp
        @if ($recent->isNotEmpty())
            <section>
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Envíos recientes</h3>
                <div class="mt-2 overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500 dark:bg-white/5">
                            <tr>
                                <th class="px-4 py-2 font-medium">Asunto</th>
                                <th class="px-4 py-2 font-medium">Para</th>
                                <th class="px-4 py-2 font-medium">Estado</th>
                                <th class="px-4 py-2 font-medium">Fecha</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                            @foreach ($recent as $log)
                                <tr>
                                    <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $log->subject }}</td>
                                    <td class="px-4 py-2 text-gray-500">{{ $log->recipient_email }}</td>
                                    <td class="px-4 py-2">
                                        <span @class([
                                            'rounded-full px-2 py-0.5 text-xs font-medium',
                                            'bg-emerald-50 text-emerald-700' => $log->status === 'sent',
                                            'bg-rose-50 text-rose-700' => $log->status !== 'sent',
                                        ])>{{ $log->status === 'sent' ? 'Enviado' : 'Fallido' }}</span>
                                    </td>
                                    <td class="px-4 py-2 text-gray-500">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
