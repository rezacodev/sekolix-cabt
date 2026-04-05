{{-- resources/views/filament/resources/user-resource/pages/import-users.blade.php --}}
<x-filament-panels::page>

@unless ($showPreview)
{{-- ══════════════════════════════════════════════════════════════════════════
     TAHAP 1 – Upload File
══════════════════════════════════════════════════════════════════════════ --}}

{{-- Info kode level --}}
<div class="rounded-xl bg-blue-50 dark:bg-blue-950/30 ring-1 ring-blue-200 dark:ring-blue-800 p-5 mb-5">
    <div class="flex items-start gap-3">
        <x-heroicon-o-information-circle class="w-5 h-5 text-blue-500 dark:text-blue-400 mt-0.5 shrink-0"/>
        <div class="text-sm text-blue-800 dark:text-blue-300 space-y-2 w-full">
            <p class="font-semibold">Panduan mengisi file import Excel:</p>
            <ul class="list-disc list-inside space-y-1">
                <li>Kolom <strong>level</strong> wajib menggunakan kode string berikut:</li>
            </ul>
            <div class="overflow-x-auto mt-1">
                <table class="text-xs border-collapse w-auto">
                    <thead>
                        <tr class="bg-blue-100 dark:bg-blue-900/40">
                            <th class="border border-blue-200 dark:border-blue-700 px-3 py-1 text-left font-semibold">Kode</th>
                            <th class="border border-blue-200 dark:border-blue-700 px-3 py-1 text-left font-semibold">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td class="border border-blue-200 dark:border-blue-700 px-3 py-1 font-mono">peserta</td><td class="border border-blue-200 dark:border-blue-700 px-3 py-1">Siswa / peserta ujian</td></tr>
                        <tr><td class="border border-blue-200 dark:border-blue-700 px-3 py-1 font-mono">guru</td><td class="border border-blue-200 dark:border-blue-700 px-3 py-1">Guru / pengajar</td></tr>
                        <tr><td class="border border-blue-200 dark:border-blue-700 px-3 py-1 font-mono">admin</td><td class="border border-blue-200 dark:border-blue-700 px-3 py-1">Administrator sekolah</td></tr>
                        <tr><td class="border border-blue-200 dark:border-blue-700 px-3 py-1 font-mono">super_admin</td><td class="border border-blue-200 dark:border-blue-700 px-3 py-1">Super Administrator</td></tr>
                    </tbody>
                </table>
            </div>
            <ul class="list-disc list-inside space-y-1 mt-2">
                <li>Kolom <strong>kode_rombel</strong> untuk peserta: gunakan kode rombel yang terdaftar, pisahkan dengan titik koma (<code class="bg-blue-100 dark:bg-blue-900 px-1 rounded">;</code>) jika lebih dari satu. Contoh: <code class="bg-blue-100 dark:bg-blue-900 px-1 rounded">X-IPA-1;X-IPA-2</code></li>
                <li>Unduh template Excel untuk format lengkap dan daftar rombel yang tersedia.</li>
            </ul>
        </div>
    </div>
</div>

{{-- Form upload --}}
<div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
    <form wire:submit="parseFile">
        {{ $this->form }}

        <div class="mt-6 flex gap-3">
            <x-filament::button
                type="submit"
                icon="heroicon-o-magnifying-glass"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-70"
            >
                <span wire:loading wire:target="parseFile" class="inline-flex items-center gap-2">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    Memproses...
                </span>
                <span wire:loading.remove wire:target="parseFile">Parse & Preview Data</span>
            </x-filament::button>

            <x-filament::button
                tag="a"
                href="{{ \App\Filament\Resources\UserResource::getUrl() }}"
                color="gray"
                icon="heroicon-o-arrow-left"
            >
                Kembali
            </x-filament::button>
        </div>
    </form>
</div>

@else
{{-- ══════════════════════════════════════════════════════════════════════════
     TAHAP 2 – Preview Hasil Parsing
══════════════════════════════════════════════════════════════════════════ --}}

@php
    $validRows   = array_filter($parsedRows, fn($r) => $r['valid']);
    $invalidRows = array_filter($parsedRows, fn($r) => !$r['valid']);
    $validCount   = count($validRows);
    $invalidCount = count($invalidRows);
@endphp

