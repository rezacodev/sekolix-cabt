{{-- resources/views/filament/resources/laporan-resource/pages/statistik-soal.blade.php --}}
<x-filament-panels::page>

@php
    $tipeBadgeColor = [
        'PG'       => 'primary',
        'PG_BOBOT' => 'info',
        'PGJ'      => 'gray',
        'JODOH'    => 'gray',
        'ISIAN'    => 'warning',
        'URAIAN'   => 'danger',
    ];

    $tipeLabels = \App\Models\Question::TIPE_LABELS ?? [
        'PG' => 'PG', 'PG_BOBOT' => 'PG Bobot', 'PGJ' => 'PGJ',
        'JODOH' => 'Jodoh', 'ISIAN' => 'Isian', 'URAIAN' => 'Uraian',
    ];

    // Sort soalStats by no
    $sorted = $soalStats->sortBy('no');
@endphp

{{-- ── Info Paket ───────────────────────────────────────────────────────────── --}}
<div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 px-6 py-4">
    <p class="text-sm text-gray-500">Paket: <span class="font-semibold text-gray-900 dark:text-white">{{ $session->package?->nama ?? '—' }}</span>
    &nbsp;|&nbsp; Total Soal: <span class="font-semibold text-gray-900 dark:text-white">{{ $soalStats->count() }}</span>
    &nbsp;|&nbsp; Sesi: <span class="font-semibold text-gray-900 dark:text-white">{{ $session->nama_sesi }}</span>
    </p>
</div>

{{-- ── Per Soal ─────────────────────────────────────────────────────────────── --}}
@forelse ($sorted as $stat)
<div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">

    {{-- Header soal --}}
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-start gap-3">
        <span class="shrink-0 flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-800 text-sm font-bold text-gray-700 dark:text-gray-300">
            {{ $stat->no }}
        </span>
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
                <div class="flex">
                    <x-filament::badge :color="$tipeBadgeColor[$stat->tipe] ?? 'gray'" size="sm">
                        {{ $tipeLabels[$stat->tipe] ?? $stat->tipe }}
                    </x-filament::badge>
                </div>
            </div>
            <p class="text-sm text-gray-800 dark:text-gray-200 line-clamp-3">{{ $stat->teks ?: '(soal memiliki gambar/format khusus)' }}</p>
        </div>

        {{-- Ringkasan kanan --}}
        <div class="shrink-0 flex gap-6 text-center text-sm">
            <div>
                <p class="text-xs text-gray-400 mb-0.5">Total</p>
                <p class="font-bold text-gray-700 dark:text-gray-300">{{ $stat->total_jawab }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-0.5">Benar</p>
                <p class="font-bold text-success-600 dark:text-success-400">{{ $stat->jumlah_benar }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-0.5">Salah</p>
                <p class="font-bold text-danger-600 dark:text-danger-400">{{ $stat->jumlah_salah }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-0.5">Kosong</p>
                <p class="font-bold text-gray-400">{{ $stat->jumlah_kosong }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-0.5">% Benar</p>
                <p class="font-bold
                   @if($stat->persen_benar >= 70) text-success-600 dark:text-success-400
                   @elseif($stat->persen_benar >= 40) text-warning-600 dark:text-warning-400
                   @else text-danger-600 dark:text-danger-400
                   @endif">
                    {{ $stat->persen_benar }}%
                </p>
            </div>
        </div>
    </div>

    {{-- Progress bar % benar --}}
    <div class="px-6 pt-3 pb-1">
        <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
            <div class="h-full rounded-full transition-all
                @if($stat->persen_benar >= 70) bg-success-500
                @elseif($stat->persen_benar >= 40) bg-warning-500
                @else bg-danger-500
                @endif"
                @style(['width: ' . $stat->persen_benar . '%'])></div>
        </div>
    </div>

    {{-- Distribusi Opsi — hanya untuk PG, PG_BOBOT, PGJ --}}
    @if (!empty($stat->distribusi_opsi) && in_array($stat->tipe, ['PG', 'PG_BOBOT', 'PGJ']))
    <div class="px-6 py-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">Distribusi Pilihan Jawaban</p>
        <div class="space-y-2">
            @foreach ($stat->distribusi_opsi as $kode => $opsi)
            <div class="flex items-center gap-3">
                <span class="shrink-0 w-7 text-center text-xs font-bold
                    {{ $opsi['correct'] ? 'text-success-600 dark:text-success-400' : 'text-gray-500' }}">
                    {{ $kode }}
                </span>
                <div class="flex-1 h-5 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                    <div class="h-full rounded-full
                        {{ $opsi['correct'] ? 'bg-success-500' : 'bg-gray-400' }}"
                        @style(['width: ' . max($opsi['persen'], $opsi['count'] > 0 ? 2 : 0) . '%'])>
                    </div>
                </div>
                <span class="shrink-0 w-14 text-right text-xs font-medium text-gray-600 dark:text-gray-400">
                    {{ $opsi['count'] }} ({{ $opsi['persen'] }}%)
                </span>
                <span class="shrink-0 text-xs text-gray-400 truncate" style="max-width:16rem">{{ $opsi['teks'] }}</span>
                @if ($opsi['correct'])
                    <span class="shrink-0">
                        <x-filament::badge color="success" size="sm">Kunci</x-filament::badge>
                    </span>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @elseif (in_array($stat->tipe, ['ISIAN', 'URAIAN', 'JODOH']))
    <div class="px-6 py-3 pb-4">
        <p class="text-xs text-gray-400">
            @if($stat->tipe === 'URAIAN')
                Soal uraian dinilai manual — distribusi opsi tidak tersedia.
            @else
                Distribusi per opsi tidak ditampilkan untuk tipe {{ $tipeLabels[$stat->tipe] ?? $stat->tipe }}.
            @endif
        </p>
    </div>
    @endif

</div>
@empty
<div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 px-6 py-10 text-center text-gray-400">
    Belum ada data jawaban untuk dihitung statistiknya.
</div>
@endforelse

</x-filament-panels::page>
