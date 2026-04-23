{{-- resources/views/filament/resources/laporan-resource/pages/laporan-nilai.blade.php --}}
<x-filament-panels::page>

@php
    $statusBadgeColor = [
        \App\Models\ExamAttempt::STATUS_SELESAI         => 'success',
        \App\Models\ExamAttempt::STATUS_TIMEOUT         => 'warning',
        \App\Models\ExamAttempt::STATUS_DISKUALIFIKASI  => 'danger',
    ];
@endphp

{{-- ── Statistik Ringkasan ──────────────────────────────────────────────────── --}}
<div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 1rem;">
    @foreach ([
        ['label' => 'Total Peserta',   'value' => $statistik['total_peserta'],             'color' => '#111827'],
        ['label' => 'Rata-rata',        'value' => number_format($statistik['rata_rata'], 1), 'color' => '#2563eb'],
        ['label' => 'Tertinggi',        'value' => number_format($statistik['nilai_tertinggi'], 1), 'color' => '#16a34a'],
        ['label' => 'Terendah',         'value' => number_format($statistik['nilai_terendah'], 1), 'color' => '#dc2626'],
        ['label' => 'Median',           'value' => number_format($statistik['median'], 1),  'color' => '#0891b2'],
    ] as $stat)
    <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4 text-center">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
        <p class="mt-1 text-3xl font-bold" style="color:{{ $stat['color'] }};">{{ $stat['value'] }}</p>
    </div>
    @endforeach
</div>

{{-- ── Grafik Distribusi Nilai ─────────────────────────────────────────────── --}}
<div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
    <div class="flex items-center gap-3 mb-4">
        <x-heroicon-o-chart-bar class="w-5 h-5 text-gray-500 dark:text-gray-400"/>
        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Distribusi Nilai</h3>
    </div>
    <div style="position:relative;height:220px;">
        <canvas id="distribusiChart"></canvas>
    </div>
</div>

{{-- ── Tabel Rekap Nilai ────────────────────────────────────────────────────── --}}
<div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
    <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <x-heroicon-o-table-cells class="w-5 h-5 text-gray-500 dark:text-gray-400"/>
        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Rekap Nilai Peserta</h3>
        <span class="ml-auto text-sm text-gray-500">{{ $rekap->count() }} peserta</span>
    </div>

    @if ($rekap->isEmpty())
        <div class="px-6 py-8 text-center text-gray-400">
            Belum ada data nilai untuk sesi ini.
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
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 text-right">Nilai</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 text-center">Benar</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 text-center">Salah</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 text-center">Kosong</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 text-center">Attempt</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Durasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($rekap as $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="px-4 py-3 text-center text-gray-500">{{ $row->no }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $row->nama }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $row->nomor_peserta }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $row->rombel_nama }}</td>
                        @php
                            $nilaiStyle = $row->nilai_akhir === null ? 'color:#9ca3af;' : ($row->nilai_akhir >= 75 ? 'color:#16a34a;' : ($row->nilai_akhir >= 50 ? 'color:#d97706;' : 'color:#dc2626;'));
                        @endphp
                        <td class="px-4 py-3 text-right font-bold text-lg" style="{{ $nilaiStyle }}">
                            {{ $row->nilai_akhir !== null ? number_format((float)$row->nilai_akhir, 1) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-center font-medium" style="color:#16a34a;">{{ $row->jumlah_benar }}</td>
                        <td class="px-4 py-3 text-center text-danger-600 dark:text-danger-400 font-medium">{{ $row->jumlah_salah }}</td>
                        <td class="px-4 py-3 text-center text-gray-400">{{ $row->jumlah_kosong }}</td>
                        <td class="px-4 py-3 text-center text-gray-500">{{ $row->attempt_ke }}×</td>
                        <td class="px-4 py-3">
                            <div class="flex">
                                <x-filament::badge :color="$statusBadgeColor[$row->status] ?? 'gray'" size="sm">
                                    {{ \App\Models\ExamAttempt::STATUS_LABELS[$row->status] ?? $row->status }}
                                </x-filament::badge>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-sm">
                            @if($row->durasi_detik !== null)
                                {{ intdiv($row->durasi_detik, 60) }}m {{ $row->durasi_detik % 60 }}d
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

</x-filament-panels::page>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const distribusi = @json($distribusiData);
    const statistik  = @json($statistik);

    const labels = distribusi.map(b => b.range);
    const counts = distribusi.map(b => b.count);
    const maxCount = Math.max(...counts, 1);

    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#9ca3af' : '#6b7280';
    const gridColor = isDark ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.07)';
    const barColor  = isDark ? 'rgba(99,102,241,0.7)' : 'rgba(99,102,241,0.65)';

    function binMidpoint(range) {
        const [lo, hi] = range.split('–').map(Number);
        return (lo + hi) / 2;
    }

    function statToAnnotation(value, label, color) {
        const binRanges = distribusi.map(b => b.range);
        // Determine x index: find which bin the value falls in
        for (let i = 0; i < distribusi.length; i++) {
            if (value >= distribusi[i].min && value <= distribusi[i].max) {
                return { x: binRanges[i], color, label, exactValue: value };
            }
        }
        return null;
    }

    const stats = [
        { value: statistik.nilai_terendah, label: 'Min', color: '#ef4444' },
        { value: statistik.median,          label: 'Med', color: '#f59e0b' },
        { value: statistik.rata_rata,        label: 'Avg', color: '#6366f1' },
        { value: statistik.nilai_tertinggi, label: 'Max', color: '#22c55e' },
    ].filter(s => s.value > 0);

    const verticalLinesPlugin = {
        id: 'verticalLines',
        afterDraw(chart) {
            const ctx   = chart.ctx;
            const xAxis = chart.scales.x;
            const yAxis = chart.scales.y;

            stats.forEach(stat => {
                const bin = distribusi.find(b => stat.value >= b.min && stat.value <= b.max);
                if (!bin) return;
                const idx = distribusi.indexOf(bin);
                const x   = xAxis.getPixelForTick(idx);
                const yTop = yAxis.top;
                const yBot = yAxis.bottom;

                ctx.save();
                ctx.setLineDash([5, 4]);
                ctx.strokeStyle = stat.color;
                ctx.lineWidth   = 1.5;
                ctx.globalAlpha = 0.85;
                ctx.beginPath();
                ctx.moveTo(x, yTop);
                ctx.lineTo(x, yBot);
                ctx.stroke();

                ctx.setLineDash([]);
                ctx.globalAlpha = 1;
                ctx.fillStyle   = stat.color;
                ctx.font        = 'bold 11px sans-serif';
                ctx.textAlign   = 'center';
                ctx.fillText(stat.label, x, yTop - 4);
                ctx.restore();
            });
        }
    };

    const ctx = document.getElementById('distribusiChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            plugins: [verticalLinesPlugin],
            data: {
                labels,
                datasets: [{
                    label: 'Jumlah Peserta',
                    data: counts,
                    backgroundColor: barColor,
                    borderColor: isDark ? 'rgba(99,102,241,0.9)' : 'rgba(99,102,241,0.9)',
                    borderWidth: 1,
                    borderRadius: 4,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { top: 20 } },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: (items) => 'Rentang nilai: ' + items[0].label,
                            label: (item) => ' ' + item.raw + ' peserta',
                        }
                    },
                },
                scales: {
                    x: {
                        grid: { color: gridColor },
                        ticks: { color: textColor, font: { size: 11 } },
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: { color: textColor, precision: 0 },
                    },
                },
            },
        });
    }
})();
</script>
@endpush
