{{-- resources/views/filament/pages/general-setting.blade.php --}}
<x-filament-panels::page>

    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex justify-end">
            <x-filament::button
                type="submit"
                icon="heroicon-o-check"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-70"
            >
                <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Menyimpan...
                </span>
                <span wire:loading.remove wire:target="save">Simpan Pengaturan</span>
            </x-filament::button>
        </div>
    </form>

</x-filament-panels::page>
