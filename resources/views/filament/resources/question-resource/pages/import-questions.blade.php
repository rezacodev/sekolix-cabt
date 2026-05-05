{{-- resources/views/filament/resources/question-resource/pages/import-questions.blade.php --}}
<x-filament-panels::page>

@unless ($showPreview)
{{-- ══════════════════════════════════════════════════════════════════════════
     TAHAP 1 – Upload File
══════════════════════════════════════════════════════════════════════════ --}}

{{-- Info panduan --}}
<div class="rounded-xl p-4 mb-5" style="background:#eff6ff;outline:1px solid #bfdbfe;">
    <div class="flex items-start gap-3">
        <x-heroicon-o-information-circle class="w-5 h-5 mt-0.5 shrink-0" style="color:#3b82f6;"/>
        <div class="text-sm space-y-2 w-full" style="color:#1e40af;">
            <p class="font-semibold">Panduan mengisi file import Excel:</p>
            <ul class="list-disc list-inside space-y-1">
                <li>Unduh template Excel terlebih dahulu — sheet pertama berisi contoh data, sheet kedua berisi petunjuk lengkap.</li>
                <li>Kolom <strong>tipe_soal</strong>, <strong>teks_soal</strong>, dan <strong>kesulitan</strong> wajib diisi.</li>
            </ul>
            <div class="overflow-x-auto mt-2">
                <table class="text-xs border-collapse w-auto">
                    <thead>
                        <tr style="background:#dbeafe;">
                            <th class="px-3 py-1 text-left font-semibold" style="border:1px solid #bfdbfe;">Tipe Soal</th>
                            <th class="px-3 py-1 text-left font-semibold" style="border:1px solid #bfdbfe;">Aturan Kolom Kunci</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td class="px-3 py-1 font-mono font-semibold" style="border:1px solid #bfdbfe;">PG / PG_BOBOT</td><td class="px-3 py-1" style="border:1px solid #bfdbfe;">Huruf opsi jawaban benar. Contoh: <code class="px-1 rounded" style="background:#dbeafe;">B</code></td></tr>
                        <tr style="background:#eff6ff;"><td class="px-3 py-1 font-mono font-semibold" style="border:1px solid #bfdbfe;">PGJ</td><td class="px-3 py-1" style="border:1px solid #bfdbfe;">Huruf dipisah koma. Contoh: <code class="px-1 rounded" style="background:#dbeafe;">A,C</code></td></tr>
                        <tr><td class="px-3 py-1 font-mono font-semibold" style="border:1px solid #bfdbfe;">BS</td><td class="px-3 py-1" style="border:1px solid #bfdbfe;"><code class="px-1 rounded" style="background:#dbeafe;">B</code> (Benar) atau <code class="px-1 rounded" style="background:#dbeafe;">S</code> (Salah)</td></tr>
                        <tr style="background:#eff6ff;"><td class="px-3 py-1 font-mono font-semibold" style="border:1px solid #bfdbfe;">ISIAN</td><td class="px-3 py-1" style="border:1px solid #bfdbfe;">Kata kunci jawaban, pisah koma jika lebih dari satu.</td></tr>
                        <tr><td class="px-3 py-1 font-mono font-semibold" style="border:1px solid #bfdbfe;">URAIAN / JODOH / CLOZE</td><td class="px-3 py-1" style="border:1px solid #bfdbfe;">Kosongkan. Dikelola setelah soal diimport.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Form upload --}}
