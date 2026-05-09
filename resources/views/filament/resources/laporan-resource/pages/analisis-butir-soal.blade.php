{{-- resources/views/filament/resources/laporan-resource/pages/analisis-butir-soal.blade.php --}}
<x-filament-panels::page>

{{-- ── Info Sesi ────────────────────────────────────────────────────────────── --}}
<div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 px-6 py-4 mb-4">
    <p class="text-sm text-gray-500">
        Paket: <span class="font-semibold text-gray-900 dark:text-white">{{ $this->record->package?->nama ?? '—' }}</span>
        &nbsp;|&nbsp;
        Sesi: <span class="font-semibold text-gray-900 dark:text-white">{{ $this->record->nama_sesi }}</span>
    </p>
    <p class="text-xs text-gray-400 mt-1">
        Statistik dihitung lintas semua attempt historis soal ini (bukan hanya sesi ini).
        P-value ideal: 0.3–0.7 &nbsp;|&nbsp; Daya Beda (D) ideal: ≥ 0.3
    </p>
</div>

{{-- ── Legenda ──────────────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap gap-3 mb-4 text-xs">
    <div class="flex items-center gap-1.5">
        <span class="inline-block w-3 h-3 rounded-full bg-green-500"></span>
        <span class="text-gray-600 dark:text-gray-400">P-value: 0.3–0.7 (ideal)</span>
    </div>
    <div class="flex items-center gap-1.5">
        <span class="inline-block w-3 h-3 rounded-full bg-yellow-400"></span>
        <span class="text-gray-600 dark:text-gray-400">P-value: 0.2–0.3 atau 0.7–0.8 (perhatian)</span>
    </div>
    <div class="flex items-center gap-1.5">
        <span class="inline-block w-3 h-3 rounded-full bg-red-500"></span>
        <span class="text-gray-600 dark:text-gray-400">P-value: &lt;0.2 atau &gt;0.8 (perlu revisi)</span>
    </div>
    <div class="mx-2 text-gray-300">|</div>
    <div class="flex items-center gap-1.5">
        <span class="inline-block w-3 h-3 rounded-full bg-green-500"></span>
        <span class="text-gray-600 dark:text-gray-400">D ≥ 0.3 (baik)</span>
    </div>
    <div class="flex items-center gap-1.5">
        <span class="inline-block w-3 h-3 rounded-full bg-yellow-400"></span>
        <span class="text-gray-600 dark:text-gray-400">D: 0.2–0.3 (cukup)</span>
    </div>
    <div class="flex items-center gap-1.5">
        <span class="inline-block w-3 h-3 rounded-full bg-red-500"></span>
        <span class="text-gray-600 dark:text-gray-400">D &lt; 0.2 (jelek — buang/revisi)</span>
    </div>
</div>

{{-- ── Tabel Statistik ──────────────────────────────────────────────────────── --}}
{{ $this->table }}

</x-filament-panels::page>
