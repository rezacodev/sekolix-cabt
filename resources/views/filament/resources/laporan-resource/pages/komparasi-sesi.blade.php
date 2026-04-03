{{-- resources/views/filament/resources/laporan-resource/pages/komparasi-sesi.blade.php --}}
<x-filament-panels::page>

{{-- ── Filter Form ──────────────────────────────────────────────────────────── --}}
<div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
    <div class="flex items-center gap-3 mb-4">
        <x-heroicon-o-adjustments-horizontal class="w-5 h-5 text-gray-500 dark:text-gray-400"/>
        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Pilih Sesi untuk Dibandingkan</h3>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:1rem;align-items:flex-end;">
        <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Sesi A (Pertama)</label>
            <select wire:model.live="sesi_a"
                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:outline-none">
                <option value="">— Pilih Sesi A —</option>
                @foreach ($sesiOptions as $id => $nama)
                    <option value="{{ $id }}" @selected($sesi_a == $id)>{{ $nama }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Sesi B (Pembanding)</label>
            <select wire:model.live="sesi_b"
                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:outline-none">
                <option value="">— Pilih Sesi B —</option>
                @foreach ($sesiOptions as $id => $nama)
                    <option value="{{ $id }}" @selected($sesi_b == $id)>{{ $nama }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Mode</label>
            <select wire:model.live="mode"
                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:outline-none">
                <option value="peserta" @selected($mode === 'peserta')>Per Peserta</option>
            </select>
        </div>
    </div>
</div>

@if ($sesi_a && $sesi_b && $result !== null)

    @if ($sesi_a === $sesi_b)
        <div class="rounded-xl bg-warning-50 dark:bg-warning-900/20 ring-1 ring-warning-200 dark:ring-warning-800 p-4 text-warning-800 dark:text-warning-300 text-sm">
            Pilih dua sesi yang berbeda untuk membandingkan.
        </div>
    @else

    {{-- ── Statistik Ringkasan ──────────────────────────────────────────────── --}}
    <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:1rem;">
        @foreach ([
            ['label' => 'Total Peserta', 'value' => $statistik['total'],        'color' => 'text-gray-900 dark:text-white'],
            ['label' => 'Data Valid',     'value' => $statistik['valid'],        'color' => 'text-gray-500 dark:text-gray-400'],
            ['label' => 'Rata Selisih',   'value' => ($statistik['rata_selisih'] > 0 ? '+' : '') . number_format($statistik['rata_selisih'], 2), 'color' => $statistik['rata_selisih'] >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400'],
            ['label' => 'Peserta Naik',   'value' => $statistik['naik'] . ' (' . $statistik['persen_naik'] . '%)', 'color' => 'text-success-600 dark:text-success-400'],
            ['label' => 'Peserta Turun',  'value' => $statistik['turun'] . ' (' . $statistik['persen_turun'] . '%)', 'color' => 'text-danger-600 dark:text-danger-400'],
            ['label' => 'Tetap',          'value' => $statistik['tetap'] . ' (' . $statistik['persen_tetap'] . '%)', 'color' => 'text-gray-500 dark:text-gray-400'],
        ] as $stat)
        <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4 text-center">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
            <p class="mt-1 text-xl font-bold {{ $stat['color'] }}">{{ $stat['value'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- ── Scatter Plot ─────────────────────────────────────────────────────── --}}
    <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
        <div class="flex items-center gap-3 mb-4">
            <x-heroicon-o-chart-bar-square class="w-5 h-5 text-gray-500 dark:text-gray-400"/>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Grafik Perbandingan Nilai</h3>
            <span class="text-xs text-gray-400 ml-2">Nilai {{ $sesiA?->nama_sesi }} (X) vs {{ $sesiB?->nama_sesi }} (Y)</span>
        </div>
        <div style="position:relative;height:280px;">
            <canvas id="scatterChart"></canvas>
        </div>
    </div>

    {{-- ── Tabel Komparasi ──────────────────────────────────────────────────── --}}
    <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
        <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <x-heroicon-o-table-cells class="w-5 h-5 text-gray-500 dark:text-gray-400"/>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Tabel Perbandingan</h3>
            <span class="ml-auto text-sm text-gray-500">{{ $result->count() }} peserta</span>
        </div>

        @if ($result->isEmpty())
            <div class="px-6 py-10 text-center text-gray-400">Tidak ada data yang dapat dibandingkan.</div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800 text-left">
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 text-center w-10">No</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Nama Peserta</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Rombel</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 text-right">{{ $sesiA?->nama_sesi ?? 'Sesi A' }}</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 text-right">{{ $sesiB?->nama_sesi ?? 'Sesi B' }}</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 text-right">Selisih</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 text-center">Tren</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($result as $i => $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="px-4 py-3 text-center text-gray-500">{{ $i + 1 }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $row->nama }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $row->rombel_nama }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">
                            {{ $row->nilai_a !== null ? number_format($row->nilai_a, 1) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">
                            {{ $row->nilai_b !== null ? number_format($row->nilai_b, 1) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right font-bold
                            @if($row->selisih === null) text-gray-400
                            @elseif($row->selisih > 0) text-success-600 dark:text-success-400
                            @elseif($row->selisih < 0) text-danger-600 dark:text-danger-400
                            @else text-gray-500
                            @endif">
                            @if($row->selisih !== null)
                                {{ $row->selisih > 0 ? '+' : '' }}{{ number_format($row->selisih, 1) }}
                            @else —
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-center">
                                @if($row->tren === 'naik')
                                    <x-filament::badge color="success" size="sm">↑ Naik</x-filament::badge>
                                @elseif($row->tren === 'turun')
                                    <x-filament::badge color="danger" size="sm">↓ Turun</x-filament::badge>
                                @elseif($row->tren === 'tetap')
                                    <x-filament::badge color="gray" size="sm">→ Tetap</x-filament::badge>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    @endif
@else
    <div class="rounded-xl bg-gray-50 dark:bg-gray-800/50 ring-1 ring-gray-200 dark:ring-gray-700 p-8 text-center text-gray-400">
        <x-heroicon-o-scale class="w-12 h-12 mx-auto mb-3 opacity-40"/>
        <p class="text-sm">Pilih Sesi A dan Sesi B di atas untuk mulai membandingkan hasil ujian.</p>
    </div>
@endif

</x-filament-panels::page>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const scatter = @json($scatterPoints ?? []);
    if (!scatter.length) return;

    const isDark    = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#9ca3af' : '#6b7280';
    const gridColor = isDark ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.07)';

    const ctx = document.getElementById('scatterChart');
    if (!ctx) return;

    // Diagonal reference line (Y=X)
    const allVals = scatter.flatMap(p => [p.x, p.y]);
    const lo = Math.min(...allVals, 0);
    const hi = Math.max(...allVals, 100);

    const diagonalPlugin = {
        id: 'diagonalLine',
        afterDraw(chart) {
            const { ctx: c, scales: { x, y } } = chart;
            c.save();
            c.setLineDash([6, 4]);
            c.strokeStyle = isDark ? 'rgba(255,255,255,0.15)' : 'rgba(0,0,0,0.12)';
            c.lineWidth = 1.5;
            c.beginPath();
            c.moveTo(x.getPixelForValue(lo), y.getPixelForValue(lo));
            c.lineTo(x.getPixelForValue(hi), y.getPixelForValue(hi));
            c.stroke();
            c.restore();
        }
    };

    new Chart(ctx, {
        type: 'scatter',
        plugins: [diagonalPlugin],
        data: {
            datasets: [{
                label: 'Peserta',
                data: scatter,
                backgroundColor: isDark ? 'rgba(99,102,241,0.7)' : 'rgba(99,102,241,0.65)',
                pointRadius: 5,
                pointHoverRadius: 7,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (item) => `${item.raw.label}: (${item.raw.x}, ${item.raw.y})`,
                    }
                },
            },
            scales: {
                x: {
                    min: 0, max: 100,
                    title: { display: true, text: 'Nilai Sesi A', color: textColor },
                    grid: { color: gridColor },
                    ticks: { color: textColor },
                },
                y: {
                    min: 0, max: 100,
                    title: { display: true, text: 'Nilai Sesi B', color: textColor },
                    grid: { color: gridColor },
                    ticks: { color: textColor },
                },
            },
        },
    });
})();
</script>
@endpush
