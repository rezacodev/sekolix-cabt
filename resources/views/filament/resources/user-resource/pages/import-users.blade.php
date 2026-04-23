{{-- resources/views/filament/resources/user-resource/pages/import-users.blade.php --}}
<x-filament-panels::page>

@unless ($showPreview)
{{-- ══════════════════════════════════════════════════════════════════════════
     TAHAP 1 – Upload File
══════════════════════════════════════════════════════════════════════════ --}}

{{-- Info kode level --}}
<div class="rounded-xl p-4 mb-5" style="background:#eff6ff;outline:1px solid #bfdbfe;">
    <div class="flex items-start gap-3">
        <x-heroicon-o-information-circle class="w-5 h-5 mt-0.5 shrink-0" style="color:#3b82f6;"/>
        <div class="text-sm space-y-2 w-full" style="color:#1e40af;">
            <p class="font-semibold">Panduan mengisi file import Excel:</p>
            <ul class="list-disc list-inside space-y-1">
                <li>Kolom <strong>level</strong> wajib menggunakan kode string berikut:</li>
            </ul>
            <div class="overflow-x-auto mt-1">
                <table class="text-xs border-collapse w-auto">
                    <thead>
                        <tr style="background:#dbeafe;">
                            <th class="px-3 py-1 text-left font-semibold" style="border:1px solid #bfdbfe;">Kode</th>
                            <th class="px-3 py-1 text-left font-semibold" style="border:1px solid #bfdbfe;">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td class="px-3 py-1 font-mono" style="border:1px solid #bfdbfe;">peserta</td><td class="px-3 py-1" style="border:1px solid #bfdbfe;">Siswa / peserta ujian</td></tr>
                        <tr><td class="px-3 py-1 font-mono" style="border:1px solid #bfdbfe;">guru</td><td class="px-3 py-1" style="border:1px solid #bfdbfe;">Guru / pengajar</td></tr>
                        <tr><td class="px-3 py-1 font-mono" style="border:1px solid #bfdbfe;">admin</td><td class="px-3 py-1" style="border:1px solid #bfdbfe;">Administrator sekolah</td></tr>
                        <tr><td class="px-3 py-1 font-mono" style="border:1px solid #bfdbfe;">super_admin</td><td class="px-3 py-1" style="border:1px solid #bfdbfe;">Super Administrator</td></tr>
                    </tbody>
                </table>
            </div>
            <ul class="list-disc list-inside space-y-1 mt-2">
                <li>Kolom <strong>kode_rombel</strong> untuk peserta: gunakan kode rombel yang terdaftar, pisahkan dengan titik koma (<code class="px-1 rounded" style="background:#dbeafe;">;</code>) jika lebih dari satu. Contoh: <code class="px-1 rounded" style="background:#dbeafe;">X-IPA-1;X-IPA-2</code></li>
                <li>Unduh template Excel untuk format lengkap dan daftar rombel yang tersedia.</li>
            </ul>
        </div>
    </div>
</div>

{{-- Form upload --}}
<div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-white/10">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Upload File Excel</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Pilih file .xlsx sesuai template, lalu klik <strong>Parse & Preview Data</strong> untuk melihat hasilnya sebelum diimport.</p>
    </div>
    <div class="px-6 py-5">
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
    <div class="rounded-xl p-4 text-center" style="background:#f0fdf4;outline:1px solid #bbf7d0;">
        <p class="text-2xl font-bold" style="color:#16a34a;">{{ $validCount }}</p>
        <p class="text-xs mt-0.5" style="color:#15803d;">Baris Valid</p>
    </div>
    <div class="rounded-xl p-4 text-center" style="background:#fef2f2;outline:1px solid #fecaca;">
        <p class="text-2xl font-bold text-danger-600 dark:text-danger-400">{{ $invalidCount }}</p>
        <p class="text-xs mt-0.5" style="color:#b91c1c;">Baris Tidak Valid</p>
    </div>
</div>

