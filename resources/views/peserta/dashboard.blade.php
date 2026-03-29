<x-peserta-layout>
    <x-slot name="title">Dashboard — {{ config('app.name') }}</x-slot>

    {{-- Welcome Banner --}}
    <div class="bg-gradient-to-r from-indigo-600 to-violet-600 rounded-2xl px-6 py-6 mb-8 flex items-center justify-between gap-4 shadow-sm">
        <div>
            <p class="text-indigo-200 text-sm font-medium mb-0.5">Selamat datang kembali,</p>
            <h1 class="text-2xl font-bold text-white">{{ auth()->user()->name }}</h1>
            <p class="text-indigo-200 text-sm mt-1">
                {{ auth()->user()->nomor_peserta }} &middot; {{ now()->translatedFormat('l, d F Y') }}
            </p>
        </div>
        <div class="hidden sm:flex w-16 h-16 rounded-full bg-white/20 items-center justify-center shrink-0">
            <span class="text-3xl font-bold text-white uppercase">
                {{ mb_substr(auth()->user()->name, 0, 1) }}
            </span>
        </div>
    </div>

    @if ($sessions->isEmpty())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <p class="text-gray-700 font-semibold">Belum ada ujian</p>
            <p class="text-gray-400 text-sm mt-1">Tidak ada ujian yang ditugaskan untuk Anda saat ini.</p>
        </div>
    @else
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-semibold text-gray-700">Daftar Ujian <span class="text-gray-400 font-normal">({{ $sessions->count() }})</span></h2>
        </div>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($sessions as $item)
                @php
                    $session = $item['session'];
                    $package = $item['package'];
                    $participation = $item['participation'];
                    $lastAttempt  = $item['last_attempt'];
                    $activeAttempt = $item['active_attempt'];
                    $attemptCount  = $item['attempt_count'];
                    $sisaAttempt   = $item['sisa_attempt']; // null = tak terbatas, 0 = habis
                    $isAktif = $session->isAktif();
                    $bisaRemidi = $isAktif
                        && $participation->status === 'selesai'
                        && ($package->max_pengulangan == 0 || $attemptCount < $package->max_pengulangan);

                    // Determine card accent color
                    $accentClass = $isAktif ? 'border-t-indigo-500' : ($session->isSelesai() ? 'border-t-blue-400' : 'border-t-gray-300');
                @endphp
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 border-t-4 {{ $accentClass }} overflow-hidden hover:shadow-md transition-all group">
                    <div class="p-5">
                        {{-- Header --}}
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <h3 class="font-semibold text-gray-900 leading-snug group-hover:text-indigo-700 transition-colors">
                                {{ $session->nama_sesi }}
                            </h3>
                            @php
                                $statusBadge = match(true) {
                                    $isAktif => 'bg-green-100 text-green-700 ring-green-200',
                                    $session->isSelesai() => 'bg-blue-100 text-blue-700 ring-blue-200',
                                    default => 'bg-gray-100 text-gray-500 ring-gray-200',
                                };
                            @endphp
                            <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold ring-1 {{ $statusBadge }}">
                                {{ \App\Models\ExamSession::STATUS_LABELS[$session->status] }}
                            </span>
                        </div>

                        <p class="text-sm text-gray-500 font-medium">{{ $package->nama }}</p>

                        {{-- Meta --}}
                        <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-400">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ $package->durasi_menit }} menit
                            </span>
                            @if ($session->token_akses)
                                <span class="flex items-center gap-1 text-amber-500">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                    </svg>
                                    Token diperlukan
                                </span>
                            @endif
                            @if ($session->waktu_mulai)
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ $session->waktu_mulai->format('d M, H:i') }}
                                </span>
                            @endif
                        </div>

                        {{-- Footer: status + CTA --}}
                        <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between gap-2">
                            @php
                                $partBadge = match($participation->status) {
                                    'sedang'  => 'bg-amber-100 text-amber-700',
                                    'selesai' => 'bg-green-100 text-green-700',
                                    'diskualifikasi' => 'bg-red-100 text-red-700',
                                    default   => 'bg-gray-100 text-gray-500',
                                };
                            @endphp
                            <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $partBadge }}">
                                {{ \App\Models\ExamSessionParticipant::STATUS_LABELS[$participation->status] }}
                            </span>

                            @if ($activeAttempt)
                                <a href="{{ route('ujian.kerjakan', $session->id) }}"
                                    class="inline-flex items-center gap-1 text-sm font-semibold text-amber-600 hover:text-amber-700 transition-colors">
                                    Lanjutkan
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            @elseif ($isAktif && in_array($participation->status, ['belum', 'sedang']))
                                <a href="{{ route('ujian.show', $session->id) }}"
                                    class="inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-700 transition-colors">
                                    Mulai Ujian
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            @elseif ($bisaRemidi)
                                <div class="flex flex-col items-end gap-0.5">
                                    <a href="{{ route('ujian.show', $session->id) }}"
                                        class="inline-flex items-center gap-1 text-sm font-semibold text-violet-600 hover:text-violet-700 transition-colors">
                                        Kerjakan Ulang
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                    @if ($sisaAttempt !== null)
                                        <span class="text-xs text-gray-400">Sisa {{ $sisaAttempt }} kesempatan</span>
                                    @endif
                                </div>
                            @elseif ($participation->status === 'selesai' && $package->tampilkan_hasil && $lastAttempt)
                                <a href="{{ route('ujian.hasil', $lastAttempt->id) }}"
                                    class="inline-flex items-center gap-1 text-sm font-semibold text-blue-600 hover:text-blue-700 transition-colors">
                                    Lihat Hasil
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-peserta-layout>
