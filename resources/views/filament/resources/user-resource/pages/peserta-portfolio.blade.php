<x-filament-panels::page>
    {{-- Stats --}}
    <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem;" class="mb-5">
        @php
            $statCards = [
                ['label' => 'Total Ujian', 'value' => $stats['total'],     'color' => 'text-gray-900 dark:text-white'],
                ['label' => 'Nilai Rata²', 'value' => $stats['rata']      !== null ? number_format($stats['rata'], 1)      : '—', 'color' => 'text-primary-600 dark:text-primary-400'],
                ['label' => 'Tertinggi',   'value' => $stats['tertinggi'] !== null ? number_format($stats['tertinggi'], 1) : '—', 'color' => 'text-success-600 dark:text-success-400'],
                ['label' => 'Terendah',    'value' => $stats['terendah']  !== null ? number_format($stats['terendah'], 1)  : '—', 'color' => 'text-danger-600 dark:text-danger-400'],
            ];
        @endphp
        @foreach ($statCards as $card)
        <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 px-5 py-4 text-center">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
            <p class="mt-1 text-3xl font-bold {{ $card['color'] }}">{{ $card['value'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Chart --}}
    @if ($chartData->count() > 1)
    <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 px-5 py-4 mb-5">
        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">Grafik Nilai</h2>
        <div style="height:200px">
            <canvas id="nilaiChartAdmin"></canvas>
        </div>
    </div>
    @endif

    {{-- Table --}}
    @if ($attempts->isEmpty())
    <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 px-5 py-10 text-center">
        <p class="text-gray-500 dark:text-gray-400">Peserta belum pernah mengikuti ujian.</p>
    </div>
    @else
    <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
        <table class="w-full text-sm table-fixed">
            <colgroup>
                <col style="width:28%">{{-- Nama Ujian --}}
                <col style="width:18%">{{-- Tanggal --}}
                <col style="width:8%"> {{-- Nilai --}}
                <col style="width:7%"> {{-- Benar --}}
                <col style="width:7%"> {{-- Salah --}}
                <col style="width:7%"> {{-- Kosong --}}
                <col style="width:14%">{{-- Status --}}
                <col style="width:11%">{{-- Aksi --}}
            </colgroup>
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                    <th class="px-4 py-3 text-left">Nama Ujian</th>
                    <th class="px-4 py-3 text-left">Tanggal</th>
                    <th class="px-4 py-3 text-center">Nilai</th>
                    <th class="px-4 py-3 text-center">Benar</th>
                    <th class="px-4 py-3 text-center">Salah</th>
                    <th class="px-4 py-3 text-center">Kosong</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($attempts as $attempt)
                @php
                    $nilai      = $attempt->nilai_akhir !== null ? number_format((float) $attempt->nilai_akhir, 1) : '—';
                    $nilaiColor = $attempt->nilai_akhir === null ? 'text-gray-400'
                        : ((float) $attempt->nilai_akhir >= 75 ? 'text-success-600 dark:text-success-400' : ((float) $attempt->nilai_akhir >= 60 ? 'text-warning-600 dark:text-warning-400' : 'text-danger-600 dark:text-danger-400'));
                    $statusLabel = \App\Models\ExamAttempt::STATUS_LABELS[$attempt->status] ?? $attempt->status;
                    $statusColor = match ($attempt->status) {
                        'selesai'        => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                        'timeout'        => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                        'diskualifikasi' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                        default          => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                    };
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100 truncate">
                        {{ $attempt->session->nama_sesi ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">
                        {{ $attempt->waktu_mulai?->format('d M Y, H:i') ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-center font-bold {{ $nilaiColor }}">{{ $nilai }}</td>
                    <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">{{ $attempt->jumlah_benar ?? '—' }}</td>
                    <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">{{ $attempt->jumlah_salah ?? '—' }}</td>
                    <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">{{ $attempt->jumlah_kosong ?? '—' }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                            {{ $statusLabel }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center text-gray-400 dark:text-gray-500 text-xs">—</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if ($chartData->count() > 1)
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js" defer></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const raw    = @json($chartData);
        const labels = raw.map(d => d.label);
        const values = raw.map(d => d.nilai);
        new Chart(document.getElementById('nilaiChartAdmin'), {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Nilai',
                    data: values,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99,102,241,0.12)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: '#6366f1',
                    spanGaps: false,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { min: 0, max: 100, ticks: { stepSize: 25, font: { size: 11 } } },
                    x: { ticks: { font: { size: 10 }, maxRotation: 30 } },
                },
                plugins: { legend: { display: false } },
            },
        });
    });
    </script>
    @endif
</x-filament-panels::page>
