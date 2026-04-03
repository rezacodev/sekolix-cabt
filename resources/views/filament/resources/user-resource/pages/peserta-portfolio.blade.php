<x-filament-panels::page>
    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        @php
            $statCards = [
                ['label' => 'Total Ujian',  'value' => $stats['total']],
                ['label' => 'Nilai Rata²',  'value' => $stats['rata']      !== null ? number_format($stats['rata'], 1)      : '—'],
                ['label' => 'Tertinggi',    'value' => $stats['tertinggi'] !== null ? number_format($stats['tertinggi'], 1) : '—'],
                ['label' => 'Terendah',     'value' => $stats['terendah']  !== null ? number_format($stats['terendah'], 1)  : '—'],
            ];
        @endphp
        @foreach ($statCards as $card)
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center shadow-sm">
            <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ $card['value'] }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $card['label'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Chart --}}
    @if ($chartData->count() > 1)
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 mb-6 shadow-sm">
        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">Grafik Nilai</h2>
        <div style="height:200px">
            <canvas id="nilaiChartAdmin"></canvas>
        </div>
    </div>
    @endif

    {{-- Table --}}
    @if ($attempts->isEmpty())
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-10 text-center shadow-sm">
        <p class="text-gray-500 dark:text-gray-400">Peserta belum pernah mengikuti ujian.</p>
    </div>
    @else
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        <th class="px-5 py-3 text-left">Nama Ujian</th>
                        <th class="px-5 py-3 text-left">Tanggal</th>
                        <th class="px-5 py-3 text-center">Nilai</th>
                        <th class="px-5 py-3 text-center">Benar</th>
                        <th class="px-5 py-3 text-center">Salah</th>
                        <th class="px-5 py-3 text-center">Kosong</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($attempts as $attempt)
                    @php
                        $nilai      = $attempt->nilai_akhir !== null ? number_format((float) $attempt->nilai_akhir, 1) : '—';
                        $nilaiColor = $attempt->nilai_akhir === null ? 'text-gray-400'
                            : ((float) $attempt->nilai_akhir >= 75 ? 'text-green-600' : ((float) $attempt->nilai_akhir >= 60 ? 'text-yellow-600' : 'text-red-600'));
                        $statusLabel = \App\Models\ExamAttempt::STATUS_LABELS[$attempt->status] ?? $attempt->status;
                        $statusColor = match ($attempt->status) {
                            'selesai'       => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                            'timeout'       => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                            'diskualifikasi'=> 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                            default         => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <td class="px-5 py-3 font-medium text-gray-900 dark:text-gray-100 max-w-xs truncate">
                            {{ $attempt->session->nama_sesi ?? '—' }}
                        </td>
                        <td class="px-5 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">
                            {{ $attempt->waktu_mulai?->format('d M Y, H:i') ?? '—' }}
                        </td>
                        <td class="px-5 py-3 text-center font-bold {{ $nilaiColor }}">{{ $nilai }}</td>
                        <td class="px-5 py-3 text-center text-gray-600 dark:text-gray-400">{{ $attempt->jumlah_benar ?? '—' }}</td>
                        <td class="px-5 py-3 text-center text-gray-600 dark:text-gray-400">{{ $attempt->jumlah_salah ?? '—' }}</td>
                        <td class="px-5 py-3 text-center text-gray-600 dark:text-gray-400">{{ $attempt->jumlah_kosong ?? '—' }}</td>
                        <td class="px-5 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-center text-gray-400 dark:text-gray-500 text-xs">—</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
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
