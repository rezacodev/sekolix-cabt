<x-peserta-layout>
    <x-slot name="title">Review Jawaban — {{ $attempt->session->nama_sesi }}</x-slot>

    <div class="max-w-3xl mx-auto">
        <div class="mb-6 flex items-center justify-between gap-4">
            <div>
                <a href="{{ route('ujian.hasil', $attempt->id) }}" class="text-sm text-indigo-600 hover:underline">
                    ← Hasil Ujian
                </a>
                <h1 class="text-2xl font-bold text-gray-900 mt-2">Review Jawaban</h1>
                <p class="text-gray-500">{{ $attempt->session->nama_sesi }}</p>
            </div>
            <div class="text-right shrink-0">
                <p class="text-sm text-gray-500">Nilai</p>
                <p class="text-3xl font-bold text-indigo-600">{{ number_format($attempt->nilai_akhir ?? 0, 2) }}</p>
            </div>
        </div>

        <div class="space-y-6">
            @foreach ($soalList as $aq)
                @php
                    $q = $aq->question;
                    $isBenar = $aq->is_correct;
                    $isDijawab = $aq->isDijawab();
                    $borderClass = $isBenar === true ? 'border-green-300' : ($isBenar === false ? 'border-red-300' : 'border-gray-200');
                    $bgClass = $isBenar === true ? 'bg-green-50' : ($isBenar === false ? 'bg-red-50' : 'bg-white');
                @endphp
                <div class="rounded-xl border {{ $borderClass }} {{ $bgClass }} overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-3 border-b {{ $borderClass }}">
                        <span class="font-semibold text-gray-700">Soal {{ $loop->iteration }}</span>
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-gray-500 uppercase">{{ $q->tipe }}</span>
                            @if ($isBenar === true)
                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-green-700 bg-green-100 px-2 py-0.5 rounded-full">
                                    ✓ Benar
                                </span>
                            @elseif ($isBenar === false)
                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-red-700 bg-red-100 px-2 py-0.5 rounded-full">
                                    ✗ Salah
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-gray-600 bg-gray-100 px-2 py-0.5 rounded-full">
                                    — Belum Dinilai
                                </span>
                            @endif
                            @if ($aq->nilai_perolehan !== null)
                                <span class="text-sm font-bold text-gray-700">+{{ number_format($aq->nilai_perolehan, 2) }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="p-5">
                        {{-- Pertanyaan --}}
                        <div class="prose prose-sm max-w-none mb-4 text-gray-800">
                            {!! $q->teks_soal !!}
                        </div>

                        {{-- Jawaban peserta --}}
                        <div class="mb-3">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Jawaban Anda:</p>
                            @if (! $isDijawab)
                                <p class="text-gray-400 italic text-sm">Tidak dijawab</p>
                            @elseif ($aq->jawaban_file)
                                <a href="{{ route('ujian.file.uraian', ['attemptId' => $attempt->id, 'filename' => basename($aq->jawaban_file)]) }}" target="_blank"
                                    class="text-sm text-indigo-600 hover:underline">
                                    Lihat file yang diunggah
                                </a>
                            @elseif (in_array($q->tipe, ['PG', 'PG_BOBOT']))
                                @php $selectedOpt = $q->options->firstWhere('id', $aq->jawaban_peserta); @endphp
                                @if ($selectedOpt)
                                    <p class="text-sm text-gray-800">{!! $selectedOpt->teks_opsi !!}</p>
                                @else
                                    {{-- jawaban_peserta is user input — always escape --}}
                                    <p class="text-sm text-gray-800">{{ $aq->jawaban_peserta }}</p>
                                @endif
                            @elseif ($q->tipe === 'PGJ')
                                @php $ids = json_decode($aq->jawaban_peserta, true) ?: []; @endphp
                                @foreach ($q->options->whereIn('id', $ids) as $opt)
                                    <p class="text-sm text-gray-800">• {!! $opt->teks_opsi !!}</p>
                                @endforeach
                            @else
                                <p class="text-sm text-gray-800 whitespace-pre-wrap">{{ $aq->jawaban_peserta }}</p>
                            @endif
                        </div>

                        {{-- Kunci jawaban (PG/PGJ) --}}
                        @if (in_array($q->tipe, ['PG', 'PG_BOBOT']))
                            @php $kunci = $q->options->firstWhere('is_correct', true); @endphp
                            @if ($kunci)
                                <div class="bg-green-50 border border-green-200 rounded-lg p-3 text-sm text-green-800">
                                    <span class="font-semibold">Kunci: </span>{!! $kunci->teks_opsi !!}
                                </div>
                            @endif
                        @endif

                        {{-- Pembahasan --}}
                        @if ($q->penjelasan && ($showPembahasan ?? true))
                            <div class="mt-3 bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm text-blue-800">
                                <p class="font-semibold mb-1">Pembahasan:</p>
                                <div class="prose prose-sm max-w-none text-blue-800">{!! $q->penjelasan !!}</div>
                            </div>
                        @elseif ($q->penjelasan && !($showPembahasan ?? true))
                            <div class="mt-3 bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm text-gray-500">
                                <p class="font-medium">Pembahasan tersedia setelah sesi ujian selesai.</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8 text-center">
            <a href="{{ route('peserta.dashboard') }}"
                class="inline-flex items-center bg-indigo-600 hover:bg-indigo-700 text-white font-semibold
                    px-6 py-2.5 rounded-lg transition-colors text-sm">
                Kembali ke Dashboard
            </a>
        </div>
    </div>
</x-peserta-layout>
