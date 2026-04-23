{{-- resources/views/filament/resources/grading-resource/pages/grading-detail.blade.php --}}
<x-filament-panels::page>

    {{-- ── Info Peserta ─────────────────────────────────────────────────────── --}}
    <x-filament::section icon="heroicon-o-user-circle" heading="Informasi Peserta">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Nama Peserta</p>
                <p class="mt-1 text-base font-semibold text-gray-900 dark:text-white">
                    {{ $attempt->user->name }}
                </p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No. Peserta</p>
                <p class="mt-1 text-base text-gray-900 dark:text-white font-mono">
                    {{ $attempt->user->nomor_peserta ?? '—' }}
                </p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sesi Ujian</p>
                <p class="mt-1 text-base text-gray-900 dark:text-white">
                    {{ $attempt->session->nama_sesi }}
                </p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Attempt</p>
                <p class="mt-1 text-base text-gray-900 dark:text-white">
                    #{{ $attempt->attempt_ke }}
                    &nbsp;·&nbsp;
                    <span class="text-sm text-gray-500">
                        {{ $attempt->waktu_selesai?->diffForHumans() ?? '—' }}
                    </span>
                </p>
            </div>
        </div>
    </x-filament::section>

    {{-- ── Ringkasan Nilai ──────────────────────────────────────────────────── --}}
    <x-filament::section icon="heroicon-o-chart-bar" heading="Ringkasan Nilai Sementara">
        <div class="flex flex-wrap gap-6">
            <div class="text-center">
                <p class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ $attempt->nilai_akhir !== null ? number_format($attempt->nilai_akhir, 1) : '—' }}
                </p>
                <p class="text-sm text-gray-500">Nilai Akhir</p>
            </div>
            <div class="text-center">
                <p class="text-3xl font-bold" style="color:#16a34a;">{{ $attempt->jumlah_benar }}</p>
                <p class="text-sm text-gray-500">Benar</p>
            </div>
            <div class="text-center">
                <p class="text-3xl font-bold text-danger-600">{{ $attempt->jumlah_salah }}</p>
                <p class="text-sm text-gray-500">Salah</p>
            </div>
            <div class="text-center">
                <p class="text-3xl font-bold text-gray-600">{{ $attempt->jumlah_kosong }}</p>
                <p class="text-sm text-gray-500">Kosong</p>
            </div>
        </div>
        @if ($attempt->nilai_akhir === null)
            <p class="mt-4 text-sm" style="color:#d97706;">
                ⚠ Nilai akhir belum tersedia — masih ada soal URAIAN yang belum dinilai.
                Setelah semua soal dinilai, tekan <strong>Simpan &amp; Hitung Ulang Nilai</strong>.
            </p>
        @endif
    </x-filament::section>

    {{-- ── Daftar Soal URAIAN ───────────────────────────────────────────────── --}}
    <x-filament::section icon="heroicon-o-document-text"
                         heading="Semua Soal & Jawaban Peserta"
                         description="Soal URAIAN (biru) memerlukan penilaian manual. Soal lain ditampilkan sebagai referensi.">

        @forelse ($questions as $aq)
            @php
                $question  = $aq->question;
                $bobot     = (float) $question->bobot;
                $isUraian  = $question->tipe === 'URAIAN';
                $fileUrl   = null;
                if ($aq->jawaban_file) {
                    $fileUrl = str_starts_with($aq->jawaban_file, 'http')
                        ? $aq->jawaban_file
                        : route('ujian.file.uraian', [
                            'attemptId' => $attempt->id,
                            'filename'  => basename($aq->jawaban_file),
                        ]);
                }
                // Warna border berdasarkan tipe soal
                if ($isUraian) {
                    $borderClass = $aq->nilai_perolehan !== null
                        ? 'border-left:4px solid #22c55e;'
                        : 'border-left:4px solid #2563eb;';
                } else {
                    $borderClass = $aq->is_correct
                        ? 'border-left:4px solid #4ade80;'
                        : ($aq->is_correct === false ? 'border-left:4px solid #f87171;' : 'border-left:4px solid #d1d5db;');
                }
            @endphp

            <div class="mb-4 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden" style="{{ $borderClass }}">

                {{-- Header soal --}}
                <div class="flex items-center justify-between px-4 py-2.5
                            bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        @if ($isUraian)
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold" style="background:#dbeafe;color:#1d4ed8;">
                                    {{ $aq->urutan }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                                            {{ $aq->urutan }}
                                        </span>
                                    @endif
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                            {{ \App\Models\Question::TIPE_LABELS[$question->tipe] ?? $question->tipe }}
                        </span>
                        <span class="text-xs text-gray-400">&middot; Bobot {{ number_format($bobot, 0) }}</span>
                    </div>

                    <div class="flex items-center gap-2">
                        @if ($isUraian)
                            @if ($aq->nilai_perolehan !== null)
                                <x-filament::badge color="success" size="sm">
                                    Dinilai: {{ number_format($aq->nilai_perolehan, 1) }}
                                </x-filament::badge>
                            @else
                                <x-filament::badge color="warning" size="sm">Belum Dinilai</x-filament::badge>
                            @endif
                        @else
                            @if ($aq->is_correct === true)
                                <x-filament::badge color="success" size="sm">Benar</x-filament::badge>
                            @elseif ($aq->is_correct === false)
                                <x-filament::badge color="danger" size="sm">Salah</x-filament::badge>
                            @else
                                <x-filament::badge color="gray" size="sm">Kosong</x-filament::badge>
                            @endif
                        @endif
                    </div>
                </div>

                <div class="p-4 space-y-3">
                    {{-- Teks Soal --}}
                    <div class="text-sm text-gray-800 dark:text-gray-200">
                        {!! $question->teks_soal !!}
                    </div>

                    {{-- ── PG / PG_BOBOT: tampilkan semua opsi ────────────── --}}
                    @if (in_array($question->tipe, ['PG', 'PG_BOBOT']))
                        @php
                            $selectedOptionId = (int) $aq->jawaban_peserta;
                        @endphp
                        <div class="space-y-1.5">
                            @foreach ($question->options->sortBy('urutan') as $opt)
                                @php
                                    $isPilihan = ($opt->id === $selectedOptionId);
                                    $isKunci   = $opt->is_correct;
                                    // Warna baris
                                    if ($isPilihan && $isKunci)       $rowStyle = 'background:#f0fdf4;border-color:#86efac;';
                                    elseif ($isPilihan && !$isKunci)  $rowStyle = 'background:#fef2f2;border-color:#fca5a5;';
                                    elseif (!$isPilihan && $isKunci)  $rowStyle = 'background:#f7fef9;border-color:#bbf7d0;';
                                    else                              $rowStyle = '';
                                @endphp
                                <div class="flex items-start gap-2.5 rounded-lg border px-3 py-2" style="{{ $rowStyle }}">
                                    {{-- Kode opsi --}}
                                    @if ($isPilihan && $isKunci)
                                        <span class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold text-white" style="background:#22c55e;">{{ $opt->kode_opsi }}</span>
                                    @elseif ($isPilihan && !$isKunci)
                                        <span class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold text-white" style="background:#ef4444;">{{ $opt->kode_opsi }}</span>
                                    @else
                                        <span class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400">{{ $opt->kode_opsi }}</span>
                                    @endif
                                    {{-- Teks opsi --}}
                                    <span class="text-sm flex-1
                                                 {{ ($isPilihan || $isKunci) ? 'text-gray-900 dark:text-gray-100 font-medium' : 'text-gray-700 dark:text-gray-300' }}">
                                        {!! $opt->teks_opsi !!}
                                    </span>
                                    {{-- Keterangan --}}
                                    <div class="flex gap-1 flex-shrink-0">
                                        @if ($isPilihan)
                                            <x-filament::badge color="{{ $isKunci ? 'success' : 'danger' }}" size="sm">
                                                {{ $isKunci ? '✓ Pilihan peserta (benar)' : '✗ Pilihan peserta (salah)' }}
                                            </x-filament::badge>
                                        @elseif ($isKunci)
                                            <x-filament::badge color="success" size="sm">Kunci</x-filament::badge>
                                        @endif
                                        @if ($question->tipe === 'PG_BOBOT' && $opt->bobot_persen)
                                            <x-filament::badge color="gray" size="sm">{{ $opt->bobot_persen }}%</x-filament::badge>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                    {{-- ── PGJ: multi-pilihan ──────────────────────────────── --}}
                    @elseif ($question->tipe === 'PGJ')
                        @php
                            $selectedIds = array_map('intval', json_decode($aq->jawaban_peserta ?? '[]', true));
                        @endphp
                        <div class="space-y-1.5">
                            @foreach ($question->options->sortBy('urutan') as $opt)
                                @php
                                    $isPilihan = in_array($opt->id, $selectedIds);
                                    $isKunci   = $opt->is_correct;
                                    if ($isPilihan && $isKunci)       $rowStyle = 'background:#f0fdf4;border-color:#86efac;';
                                    elseif ($isPilihan && !$isKunci)  $rowStyle = 'background:#fef2f2;border-color:#fca5a5;';
                                    elseif (!$isPilihan && $isKunci)  $rowStyle = 'background:#f7fef9;border-color:#bbf7d0;';
                                    else                              $rowStyle = '';
                                @endphp
                                <div class="flex items-start gap-2.5 rounded-lg border px-3 py-2" style="{{ $rowStyle }}">
                                    @if ($isPilihan && $isKunci)
                                        <span class="flex-shrink-0 w-5 h-5 rounded flex items-center justify-center text-xs font-bold mt-0.5 text-white" style="background:#22c55e;">✓</span>
                                    @elseif ($isPilihan && !$isKunci)
                                        <span class="flex-shrink-0 w-5 h-5 rounded flex items-center justify-center text-xs font-bold mt-0.5 text-white" style="background:#ef4444;">✓</span>
                                    @else
                                        <span class="flex-shrink-0 w-5 h-5 rounded flex items-center justify-center text-xs font-bold mt-0.5 bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400">{{ $opt->kode_opsi }}</span>
                                    @endif
                                    <span class="text-sm flex-1 {{ ($isPilihan || $isKunci) ? 'text-gray-900 dark:text-gray-100 font-medium' : 'text-gray-700 dark:text-gray-300' }}">
                                        <span class="text-xs text-gray-500 mr-1">{{ $opt->kode_opsi }}.</span>{!! $opt->teks_opsi !!}
                                    </span>
                                    <div class="flex gap-1 flex-shrink-0">
                                        @if ($isPilihan)
                                            <x-filament::badge color="{{ $isKunci ? 'success' : 'danger' }}" size="sm">
                                                {{ $isKunci ? 'Dipilih ✓' : 'Dipilih ✗' }}
                                            </x-filament::badge>
                                        @elseif ($isKunci)
                                            <x-filament::badge color="success" size="sm">Kunci</x-filament::badge>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                    {{-- ── JODOH: tampilkan pasangan premis-respon ─────────── --}}
                    @elseif ($question->tipe === 'JODOH')
                        @php
                            $jawabPasangan = json_decode($aq->jawaban_peserta ?? '{}', true);
                        @endphp
                        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-100 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 w-1/3">#</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-400">Premis</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-400">Respon Peserta</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-400">Respon Benar</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($question->matches->sortBy('urutan') as $match)
                                        @php
                                            $matchIdStr  = (string) $match->id;
                                            $pesertaResp = $jawabPasangan[$matchIdStr] ?? null;
                                            // Benar jika key === value (pasangan ke dirinya sendiri)
                                            $isBenar = ($pesertaResp !== null && (string) $pesertaResp === $matchIdStr);
                                            // Cari respon yang dipasangkan peserta
                                            $responPeserta = $pesertaResp
                                                ? ($question->matches->firstWhere('id', (int) $pesertaResp)?->respon ?? '?')
                                                : '—';
                                        @endphp
                                        <tr style="{{ $isBenar ? 'background:#f0fdf4;' : ($pesertaResp ? 'background:#fef2f2;' : '') }}">
                                            <td class="px-3 py-2 text-gray-500 text-xs">{{ $loop->iteration }}</td>
                                            <td class="px-3 py-2 text-gray-800 dark:text-gray-200">{{ $match->premis }}</td>
                                            <td class="px-3 py-2 font-medium" style="color:{{ $isBenar ? '#15803d' : '#b91c1c' }};">
                                                {{ $responPeserta }}
                                                @if ($pesertaResp)
                                                    <span class="ml-1">{{ $isBenar ? '✓' : '✗' }}</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2" style="color:#15803d;">{{ $match->respon }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                    {{-- ── ISIAN: teks jawaban & keyword ───────────────────── --}}
                    @elseif ($question->tipe === 'ISIAN')
                        @php
                            $keywords     = $question->keywords->pluck('keyword')->map(fn($k) => strtolower(trim($k)));
                            $jawabanBersih = strtolower(trim($aq->jawaban_peserta ?? ''));
                            $isIsianBenar  = $keywords->contains($jawabanBersih);
                        @endphp
                        <div class="rounded-lg p-3 border" style="{{ $isIsianBenar ? 'background:#f0fdf4;border-color:#bbf7d0;' : 'background:#fef2f2;border-color:#fecaca;' }}">
                            <p class="text-xs font-semibold uppercase tracking-wide mb-2" style="color:{{ $isIsianBenar ? '#16a34a' : '#dc2626' }};">
                                Jawaban Peserta {{ $isIsianBenar ? '✓ Benar' : '✗ Salah' }}
                            </p>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $aq->jawaban_peserta ?? '—' }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                Kunci: {{ $question->keywords->pluck('keyword')->implode(' / ') }}
                            </p>
                        </div>

                    {{-- ── URAIAN: teks & file jawaban ─────────────────────── --}}
                    @elseif ($isUraian)
                        @if ($aq->jawaban_peserta || $fileUrl)
                            <div class="rounded-lg p-3 border" style="background:#eff6ff;border-color:#bfdbfe;">
                                <p class="text-xs font-semibold uppercase tracking-wide mb-2 text-primary-600 dark:text-primary-400">
                                    Jawaban Peserta
                                </p>
                                @if ($aq->jawaban_peserta)
                                    <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ $aq->jawaban_peserta }}</p>
                                @endif
                                @if ($fileUrl)
                                    <div class="mt-2">
                                        @php $ext = strtolower(pathinfo($aq->jawaban_file, PATHINFO_EXTENSION)); @endphp
                                        @if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                            <img src="{{ $fileUrl }}" alt="Jawaban file"
                                                 class="max-w-full max-h-96 rounded-lg border border-gray-200 dark:border-gray-700 object-contain">
                                        @else
                                            <a href="{{ $fileUrl }}" target="_blank" rel="noopener noreferrer"
                                               class="inline-flex items-center gap-1.5 text-sm text-primary-600 hover:underline">
                                                <x-heroicon-o-paper-clip class="w-4 h-4"/> Lihat File
                                            </a>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @else
                            <p class="text-xs text-gray-400 italic">Peserta tidak memberikan jawaban.</p>
                        @endif
                    @endif

                    {{-- Input Nilai — hanya untuk URAIAN --}}
                    @if ($isUraian)
                        <div class="flex items-end gap-3 pt-1 border-t border-gray-100 dark:border-gray-700">
                            <div class="flex-1 max-w-xs">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Nilai Perolehan
                                    <span class="text-gray-400 font-normal">(0 – {{ number_format($bobot, 0) }})</span>
                                </label>
                                <input type="number"
                                       min="0"
                                       max="{{ $bobot }}"
                                       step="0.5"
                                       wire:model.lazy="nilai.{{ $aq->id }}"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-600
                                              dark:bg-gray-900 dark:text-white text-sm
                                              focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                       placeholder="0">
                            </div>
                            <div class="text-sm text-gray-500" style="padding-bottom:0.5rem;">/ {{ number_format($bobot, 0) }}</div>
                        </div>
                    @endif
                </div>
            </div>

        @empty
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-6 text-center text-gray-500">
                Tidak ada soal untuk attempt ini.
            </div>
        @endforelse

    </x-filament::section>

</x-filament-panels::page>
