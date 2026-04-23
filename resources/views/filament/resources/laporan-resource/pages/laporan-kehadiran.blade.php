{{-- resources/views/filament/resources/laporan-resource/pages/laporan-kehadiran.blade.php --}}
<x-filament-panels::page>

@php
    $statusConfig = [
        \App\Models\ExamSessionParticipant::STATUS_SELESAI        => ['label' => 'Selesai',               'badge' => 'success', 'text' => 'text-success-600 dark:text-success-400'],
        \App\Models\ExamSessionParticipant::STATUS_SEDANG         => ['label' => 'Sedang Mengerjakan',    'badge' => 'warning', 'text' => 'text-warning-600 dark:text-warning-400'],
        \App\Models\ExamSessionParticipant::STATUS_BELUM          => ['label' => 'Belum Mulai',           'badge' => 'gray',    'text' => 'text-gray-500 dark:text-gray-400'],
        \App\Models\ExamSessionParticipant::STATUS_DISKUALIFIKASI => ['label' => 'Diskualifikasi',        'badge' => 'danger',  'text' => 'text-danger-600 dark:text-danger-400'],
    ];

    $list = $kehadiran['list'];
@endphp

{{-- ── Kartu Statistik ──────────────────────────────────────────────────────── --}}
<div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 1rem;">
    @foreach ([
        ['label' => 'Total Terdaftar', 'value' => $kehadiran['total'],          'color' => '#111827'],
        ['label' => 'Selesai',          'value' => $kehadiran['selesai'],         'color' => '#16a34a'],
        ['label' => 'Sedang',           'value' => $kehadiran['sedang'],          'color' => '#d97706'],
        ['label' => 'Belum Mulai',      'value' => $kehadiran['belum'],           'color' => '#6b7280'],
        ['label' => 'Diskualifikasi',   'value' => $kehadiran['diskualifikasi'],  'color' => '#dc2626'],
    ] as $stat)
    <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4 text-center">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
        <p class="mt-1 text-3xl font-bold" style="color:{{ $stat['color'] }};">{{ $stat['value'] }}</p>
    </div>
    @endforeach
</div>

{{-- ── Progress Bar ─────────────────────────────────────────────────────────── --}}
@if ($kehadiran['total'] > 0)
<div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
    @php
        $total       = $kehadiran['total'];
        $pctSelesai  = round($kehadiran['selesai']        / $total * 100);
        $pctSedang   = round($kehadiran['sedang']         / $total * 100);
        $pctBelum    = round($kehadiran['belum']          / $total * 100);
        $pctDisq     = round($kehadiran['diskualifikasi'] / $total * 100);
    @endphp
    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4">Distribusi Kehadiran</p>
    <div class="flex h-6 rounded-full overflow-hidden">
        @if ($pctSelesai > 0)
            <div style="background:#22c55e;width:{{ $pctSelesai }}%;" title="Selesai {{ $pctSelesai }}%"></div>
        @endif
        @if ($pctSedang > 0)
            <div style="background:#f59e0b;width:{{ $pctSedang }}%;" title="Sedang {{ $pctSedang }}%"></div>
        @endif
        @if ($pctDisq > 0)
            <div class="bg-danger-500" @style(['width: ' . $pctDisq . '%']) title="Diskualifikasi {{ $pctDisq }}%"></div>
        @endif
        @if ($pctBelum > 0)
            <div class="bg-gray-200 dark:bg-gray-700" @style(['width: ' . $pctBelum . '%']) title="Belum {{ $pctBelum }}%"></div>
        @endif
    </div>
    <div class="flex gap-6 mt-3 text-xs text-gray-500">
        <span><span class="inline-block w-2.5 h-2.5 rounded-full mr-1" style="background:#22c55e;"></span>Selesai {{ $pctSelesai }}%</span>
        <span><span class="inline-block w-2.5 h-2.5 rounded-full mr-1" style="background:#f59e0b;"></span>Sedang {{ $pctSedang }}%</span>
        <span><span class="inline-block w-2.5 h-2.5 rounded-full bg-danger-500 mr-1"></span>Diskualifikasi {{ $pctDisq }}%</span>
        <span><span class="inline-block w-2.5 h-2.5 rounded-full bg-gray-300 mr-1"></span>Belum Mulai {{ $pctBelum }}%</span>
    </div>
</div>
@endif

{{-- ── Tabel Peserta ────────────────────────────────────────────────────────── --}}
<div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
    <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <x-heroicon-o-users class="w-5 h-5 text-gray-500 dark:text-gray-400"/>
        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Daftar Peserta</h3>
        <span class="ml-auto text-sm text-gray-500">{{ $list->count() }} peserta</span>
    </div>

    @if ($list->isEmpty())
        <div class="px-6 py-8 text-center text-gray-400">
            Belum ada peserta yang terdaftar di sesi ini.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800 text-left">
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 text-center w-10">No</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Nama Peserta</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">No. Peserta</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Rombel</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($list as $i => $p)
                    @php $cfg = $statusConfig[$p->status] ?? ['label' => $p->status, 'badge' => 'gray', 'text' => '']; @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="px-4 py-3 text-center text-gray-500">{{ $i + 1 }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $p->user?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $p->user?->nomor_peserta ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $p->user?->rombel?->nama ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex">
                                <x-filament::badge :color="$cfg['badge']" size="sm">{{ $cfg['label'] }}</x-filament::badge>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

</x-filament-panels::page>
