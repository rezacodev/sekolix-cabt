<x-peserta-layout>
    <x-slot name="title">Hasil Ujian — {{ $attempt->session->nama_sesi }}</x-slot>

    <div class="max-w-3xl mx-auto">
        <div class="mb-6">
            <a href="{{ route('peserta.dashboard') }}" class="text-sm text-indigo-600 hover:underline">← Dashboard</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-2">Hasil Ujian</h1>
            <p class="text-gray-500">{{ $attempt->session->nama_sesi }} · {{ $attempt->session->package->nama }}</p>
        </div>

        {{-- Score card --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-500 px-8 py-10 text-center">
                <p class="text-indigo-200 text-sm mb-2 uppercase tracking-wider">Nilai Akhir</p>
                <p class="text-7xl font-bold text-white">
                    {{ number_format($attempt->nilai_akhir ?? 0, 2) }}
                </p>
                <p class="text-indigo-200 mt-2 text-sm">
                    Status:
                    <span class="font-semibold text-white capitalize">
                        @if ($attempt->status === 'selesai') Selesai
                        @elseif ($attempt->status === 'timeout') Timeout
                        @else {{ ucfirst($attempt->status) }}
                        @endif
                    </span>
                </p>
            </div>

            <div class="grid grid-cols-3 divide-x divide-gray-100">
                <div class="px-6 py-5 text-center">
                    <p class="text-2xl font-bold text-green-600">{{ $attempt->jumlah_benar }}</p>
                    <p class="text-sm text-gray-500 mt-1">Benar</p>
                </div>
                <div class="px-6 py-5 text-center">
                    <p class="text-2xl font-bold text-red-500">{{ $attempt->jumlah_salah }}</p>
                    <p class="text-sm text-gray-500 mt-1">Salah</p>
                </div>
                <div class="px-6 py-5 text-center">
                    <p class="text-2xl font-bold text-gray-400">{{ $attempt->jumlah_kosong }}</p>
                    <p class="text-sm text-gray-500 mt-1">Tidak Dijawab</p>
                </div>
            </div>
        </div>

        {{-- Detail waktu --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <h2 class="font-semibold text-gray-800 mb-4">Detail Pengerjaan</h2>
            <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div>
                    <dt class="text-gray-500">Mulai</dt>
                    <dd class="font-medium text-gray-900">
                        {{ $attempt->waktu_mulai?->format('d M Y, H:i:s') }}
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500">Selesai</dt>
                    <dd class="font-medium text-gray-900">
                        {{ $attempt->waktu_selesai?->format('d M Y, H:i:s') ?? '-' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500">Durasi Pengerjaan</dt>
                    <dd class="font-medium text-gray-900">
                        @if ($attempt->waktu_selesai)
                            {{ (int) $attempt->waktu_mulai->diffInMinutes($attempt->waktu_selesai) }} menit
                        @else
                            -
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500">Attempt ke-</dt>
                    <dd class="font-medium text-gray-900">{{ $attempt->attempt_ke }}</dd>
                </div>
            </dl>
        </div>

        {{-- Ranking card (only shown when show_ranking_hasil is enabled) --}}
        @if ($ranking ?? null)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <h2 class="font-semibold text-gray-800 mb-4">Ranking Kamu</h2>
            <div class="text-center">
                <p class="text-5xl font-bold text-indigo-600">#{{ $ranking['rank'] }}</p>
                <p class="text-sm text-gray-500 mt-2">dari {{ $ranking['total'] }} peserta</p>
            </div>
        </div>
        @endif

        {{-- Aksi --}}
        <div class="flex flex-wrap gap-3">
            @if ($attempt->session->package->tampilkan_review)
                <a href="{{ route('ujian.review', $attempt->id) }}"
                    class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold
                        px-5 py-2.5 rounded-lg transition-colors text-sm">
                    Review Jawaban
                </a>
            @endif
            <a href="{{ route('peserta.dashboard') }}"
                class="inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 font-semibold
                    border border-gray-200 px-5 py-2.5 rounded-lg transition-colors text-sm">
                Kembali ke Dashboard
            </a>
        </div>
    </div>
</x-peserta-layout>