{{-- Ringkasan --}}
<div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem;" class="mb-4">
    <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4 text-center">
        <p class="text-2xl font-bold text-gray-700 dark:text-white">{{ count($parsedRows) }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Total Baris</p>
    </div>
    <div class="rounded-xl bg-success-50 dark:bg-success-950/30 ring-1 ring-success-200 dark:ring-success-800 p-4 text-center">
        <p class="text-2xl font-bold text-success-600 dark:text-success-400">{{ $validCount }}</p>
        <p class="text-xs text-success-700 dark:text-success-400 mt-0.5">Baris Valid</p>
    </div>
    <div class="rounded-xl bg-danger-50 dark:bg-danger-950/30 ring-1 ring-danger-200 dark:ring-danger-800 p-4 text-center">
        <p class="text-2xl font-bold text-danger-600 dark:text-danger-400">{{ $invalidCount }}</p>
        <p class="text-xs text-danger-700 dark:text-danger-400 mt-0.5">Baris Tidak Valid</p>
    </div>
</div>

{{-- Tombol Aksi --}}
<div class="flex gap-3 mb-5">
    @if ($validCount > 0)
    <x-filament::button
        wire:click="doImport"
        icon="heroicon-o-arrow-up-tray"
        color="success"
        wire:loading.attr="disabled"
        wire:loading.class="opacity-70"
        wire:confirm="Import {{ $validCount }} baris valid? Aksi ini tidak dapat dibatalkan."
    >
        <span wire:loading wire:target="doImport" class="inline-flex items-center gap-2">
            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            Mengimport...
        </span>
        <span wire:loading.remove wire:target="doImport">Konfirmasi Import {{ $validCount }} Baris Valid</span>
    </x-filament::button>
    @endif

    <x-filament::button
        wire:click="resetImport"
        color="gray"
        icon="heroicon-o-arrow-path"
    >
        Batal / Upload Ulang
    </x-filament::button>
</div>

{{-- Tabel valid --}}
@if ($validCount > 0)
<div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden mb-5">
    <div class="px-5 py-3 border-b border-gray-100 dark:border-white/10 bg-success-50 dark:bg-success-950/30">
        <h3 class="text-sm font-semibold text-success-700 dark:text-success-400 flex items-center gap-2">
            <x-heroicon-o-check-circle class="w-4 h-4"/>
            Baris Valid ({{ $validCount }} baris)
        </h3>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                    <th class="px-4 py-2 text-left">#</th>
                    <th class="px-4 py-2 text-left">Nama Lengkap</th>
                    <th class="px-4 py-2 text-left">Email</th>
                    <th class="px-4 py-2 text-left">Level</th>
                    <th class="px-4 py-2 text-left">Kode Rombel</th>
                    <th class="px-4 py-2 text-left">Nomor Peserta</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($validRows as $item)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                    <td class="px-4 py-2 text-gray-400 text-xs">{{ $item['row_num'] }}</td>
                    <td class="px-4 py-2 text-gray-900 dark:text-white font-medium">{{ $item['row']['nama_lengkap'] }}</td>
                    <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $item['row']['email'] }}</td>
                    <td class="px-4 py-2">
                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset
                            {{ $item['row']['level'] === 'peserta'     ? 'bg-gray-100 text-gray-700 ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700' : '' }}
                            {{ $item['row']['level'] === 'guru'        ? 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-950/30 dark:text-blue-400 dark:ring-blue-800' : '' }}
                            {{ $item['row']['level'] === 'admin'       ? 'bg-yellow-50 text-yellow-700 ring-yellow-200 dark:bg-yellow-950/30 dark:text-yellow-400 dark:ring-yellow-800' : '' }}
                            {{ $item['row']['level'] === 'super_admin' ? 'bg-green-50 text-green-700 ring-green-200 dark:bg-green-950/30 dark:text-green-400 dark:ring-green-800' : '' }}
                        ">{{ $item['row']['level'] }}</span>
                    </td>
                    <td class="px-4 py-2 text-gray-600 dark:text-gray-300 font-mono text-xs">{{ $item['row']['kode_rombel'] ?: '—' }}</td>
                    <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $item['row']['nomor_peserta'] ?: '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Tabel tidak valid --}}
@if ($invalidCount > 0)
<div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
    <div class="px-5 py-3 border-b border-gray-100 dark:border-white/10 bg-danger-50 dark:bg-danger-950/30">
        <h3 class="text-sm font-semibold text-danger-700 dark:text-danger-400 flex items-center gap-2">
            <x-heroicon-o-x-circle class="w-4 h-4"/>
            Baris Tidak Valid ({{ $invalidCount }} baris — tidak akan diimport)
        </h3>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                    <th class="px-4 py-2 text-left">#</th>
                    <th class="px-4 py-2 text-left">Nama Lengkap</th>
                    <th class="px-4 py-2 text-left">Email</th>
                    <th class="px-4 py-2 text-left">Level</th>
                    <th class="px-4 py-2 text-left">Kode Rombel</th>
                    <th class="px-4 py-2 text-left">Pesan Error</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($invalidRows as $item)
                <tr class="bg-danger-50/30 dark:bg-danger-950/10">
                    <td class="px-4 py-2 text-gray-400 text-xs">{{ $item['row_num'] }}</td>
                    <td class="px-4 py-2 text-gray-900 dark:text-white font-medium">{{ $item['row']['nama_lengkap'] ?: '—' }}</td>
                    <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $item['row']['email'] ?: '—' }}</td>
                    <td class="px-4 py-2 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $item['row']['level'] ?: '—' }}</td>
                    <td class="px-4 py-2 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $item['row']['kode_rombel'] ?: '—' }}</td>
                    <td class="px-4 py-2">
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($item['errors'] as $err)
                            <li class="text-danger-600 dark:text-danger-400 text-xs">{{ $err }}</li>
                            @endforeach
                        </ul>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endunless

</x-filament-panels::page>
