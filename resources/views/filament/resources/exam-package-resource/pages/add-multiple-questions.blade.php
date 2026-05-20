<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament-panels::form>
            {{ $this->form }}
        </x-filament-panels::form>

        <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Daftar Soal</h2>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
