<div>
    <form wire:submit="complete" class="space-y-6">
        {{ $this->form }}

        <div class="flex items-center gap-3 pt-2">
            <button type="submit"
                class="rounded-lg px-5 py-2.5 text-sm font-medium text-white transition hover:opacity-90"
                style="background: var(--brand)">
                {{ __('Registrar sesión') }}
            </button>
            <button type="button" wire:click="saveDraft"
                class="rounded-lg px-5 py-2.5 text-sm font-medium border border-gray-300 text-gray-700 hover:bg-gray-50">
                {{ __('Guardar borrador') }}
            </button>
        </div>
    </form>

    <x-filament-actions::modals />
</div>
