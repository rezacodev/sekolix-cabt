<x-peserta-layout>
    <x-slot name="title">Riwayat Ujian — {{ config('app.name') }}</x-slot>

    {{-- Page Header --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Riwayat Ujian Saya</h1>
            <p class="text-sm text-gray-500 mt-0.5">Rekap semua ujian yang pernah Anda ikuti</p>
        </div>
        <a href="{{ route('peserta.dashboard') }}"
            class="inline-flex items-center gap-1.5 text-sm text-indigo-600 hover:text-indigo-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Kembali
        </a>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        @php
            $statCards = [
                ['label' => 'Total Ujian',  'value' => $stats['total'],    'color' => 'indigo'],
                ['label' => 'Nilai Rata²',  'value' => $stats['rata'] !== null    ? number_format($stats['rata'], 1)      : '—', 'color' => 'blue'],
                ['label' => 'Tertinggi',    'value' => $stats['tertinggi'] !== null ? number_format($stats['tertinggi'], 1) : '—', 'color' => 'green'],
                ['label' => 'Terendah',     'value' => $stats['terendah'] !== null  ? number_format($stats['terendah'], 1)  : '—', 'color' => 'orange'],
            ];
        @endphp
        @foreach ($statCards as $card)
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 text-center">
            <p class="text-2xl font-bold text-{{ $card['color'] }}-600">{{ $card['value'] }}</p>
            <p class="text-xs text-gray-500 mt-0.5">{{ $card['label'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Chart --}}
    @if ($chartData->count() > 1)
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Grafik Nilai</h2>
        <div style="height:200px">
            <canvas id="nilaiChart"></canvas>
        </div>
    </div>
    @endif

    {{-- Table --}}
    @if ($attempts->isEmpty())
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center">
        <p class="text-gray-500 font-medium">Belum ada riwayat ujian.</p>
    </div>
    @else
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        <th class="px-5 py-3 text-left">Nama Ujian</th>
                        <th class="px-5 py-3 text-left">Tanggal</th>
                        <th class="px-5 py-3 text-center">Nilai</th>
                        <th class="px-5 py-3 text-center">Benar</th>
                        <th class="px-5 py-3 text-center">Salah</th>
                        <th class="px-5 py-3 text-center">Kosong</th>
                        <th class="px-5 py-3 text-center">Durasi</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($attempts as $attempt)
                    @php
                        $durasi = $attempt->waktu_mulai && $attempt->waktu_selesai
                            ? $attempt->waktu_mulai->diffInMinutes($attempt->waktu_selesai) . ' mnt'
                            : '—';
                        $nilai  = $attempt->nilai_akhir !== null ? number_format((float) $attempt->nilai_akhir, 1) : '—';
                        $nilaiColor = $attempt->nilai_akhir === null ? 'text-gray-400'
                            : ((float) $attempt->nilai_akhir >= 75 ? 'text-green-600' : ((float) $attempt->nilai_akhir >= 60 ? 'text-yellow-600' : 'text-red-600'));
                        $statusLabel = \App\Models\ExamAttempt::STATUS_LABELS[$attempt->status] ?? $attempt->status;
                        $statusColor = match ($attempt->status) {
                            'selesai'       => 'bg-green-100 text-green-700',
                            'timeout'       => 'bg-yellow-100 text-yellow-700',
                            'diskualifikasi'=> 'bg-red-100 text-red-700',
                            default         => 'bg-gray-100 text-gray-600',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3 font-medium text-gray-900 max-w-xs truncate">
                            {{ $attempt->session->nama_sesi ?? '—' }}
                        </td>
                        <td class="px-5 py-3 text-gray-500 whitespace-nowrap">
                            {{ $attempt->waktu_mulai?->format('d M Y, H:i') ?? '—' }}
                        </td>
                        <td class="px-5 py-3 text-center font-bold {{ $nilaiColor }}">{{ $nilai }}</td>
                        <td class="px-5 py-3 text-center text-gray-600">{{ $attempt->jumlah_benar ?? '—' }}</td>
                        <td class="px-5 py-3 text-center text-gray-600">{{ $attempt->jumlah_salah ?? '—' }}</td>
                        <td class="px-5 py-3 text-center text-gray-600">{{ $attempt->jumlah_kosong ?? '—' }}</td>
                        <td class="px-5 py-3 text-center text-gray-500">{{ $durasi }}</td>
                        <td class="px-5 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-center">
                            @if ($attempt->session?->package?->tampilkan_review && $attempt->status === 'selesai')
                            <a href="{{ route('ujian.review', $attempt->id) }}"
                                class="text-xs text-indigo-600 hover:text-indigo-800 font-medium transition-colors">
                                Review
                            </a>
                            @else
                            <span class="text-xs text-gray-300">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if ($chartData->count() > 1)
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script>
    (() => {
        const raw   = @json($chartData);
        const labels = raw.map(d => d.label + (d.date ? '\n' + d.date : ''));
        const values = raw.map(d => d.nilai);

        new Chart(document.getElementById('nilaiChart'), {
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
                    y: {
                        min: 0, max: 100,
                        ticks: { stepSize: 25, font: { size: 11 } },
                        grid: { color: '#f3f4f6' },
                    },
                    x: { ticks: { font: { size: 10 }, maxRotation: 30 } },
                },
                plugins: { legend: { display: false } },
            },
        });
    })();
    </script>
    @endpush
    @endif
</x-peserta-layout>
