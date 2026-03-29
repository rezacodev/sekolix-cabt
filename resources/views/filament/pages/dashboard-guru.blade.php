{{-- resources/views/filament/pages/dashboard-guru.blade.php --}}
<x-filament-panels::page>

{{-- ── Filter Sesi ──────────────────────────────────────────────────────────── --}}
<div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 px-6 py-4">
    <div class="flex flex-wrap items-center gap-4">
        <div>
            <p class="text-xs text-gray-500 dark:text-gray-400 font-semibold uppercase tracking-wide mb-1">Filter Sesi Ujian</p>
            <select
                wire:model.live="selectedSesiId"
                class="rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white shadow-sm focus:ring-primary-500 focus:border-primary-500 min-w-[18rem]">
                <option value="">— Pilih Sesi —</option>
                @foreach ($sesiOptions as $id => $label)
                    <option value="{{ $id }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        @if ($sesi)
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-5">
                <span class="font-medium text-gray-900 dark:text-white">{{ $sesi->package?->nama }}</span>
                &middot; {{ $sesi->waktu_mulai?->format('d M Y, H:i') }}
            </div>
        @endif
    </div>
</div>

@if (!$selectedSesiId || !$sesi)
    <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 px-6 py-12 text-center text-gray-400">
        Pilih sesi ujian untuk melihat data nilai rombel.
    </div>

@elseif ($rombelData->isEmpty())
    <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 px-6 py-12 text-center text-gray-400">
        Anda belum mengampu rombel apapun, atau rombel yang diampu tidak memiliki peserta yang terdaftar pada sesi ini.
    </div>

@else
    @foreach ($rombelData as $rd)
    {{-- ── Per-Rombel Card ───────────────────────────────────────────────────── --}}
    <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">

        {{-- Header --}}
        <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
            <x-heroicon-o-user-group class="w-5 h-5 text-primary-500"/>
            <div>
                <h3 class="font-bold text-gray-900 dark:text-white text-base">{{ $rd->rombel->nama }}</h3>
                @if ($rd->rombel->tahun_ajaran)
                    <p class="text-xs text-gray-400">{{ $rd->rombel->tahun_ajaran }}</p>
                @endif
            </div>

            {{-- Stats row --}}
            <div class="ml-auto flex items-center gap-6 text-sm">
                <div class="text-center">
                    <p class="text-xs text-gray-400 uppercase tracking-wide">Selesai</p>
                    <p class="font-bold text-gray-900 dark:text-white">{{ $rd->selesai }}/{{ $rd->total }}</p>
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-400 uppercase tracking-wide">Rata-rata</p>
                    <p class="font-bold text-primary-600 dark:text-primary-400">{{ $rd->rata_rata !== null ? number_format($rd->rata_rata, 1) : '—' }}</p>
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-400 uppercase tracking-wide">Tertinggi</p>
                    <p class="font-bold text-success-600 dark:text-success-400">{{ $rd->tertinggi !== null ? number_format($rd->tertinggi, 1) : '—' }}</p>
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-400 uppercase tracking-wide">Terendah</p>
                    <p class="font-bold text-danger-600 dark:text-danger-400">{{ $rd->terendah !== null ? number_format($rd->terendah, 1) : '—' }}</p>
                </div>

                {{-- Export button --}}
                <div class="flex">
                    <a href="{{ route('cabt.guru.rombel.export', [$sesi->id, $rd->rombel->id]) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-success-50 text-success-700 hover:bg-success-100 dark:bg-success-900/20 dark:text-success-400 ring-1 ring-success-200 dark:ring-success-800 transition-colors">
                        <x-heroicon-o-arrow-down-tray class="w-3.5 h-3.5"/>
                        Export Excel
                    </a>
                </div>
            </div>
        </div>

        {{-- Peserta Table --}}
        @if ($rd->peserta->isEmpty())
            <div class="px-6 py-8 text-center text-gray-400 text-sm">
                Tidak ada peserta di rombel ini.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left">
                            <th class="px-4 py-2.5 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 text-center w-10">No</th>
                            <th class="px-4 py-2.5 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Nama</th>
                            <th class="px-4 py-2.5 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">No. Peserta</th>
                            <th class="px-4 py-2.5 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 text-right">Nilai</th>
                            <th class="px-4 py-2.5 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 text-center">Benar</th>
                            <th class="px-4 py-2.5 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 text-center">Salah</th>
                            <th class="px-4 py-2.5 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 text-center">Kosong</th>
                            <th class="px-4 py-2.5 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 text-center">Attempt</th>
                            <th class="px-4 py-2.5 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Durasi</th>
                            <th class="px-4 py-2.5 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($rd->peserta as $p)
                        @php
                            $nilaiColor = 'text-gray-400';
                            if ($p->nilai !== null) {
                                if ($p->nilai >= 75)     $nilaiColor = 'text-success-600 dark:text-success-400';
                                elseif ($p->nilai >= 50) $nilaiColor = 'text-warning-600 dark:text-warning-400';
                                else                     $nilaiColor = 'text-danger-600 dark:text-danger-400';
                            }
                            $statusLabels = \App\Models\ExamAttempt::STATUS_LABELS;
                            $statusBadge  = match($p->status ?? '') {
                                \App\Models\ExamAttempt::STATUS_SELESAI        => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                \App\Models\ExamAttempt::STATUS_TIMEOUT        => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                \App\Models\ExamAttempt::STATUS_DISKUALIFIKASI => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                default                                         => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                            <td class="px-4 py-2.5 text-center text-gray-400">{{ $p->no }}</td>
                            <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-white">{{ $p->nama }}</td>
                            <td class="px-4 py-2.5 text-gray-500 font-mono text-xs">{{ $p->nomor }}</td>
                            <td class="px-4 py-2.5 text-right font-bold text-lg {{ $nilaiColor }}">
                                {{ $p->nilai !== null ? number_format((float)$p->nilai, 1) : '—' }}
                            </td>
                            <td class="px-4 py-2.5 text-center text-success-600 dark:text-success-400 font-medium">{{ $p->benar ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-center text-danger-600 dark:text-danger-400 font-medium">{{ $p->salah ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-center text-gray-400">{{ $p->kosong ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-center text-gray-500">{{ $p->attempt_ke > 0 ? $p->attempt_ke . '×' : '—' }}</td>
                            <td class="px-4 py-2.5 text-gray-500 text-sm">{{ $p->durasi ?? '—' }}</td>
                            <td class="px-4 py-2.5">
                                <div class="flex">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $statusBadge }}">
                                        {{ $p->status ? ($statusLabels[$p->status] ?? $p->status) : 'Belum Mengerjakan' }}
                                    </span>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    @endforeach
@endif

</x-filament-panels::page>
