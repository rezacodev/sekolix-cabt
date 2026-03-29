{{-- resources/views/filament/resources/grading-resource/pages/grading-attempt-list.blade.php --}}
<x-filament-panels::page>

@php
    $session = $this->record;
    $package = $session->package;

    // Statistik soal per tipe
    $questions      = $package?->questions ?? collect();
    $totalSoal      = $questions->count();
    $perTipe        = $questions->groupBy('tipe')->map->count();

    $tipeLabels = \App\Models\Question::TIPE_LABELS;
    $tipeOrder  = ['PG', 'PG_BOBOT', 'PGJ', 'JODOH', 'ISIAN', 'URAIAN'];

    // Hitung total bobot
    $totalBobot = $questions->sum(fn ($q) => (float) $q->bobot);

    // Statistik peserta
    $participants     = $session->participants ?? collect();
    $totalPeserta     = $participants->count();
    $jumlahSelesai    = $participants->whereIn('status', ['selesai', 'diskualifikasi'])->count();
    $jumlahSedang     = $participants->where('status', 'sedang')->count();
    $jumlahBelum      = $participants->where('status', 'belum')->count();
@endphp

    {{-- ── Dua Card Berjejer ───────────────────────────────────────────────── --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; align-items: start;">

        {{-- Card 1: Informasi Sesi Ujian --}}
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <x-heroicon-o-play-circle class="w-5 h-5 text-gray-500 dark:text-gray-400"/>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Informasi Sesi Ujian</h3>
            </div>
            <div class="px-6 py-4 grid grid-cols-2 gap-x-6 gap-y-4">
                <div class="col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Nama Sesi</p>
                    <p class="mt-0.5 text-base font-semibold text-gray-900 dark:text-white">{{ $session->nama_sesi }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Mulai</p>
                    <p class="mt-0.5 text-sm text-gray-700 dark:text-gray-300">
                        {{ $session->waktu_mulai?->format('d M Y, H:i') ?? '—' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Selesai</p>
                    <p class="mt-0.5 text-sm text-gray-700 dark:text-gray-300">
                        {{ $session->waktu_selesai?->format('d M Y, H:i') ?? '—' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</p>
                    <div class="mt-0.5 flex">
                        @php
                            $statusColor = \App\Models\ExamSession::STATUS_COLORS[$session->status] ?? 'gray';
                            $statusLabel = \App\Models\ExamSession::STATUS_LABELS[$session->status] ?? $session->status;
                        @endphp
                        <x-filament::badge :color="$statusColor">{{ $statusLabel }}</x-filament::badge>
                    </div>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Dibuat oleh</p>
                    <p class="mt-0.5 text-sm text-gray-700 dark:text-gray-300">
                        {{ $session->creator?->name ?? '—' }}
                    </p>
                </div>

                {{-- Statistik Peserta --}}
                <div class="col-span-2 border-t border-gray-100 dark:border-gray-700 pt-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">Peserta</p>
                    <div class="flex gap-4">
                        <div class="text-center min-w-[3rem]">
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalPeserta }}</p>
                            <p class="text-xs text-gray-500">Total</p>
                        </div>
                        <div class="w-px bg-gray-200 dark:bg-gray-700 self-stretch"></div>
                        <div class="text-center min-w-[3rem]">
                            <p class="text-2xl font-bold text-success-600 dark:text-success-400">{{ $jumlahSelesai }}</p>
                            <p class="text-xs text-gray-500">Selesai</p>
                        </div>
                        <div class="text-center min-w-[3rem]">
                            <p class="text-2xl font-bold text-warning-600 dark:text-warning-400">{{ $jumlahSedang }}</p>
                            <p class="text-xs text-gray-500">Sedang</p>
                        </div>
                        <div class="text-center min-w-[3rem]">
                            <p class="text-2xl font-bold text-gray-400 dark:text-gray-500">{{ $jumlahBelum }}</p>
                            <p class="text-xs text-gray-500">Belum Mulai</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 2: Informasi Paket Ujian --}}
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <x-heroicon-o-clipboard-document-list class="w-5 h-5 text-gray-500 dark:text-gray-400"/>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Informasi Paket Ujian</h3>
            </div>
            <div class="px-6 py-4">
                @if ($package)
                    <div class="grid grid-cols-2 gap-x-6 gap-y-4">
                        <div class="col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Nama Paket</p>
                            <p class="mt-0.5 text-base font-semibold text-gray-900 dark:text-white">{{ $package->nama }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Durasi</p>
                            <p class="mt-0.5 text-sm text-gray-700 dark:text-gray-300">{{ $package->durasi_menit }} menit</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Mode Penilaian</p>
                            <div class="mt-0.5 flex">
                                <x-filament::badge color="{{ $package->grading_mode === 'manual' ? 'warning' : 'success' }}">
                                    {{ \App\Models\ExamPackage::GRADING_LABELS[$package->grading_mode] ?? $package->grading_mode }}
                                </x-filament::badge>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Max Pengulangan</p>
                            <p class="mt-0.5 text-sm text-gray-700 dark:text-gray-300">
                                {{ $package->max_pengulangan === 0 ? 'Tidak terbatas' : $package->max_pengulangan . '×' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Pengaturan</p>
                            <div class="mt-0.5 flex flex-wrap gap-1">
                                @if ($package->acak_soal)
                                    <x-filament::badge color="gray" size="sm">Acak Soal</x-filament::badge>
                                @endif
                                @if ($package->acak_opsi)
                                    <x-filament::badge color="gray" size="sm">Acak Opsi</x-filament::badge>
                                @endif
                                @if ($package->tampilkan_hasil)
                                    <x-filament::badge color="info" size="sm">Tampilkan Hasil</x-filament::badge>
                                @endif
                                @if ($package->tampilkan_review)
                                    <x-filament::badge color="info" size="sm">Review Jawaban</x-filament::badge>
                                @endif
                                @if (!$package->acak_soal && !$package->acak_opsi && !$package->tampilkan_hasil && !$package->tampilkan_review)
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </div>
                        </div>

                        {{-- Komposisi soal --}}
                        <div class="col-span-2 border-t border-gray-100 dark:border-gray-700 pt-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">
                                Komposisi Soal — Total {{ $totalSoal }} soal · Bobot {{ number_format($totalBobot, 0) }}
                            </p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($tipeOrder as $tipe)
                                    @if (($perTipe[$tipe] ?? 0) > 0)
                                        @php
                                            $badgeColor = match ($tipe) {
                                                'PG'       => 'primary',
                                                'PG_BOBOT' => 'info',
                                                'PGJ'      => 'gray',
                                                'JODOH'    => 'gray',
                                                'ISIAN'    => 'warning',
                                                'URAIAN'   => 'danger',
                                                default    => 'gray',
                                            };
                                        @endphp
                                        <div class="flex items-center gap-1.5 rounded-lg border border-gray-200 dark:border-gray-700
                                                    px-2.5 py-1.5 bg-white dark:bg-gray-800">
                                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $perTipe[$tipe] }}</span>
                                            <span class="text-xs text-gray-500"> soal</span>
                                            <x-filament::badge :color="$badgeColor" size="sm">
                                                {{ $tipeLabels[$tipe] ?? $tipe }}
                                            </x-filament::badge>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-500">Paket ujian tidak ditemukan.</p>
                @endif
            </div>
        </div>

    </div>

    {{-- ── Tabel Peserta ────────────────────────────────────────────────────── --}}
    <x-filament::section icon="heroicon-o-users" heading="Daftar Peserta & Status Penilaian">
        {{ $this->table }}
    </x-filament::section>

</x-filament-panels::page>