<div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-white/10">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Upload File Excel</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Pilih file <code>.xlsx</code> sesuai template, lalu klik <strong>Parse &amp; Preview Data</strong> untuk melihat hasilnya sebelum diimport.</p>
    </div>
    <div class="px-6 py-5">
        <form wire:submit="parseFile">
            {{ $this->form }}
            <div class="mt-6 flex gap-3 flex-wrap">
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
                    <span wire:loading.remove wire:target="parseFile">Parse &amp; Preview Data</span>
                </x-filament::button>

                <x-filament::button
                    tag="a"
                    href="{{ \App\Filament\Resources\QuestionResource::getUrl() }}"
                    color="gray"
                    icon="heroicon-o-arrow-left"
                >
                    Kembali ke Bank Soal
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
    $validCount  = count($validRows);
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
<div class="flex gap-3 mb-5 flex-wrap">
    @if ($validCount > 0)
    <x-filament::button
        wire:click="$set('showImportModal', true)"
        icon="heroicon-o-arrow-up-tray"
        color="success"
    >
        Konfirmasi Import {{ $validCount }} Soal Valid
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
            Baris Valid — {{ $validCount }} soal akan diimport
        </h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                    <th class="px-4 py-2 text-left w-10">#</th>
                    <th class="px-4 py-2 text-left w-24">Tipe</th>
                    <th class="px-4 py-2 text-left">Teks Soal</th>
                    <th class="px-4 py-2 text-left w-28">Kategori</th>
                    <th class="px-4 py-2 text-left w-20">Kesulitan</th>
                    <th class="px-4 py-2 text-left w-16">Bobot</th>
                    <th class="px-4 py-2 text-left w-24">Kunci</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($validRows as $item)
                @php
                    $tipe = $item['row']['tipe_soal'];
                    $tipeBadgeStyle = match ($tipe) {
                        'PG'       => 'background:#eff6ff;color:#1d4ed8;outline:1px solid #bfdbfe;',
                        'PG_BOBOT' => 'background:#eef2ff;color:#4338ca;outline:1px solid #c7d2fe;',
                        'PGJ'      => 'background:#f0f9ff;color:#0369a1;outline:1px solid #bae6fd;',
                        'BS'       => 'background:#fefce8;color:#a16207;outline:1px solid #fde68a;',
                        'ISIAN'    => 'background:#f0fdf4;color:#15803d;outline:1px solid #bbf7d0;',
                        'URAIAN'   => 'background:#fdf4ff;color:#7e22ce;outline:1px solid #e9d5ff;',
                        'JODOH'    => 'background:#fff7ed;color:#c2410c;outline:1px solid #fed7aa;',
                        'CLOZE'    => 'background:#f8fafc;color:#475569;outline:1px solid #cbd5e1;',
                        default    => 'background:#f3f4f6;color:#374151;outline:1px solid #d1d5db;',
                    };
                    $kesulitanStyle = match ($item['row']['kesulitan']) {
                        'mudah'  => 'background:#f0fdf4;color:#15803d;outline:1px solid #bbf7d0;',
                        'sedang' => 'background:#fefce8;color:#a16207;outline:1px solid #fde68a;',
                        'sulit'  => 'background:#fef2f2;color:#b91c1c;outline:1px solid #fecaca;',
                        default  => 'background:#f3f4f6;color:#374151;outline:1px solid #d1d5db;',
                    };
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                    <td class="px-4 py-2 text-gray-400 text-xs">{{ $item['row_num'] }}</td>
                    <td class="px-4 py-2">
                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-mono font-semibold" style="{{ $tipeBadgeStyle }}">{{ $tipe }}</span>
                    </td>
                    <td class="px-4 py-2 text-gray-900 dark:text-white text-xs max-w-xs">
                        <span class="line-clamp-2" title="{{ strip_tags($item['row']['teks_soal']) }}">
                            {{ \Illuminate\Support\Str::limit(strip_tags($item['row']['teks_soal']), 120) }}
                        </span>
                    </td>
                    <td class="px-4 py-2 text-gray-600 dark:text-gray-300 text-xs">{{ $item['row']['kategori'] ?: '—' }}</td>
                    <td class="px-4 py-2">
                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium" style="{{ $kesulitanStyle }}">{{ $item['row']['kesulitan'] }}</span>
                    </td>
                    <td class="px-4 py-2 text-gray-600 dark:text-gray-300 text-xs text-center">{{ $item['row']['bobot'] ?? 1 }}</td>
                    <td class="px-4 py-2 font-mono text-xs text-gray-600 dark:text-gray-300">
                        @if ($item['row']['kunci'])
                            <span class="inline-flex items-center rounded-md px-2 py-0.5" style="background:#f3f4f6;outline:1px solid #d1d5db;">{{ $item['row']['kunci'] }}</span>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
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
            Baris Tidak Valid — {{ $invalidCount }} soal akan dilewati
        </h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                    <th class="px-4 py-2 text-left w-10">#</th>
                    <th class="px-4 py-2 text-left w-24">Tipe</th>
                    <th class="px-4 py-2 text-left">Teks Soal</th>
                    <th class="px-4 py-2 text-left">Pesan Error</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($invalidRows as $item)
                <tr style="background:#fff5f5;">
                    <td class="px-4 py-2 text-gray-400 text-xs">{{ $item['row_num'] }}</td>
                    <td class="px-4 py-2 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $item['row']['tipe_soal'] ?: '—' }}</td>
                    <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300 max-w-xs">
                        <span class="line-clamp-2">{{ \Illuminate\Support\Str::limit(strip_tags($item['row']['teks_soal']), 80) ?: '—' }}</span>
                    </td>
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

        <div class="flex items-center justify-center w-12 h-12 rounded-full mx-auto mb-4" style="background:#dcfce7;">
            <x-heroicon-o-arrow-up-tray class="w-6 h-6" style="color:#16a34a;"/>
        </div>

        <h3 class="text-base font-bold text-gray-900 dark:text-white text-center mb-2">Konfirmasi Import Soal?</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 text-center mb-2">
            Akan mengimport <span class="font-semibold text-gray-900 dark:text-white">{{ $validCount }} soal valid</span> ke bank soal.
        </p>
        @if ($invalidCount > 0)
        <p class="text-xs text-center mb-2" style="color:#b45309;">
            <x-heroicon-o-exclamation-triangle class="w-3.5 h-3.5 inline-block -mt-0.5"/>
            {{ $invalidCount }} baris tidak valid akan dilewati.
        </p>
        @endif
        <p class="text-xs text-gray-400 text-center mb-6">
            Aksi ini <span class="font-semibold" style="color:#dc2626;">tidak dapat dibatalkan</span>. Pastikan data sudah benar.
        </p>

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
                :class="importing ? 'opacity-70 cursor-not-allowed' : 'hover:bg-green-600'"
                class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white transition-colors"
                style="background:#16a34a;">
                <svg x-show="importing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span x-text="importing ? 'Mengimport...' : 'Ya, Import Sekarang'"></span>
            </button>
        </div>
    </div>
</div>

@endunless

</x-filament-panels::page>