{{-- Tombol Aksi --}}
<div class="flex gap-3 mb-5">
    @if ($validCount > 0)
    <x-filament::button
        wire:click="$set('showImportModal', true)"
        icon="heroicon-o-arrow-up-tray"
        color="success"
    >
        Konfirmasi Import {{ $validCount }} Baris Valid
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
    <div class="px-4 py-3 border-b border-gray-100 dark:border-white/10" style="background:#f0fdf4;">
        <h3 class="text-sm font-semibold flex items-center gap-2" style="color:#15803d;">
            <x-heroicon-o-check-circle class="w-4 h-4"/>
            Baris Valid ({{ $validCount }} baris)
        </h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
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
                        @php
                            $levelStyle = match ($item['row']['level']) {
                                'peserta'     => 'background:#f3f4f6;color:#374151;outline:1px solid #d1d5db;',
                                'guru'        => 'background:#eff6ff;color:#1d4ed8;outline:1px solid #bfdbfe;',
                                'admin'       => 'background:#fefce8;color:#a16207;outline:1px solid #fde68a;',
                                'super_admin' => 'background:#f0fdf4;color:#15803d;outline:1px solid #bbf7d0;',
                                default       => 'background:#f3f4f6;color:#374151;outline:1px solid #d1d5db;',
                            };
                        @endphp
                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium" style="{{ $levelStyle }}">{{ $item['row']['level'] }}</span>
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
    <div class="px-4 py-3 border-b border-gray-100 dark:border-white/10" style="background:#fef2f2;">
        <h3 class="text-sm font-semibold flex items-center gap-2" style="color:#b91c1c;">
            <x-heroicon-o-x-circle class="w-4 h-4"/>
            Baris Tidak Valid ({{ $invalidCount }} baris — tidak akan diimport)
        </h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
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
                <tr style="background:#fff5f5;">
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

{{-- ── Import Confirmation Modal ───────────────────────────────────────────── --}}
<div
    x-data="{ open: $wire.entangle('showImportModal') }"
    x-show="open"
    x-cloak
    x-trap.noscroll="open"
    class="fixed inset-0 z-50 flex items-center justify-center p-4">

    {{-- Backdrop --}}
    <div
        class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"
        x-on:click="$wire.set('showImportModal', false)"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">
    </div>

    {{-- Modal panel --}}
    <div
        class="relative z-10 w-full max-w-sm rounded-2xl bg-white dark:bg-gray-900 shadow-2xl ring-1 ring-gray-950/10 dark:ring-white/10 p-6"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95">

        {{-- Icon --}}
        <div class="flex items-center justify-center w-12 h-12 rounded-full mx-auto mb-4" style="background:#dcfce7;">
            <x-heroicon-o-arrow-up-tray class="w-6 h-6" style="color:#16a34a;"/>
        </div>

        {{-- Title & body --}}
        <h3 class="text-base font-bold text-gray-900 dark:text-white text-center mb-2">Konfirmasi Import Data?</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 text-center mb-2">
            Akan mengimport <span class="font-semibold text-gray-900 dark:text-white">{{ $validCount }} baris valid</span> ke database.
        </p>
        <p class="text-xs text-gray-400 text-center mb-6">
            Aksi ini <span class="font-semibold" style="color:#dc2626;">tidak dapat dibatalkan</span>. Pastikan data sudah benar sebelum melanjutkan.
        </p>

        {{-- Actions --}}
        <div class="flex gap-3" x-data="{ importing: false }">
            <button
                type="button"
                wire:click="$set('showImportModal', false)"
                :disabled="importing"
                :class="importing ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-200 dark:hover:bg-gray-700'"
                class="flex-1 inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 transition-colors">
                Batal
            </button>
            <button
                type="button"
                x-on:click="importing = true; $wire.doImport()"
                :disabled="importing"
                :class="importing ? 'opacity-75 cursor-not-allowed' : ''"
                class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white transition-colors" style="background:#16a34a;">
                <span x-show="importing" class="animate-spin inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full"></span>
                <span x-text="importing ? 'Sedang mengimport...' : 'Ya, Import Sekarang'"></span>
            </button>
        </div>
    </div>
</div>

@endunless

</x-filament-panels::page>
