<x-ujian-layout>
    <x-slot name="title">{{ $session->nama_sesi }}</x-slot>

    @php
        $package = $session->package;
        $totalSoal = $soalList->count();
        $soalCount = $soalList->filter(fn ($q) => $q->isDijawab())->count();
        $hasSections = $package->has_sections && $seksiAktif !== null;
    @endphp

    <div
        x-data="ujianApp()"
        @blur.window="handleBlur()"
        @visibilitychange.window="handleVisibility()"
        class="flex flex-col h-screen overflow-hidden"
    >
        {{-- ── TOP BAR ── --}}
        <header class="bg-slate-800 border-b border-slate-700 sticky top-0 z-50 shrink-0 exam-header">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 flex items-center justify-between h-14 gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-white truncate exam-hd-title">{{ $session->nama_sesi }}</p>
                    <p class="text-xs text-slate-400 exam-hd-sub">
                        {{ auth()->user()->name }}
                    </p>
                </div>

                {{-- Timer --}}
                <div class="flex items-center gap-2 shrink-0">
                    @if ($hasSections)
                    {{-- Section timer --}}
                    <div class="text-center">
                        <p class="text-xs text-slate-400 mb-0.5 exam-hd-sub">Sisa Bagian</p>
                        <div
                            class="font-mono text-lg font-bold tabular-nums px-3 py-1 rounded-md transition-colors exam-seksi-timer"
                            :class="sisaSeksiDetik <= 120 ? 'bg-orange-500 text-white animate-pulse' : 'bg-indigo-700 text-white'"
                            x-text="formatWaktu(sisaSeksiDetik)"
                        ></div>
                    </div>
                    @endif
                    <div class="text-center">
                        <p class="text-xs text-slate-400 mb-0.5 exam-hd-sub">Sisa Waktu</p>
                        <div
                            class="font-mono text-lg font-bold tabular-nums px-3 py-1 rounded-md transition-colors exam-timer"
                            :class="sisaDetik <= 300 ? 'bg-red-600 text-white animate-pulse' : 'bg-slate-700 text-white'"
                            x-text="formatWaktu(sisaDetik)"
                        ></div>
                    </div>
                    <template x-if="waktuPerSoalDetik > 0">
                        <div class="text-center">
                            <p class="text-xs text-slate-400 mb-0.5">Per Soal</p>
                            <div
                                class="font-mono text-lg font-bold tabular-nums px-3 py-1 rounded-md transition-colors"
                                :class="timerSoalSisa <= 10 ? 'bg-orange-500 text-white animate-pulse' : 'bg-slate-600 text-white'"
                                x-text="timerSoalSisa"
                            ></div>
                        </div>
                    </template>
                </div>

                {{-- Progress --}}
                <div class="hidden sm:flex items-center gap-1.5 text-sm shrink-0">
                    <span class="text-slate-300 exam-hd-sub">
                        <span class="font-bold text-white exam-hd-title" x-text="soalTerjawab"></span>/<span x-text="totalSoal"></span>
                    </span>
                </div>

                {{-- Theme toggle --}}
                <button @click="toggleTheme()" title="Ganti tema"
                    class="shrink-0 p-2 rounded-lg transition-colors text-slate-400 hover:text-white hover:bg-slate-700 exam-theme-btn">
                    <svg x-show="!lightMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <svg x-show="lightMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                </button>

                {{-- Submit --}}
                @if ($hasSections)
                <div class="flex items-center gap-2 shrink-0">
                    @if ($navigasiSeksi === 'urut_kembali' && $seksiAktif->urutan > 1)
                    <button @click="kembaliSeksi()"
                        :disabled="sectionLoading"
                        class="bg-slate-600 hover:bg-slate-500 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors disabled:opacity-50">
                        ← Kembali
                    </button>
                    @endif
                    <button @click="triggerSectionComplete(false)"
                        class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
                        @if ($seksiAktif->urutan < $seksiList->count())
                            Selesai Bagian →
                        @else
                            Selesai Ujian
                        @endif
                    </button>
                </div>
                @else
                <form @submit.prevent="konfirmasiSubmit()" class="shrink-0">
                    <button type="submit"
                        class="bg-green-600 hover:bg-green-500 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
                        Selesai
                    </button>
                </form>
                @endif
            </div>
            {{-- Progress bar --}}
            <div class="h-1 bg-slate-700 exam-pb-bg">
                <div class="h-1 bg-indigo-500 transition-all duration-300"
                    :style="'width:' + (soalTerjawab / totalSoal * 100) + '%'"></div>
            </div>
        </header>

        {{-- ── TAB SWITCH WARNING ── --}}
        <div x-show="tabWarning" x-cloak
            class="fixed inset-0 z-[200] bg-black/80 flex items-center justify-center p-4"
        >
            <div class="bg-white rounded-xl text-gray-900 p-8 max-w-sm w-full text-center shadow-2xl">
                <div class="text-5xl mb-4">⚠️</div>
                <h2 class="text-xl font-bold mb-2">Peringatan Pelanggaran</h2>
                <p class="text-gray-600 text-sm mb-4" x-text="tabWarningMsg"></p>
                <button @click="tabWarning = false"
                    class="w-full bg-indigo-600 text-white font-semibold py-2 rounded-lg">
                    Saya Mengerti
                </button>
            </div>
        </div>

        @if ($hasSections)
        {{-- ── SECTION COMPLETE MODAL ── --}}
        <div x-show="showSectionComplete" x-cloak
            class="fixed inset-0 z-[200] bg-black/80 flex items-center justify-center p-4"
        >
            <div class="bg-white rounded-xl text-gray-900 p-8 max-w-sm w-full text-center shadow-2xl">
                <div class="text-5xl mb-4">⏱️</div>
                <h2 class="text-xl font-bold mb-2" x-text="sectionCompleteTitle"></h2>
                <p class="text-gray-600 text-sm mb-6" x-text="sectionCompleteMsg"></p>
                <div class="flex gap-3">
                    <button x-show="!sectionCompleteIsTimeout"
                        @click="showSectionComplete = false"
                        class="flex-1 bg-gray-100 text-gray-700 font-semibold py-3 rounded-lg hover:bg-gray-200 transition-colors">
                        Batal
                    </button>
                    <button @click="lanjutSeksi()"
                        :disabled="sectionLoading"
                        :class="sectionCompleteIsTimeout ? 'w-full' : 'flex-1'"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-lg disabled:opacity-50 transition-colors">
                        <span x-show="!sectionLoading">Lanjutkan →</span>
                        <span x-show="sectionLoading">Memproses…</span>
                    </button>
                </div>
            </div>
        </div>
        @endif

        {{-- ── CONFIRM SUBMIT MODAL ── --}}
        <div x-show="showConfirmSubmit" x-cloak
            class="fixed inset-0 z-[200] bg-black/70 flex items-center justify-center p-4"
        >
            <div class="bg-white rounded-xl text-gray-900 p-8 max-w-sm w-full text-center shadow-2xl">
                <h2 class="text-xl font-bold mb-2">Konfirmasi Submit</h2>
                <p class="text-gray-600 text-sm mb-1">
                    Anda telah menjawab <strong x-text="soalTerjawab"></strong> dari <strong>{{ $totalSoal }}</strong> soal.
                </p>
                <p class="text-gray-600 text-sm mb-6">Submit ujian tidak dapat dibatalkan.</p>
                <div class="flex gap-3">
                    <button @click="showConfirmSubmit = false"
                        class="flex-1 bg-gray-100 text-gray-700 font-semibold py-2 rounded-lg hover:bg-gray-200">
                        Batal
                    </button>
                    <button @click="doSubmit()"
                        class="flex-1 bg-green-600 text-white font-semibold py-2 rounded-lg hover:bg-green-500">
                        Ya, Submit
                    </button>
                </div>
            </div>
        </div>

        {{-- ── MAIN CONTENT ── --}}
        <div class="flex flex-1 overflow-hidden">
            {{-- Sidebar nomor soal --}}
            <aside class="w-64 bg-slate-800 border-r border-slate-700 hidden lg:flex flex-col shrink-0 exam-sidebar">
                <div class="px-4 py-3 border-b border-slate-700 shrink-0 exam-sb-border">
                    @if ($hasSections)
                        <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider exam-sb-title">Bagian {{ $seksiAktif->urutan }}/{{ $seksiList->count() }}: {{ $seksiAktif->nama }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $totalSoal }} soal bagian ini &bull; {{ $totalSoalSemua }} total</p>
                    @else
                        <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider exam-sb-title">Navigasi Soal</p>
                    @endif
                </div>
                @if ($hasSections && $navigasiSeksi === 'bebas')
                <div class="px-3 py-2 border-b border-slate-700 shrink-0 space-y-1">
                    @foreach ($seksiList->sortBy('urutan') as $s)
                    <button @click="switchSection({{ $s->id }})"
                        :disabled="sectionLoading"
                        :class="activeSectionId === {{ $s->id }} ? 'bg-indigo-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600'"
                        class="w-full text-left text-xs font-semibold px-3 py-2 rounded-lg transition-colors disabled:opacity-50">
                        {{ $s->urutan }}. {{ $s->nama }}
                    </button>
                    @endforeach
                </div>
                @endif
                <div class="flex-1 overflow-y-auto p-3 grid grid-cols-5 gap-1.5 content-start">
                    @foreach ($soalList as $aq)
                        <button
                            @click="scrollToSoal({{ $loop->index }})"
                            :class="[getSoalButtonClass({{ $aq->question_id }}), activeSoal === {{ $loop->index }} ? 'ring-2 ring-white ring-inset' : '']"
                            class="w-full aspect-square rounded text-xs font-semibold transition-colors"
                        >{{ $loop->iteration }}</button>
                    @endforeach
                </div>

                <div class="px-4 py-3 border-t border-slate-700 text-xs shrink-0 exam-sb-footer">
                    <p class="text-slate-500 font-semibold uppercase tracking-wider mb-2.5 exam-sb-subtitle">Statistik</p>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="w-3.5 h-3.5 rounded bg-green-600 shrink-0"></span>
                                <span class="text-slate-300 exam-sb-legend">Terjawab</span>
                            </div>
                            <span class="font-bold text-green-400 tabular-nums exam-sb-count-green" x-text="countDijawab()"></span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="w-3.5 h-3.5 rounded bg-yellow-500 shrink-0"></span>
                                <span class="text-slate-300 exam-sb-legend">Ragu-ragu</span>
                            </div>
                            <span class="font-bold text-yellow-400 tabular-nums exam-sb-count-yellow" x-text="countRagu()"></span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="w-3.5 h-3.5 rounded bg-slate-600 shrink-0"></span>
                                <span class="text-slate-300 exam-sb-legend">Belum jawab</span>
                            </div>
                            <span class="font-bold text-slate-400 tabular-nums exam-sb-count-slate" x-text="countBelum()"></span>
                        </div>
                    </div>
                </div>
            </aside>

            {{-- Soal list --}}
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 relative" id="soal-container">

                <div class="max-w-5xl mx-auto space-y-6">
                    @foreach ($soalList as $aq)
                        @php
                            $q     = $aq->question;
                            $group = $q->group;
                        @endphp
                        <div
                            id="soal-{{ $loop->index }}"
                            class="rounded-xl border border-slate-700 overflow-hidden exam-card"
                            :class="activeSoal === {{ $loop->index }} ? 'ring-2 ring-indigo-500' : ''"
                        >
                            {{-- ── GROUP SPLIT-PANEL WRAPPER ── --}}
                            @if ($group)
                                {{-- Single x-data scope for tab state shared between mobile toggle + panels --}}
                                <div x-data="{ stimTab: false }">
                                {{-- Mobile: tab toggle --}}
                                <div class="lg:hidden flex border-b border-slate-700 bg-slate-800 exam-card-hd">
                                    <button
                                        @click="stimTab = false"
                                        :class="!stimTab ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white'"
                                        class="flex-1 py-2 text-xs font-semibold transition-colors">Soal</button>
                                    <button
                                        @click="stimTab = true"
                                        :class="stimTab ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white'"
                                        class="flex-1 py-2 text-xs font-semibold transition-colors">Materi</button>
                                </div>

                                {{-- Split panel (lg+: side-by-side) --}}
                                <div class="flex flex-col lg:flex-row exam-card-body">
                                    {{-- Panel stimulus (kiri 40%) --}}
                                    <div
                                        class="lg:w-2/5 lg:max-h-[70vh] lg:overflow-y-auto border-b border-slate-700 lg:border-b-0 lg:border-r"
                                        :class="stimTab ? '' : 'hidden lg:block'"
                                    >
                                        <div class="px-4 py-3 border-b border-slate-700 bg-slate-750 shrink-0 exam-card-hd hidden lg:flex items-center gap-2">
                                            <span class="text-xs font-semibold text-slate-300 uppercase tracking-wider exam-card-label">Stimulus</span>
                                            <span class="text-xs text-slate-500 exam-card-tipe">{{ \App\Models\QuestionGroup::TIPE_STIMULUS_LABELS[$group->tipe_stimulus] ?? $group->tipe_stimulus }}</span>
                                        </div>
                                        @if ($group->deskripsi)
                                            <p class="px-4 pt-3 text-xs text-slate-400 italic exam-card-label">{{ $group->deskripsi }}</p>
                                        @endif
                                        <div class="p-4">
                                            @if (in_array($group->tipe_stimulus, ['teks', 'tabel']))
                                                <div class="prose prose-invert prose-sm max-w-none text-gray-200 exam-soal-text">
                                                    {!! $group->konten !!}
                                                </div>
                                            @elseif ($group->tipe_stimulus === 'gambar')
                                                <img src="{{ $group->konten }}" alt="Stimulus" class="max-w-full rounded-lg">
                                            @elseif ($group->tipe_stimulus === 'audio')
                                                <audio controls class="w-full">
                                                    <source src="{{ $group->konten }}">
                                                    Browser tidak mendukung audio.
                                                </audio>
                                            @elseif ($group->tipe_stimulus === 'video')
                                                @php
                                                    // detect YouTube embed
                                                    $isYoutube = str_contains($group->konten, 'youtube.com') || str_contains($group->konten, 'youtu.be');
                                                    $videoSrc  = $group->konten;
                                                    if ($isYoutube) {
                                                        preg_match('/(?:v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $videoSrc, $ytMatch);
                                                        $videoSrc = isset($ytMatch[1]) ? 'https://www.youtube.com/embed/' . $ytMatch[1] : $videoSrc;
                                                    }
                                                @endphp
                                                @if ($isYoutube)
                                                    <div class="aspect-video">
                                                        <iframe src="{{ $videoSrc }}" class="w-full h-full rounded-lg"
                                                            allowfullscreen></iframe>
                                                    </div>
                                                @else
                                                    <video controls class="w-full rounded-lg">
                                                        <source src="{{ $group->konten }}">
                                                        Browser tidak mendukung video.
                                                    </video>
                                                @endif
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Panel soal (kanan 60%) --}}
                                    <div class="lg:w-3/5 lg:max-h-[70vh] lg:overflow-y-auto flex flex-col"
                                        :class="stimTab ? 'hidden lg:flex' : 'flex flex-col'">
                                        @include('peserta._soal-card', ['aq' => $aq, 'q' => $q, 'attempt' => $attempt, 'loop' => $loop])
                                    </div>
                                </div>
                                </div>{{-- end x-data="{ stimTab }" --}}

                            @else
                                {{-- Standalone: layout full-width --}}
                                <div class="exam-card-body">
                                    @include('peserta._soal-card', ['aq' => $aq, 'q' => $q, 'attempt' => $attempt, 'loop' => $loop])
                                </div>
                            @endif
                        </div>
                    @endforeach

                    {{-- Bottom submit button --}}
                    <div class="pb-8 text-center">
                        @if ($hasSections)
                        <div class="inline-flex items-center gap-3 flex-wrap justify-center">
                            @if ($navigasiSeksi === 'urut_kembali' && $seksiAktif->urutan > 1)
                            <button @click="kembaliSeksi()"
                                :disabled="sectionLoading"
                                class="inline-flex items-center gap-2 bg-slate-600 hover:bg-slate-500 text-white font-semibold px-8 py-3 rounded-xl transition-colors text-base disabled:opacity-50">
                                ← Kembali ke Bagian Sebelumnya
                            </button>
                            @endif
                            <button @click="triggerSectionComplete(false)"
                                class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold
                                    px-8 py-3 rounded-xl transition-colors text-base">
                                @if ($seksiAktif->urutan < $seksiList->count())
                                    → Selesaikan Bagian {{ $seksiAktif->urutan }} &amp; Lanjut
                                @else
                                    ✓ Selesaikan Ujian
                                @endif
                            </button>
                        </div>
                        @else
                        <button @click="konfirmasiSubmit()"
                            class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-500 text-white font-semibold
                                px-8 py-3 rounded-xl transition-colors text-base">
                            ✓ Selesaikan Ujian
                        </button>
                        @endif
                    </div>
                </div>
            </main>
        </div>

        {{-- ── MOBILE NAV DRAWER (visible on < lg screens) ── --}}
        {{-- Floating button --}}
        <button
            @click="showMobileNav = true"
            class="fixed bottom-5 right-5 z-50 lg:hidden flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500
                   text-white text-sm font-semibold px-4 py-2.5 rounded-full shadow-lg transition-colors"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            Navigasi Soal
        </button>

        {{-- Backdrop --}}
        <div
            x-show="showMobileNav"
            x-cloak
            @click="showMobileNav = false"
            class="fixed inset-0 z-[150] bg-black/60 lg:hidden"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        ></div>

        {{-- Drawer panel --}}
        <div
            x-show="showMobileNav"
            x-cloak
            class="fixed bottom-0 left-0 right-0 z-[160] bg-slate-800 rounded-t-2xl shadow-2xl lg:hidden max-h-[70vh] flex flex-col"
            x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="translate-y-full"
            x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0"
            x-transition:leave-end="translate-y-full"
        >
            {{-- Handle + header --}}
            <div class="flex items-center justify-between px-5 pt-4 pb-3 border-b border-slate-700 shrink-0">
                <div>
                    @if ($hasSections)
                        <p class="text-sm font-semibold text-white">Bagian {{ $seksiAktif->urutan }}/{{ $seksiList->count() }}: {{ $seksiAktif->nama }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            <span class="font-bold text-white" x-text="soalTerjawab"></span>/{{ $totalSoal }} bagian ini &bull; {{ $totalSoalSemua }} soal total
                        </p>
                    @else
                        <p class="text-sm font-semibold text-white">Navigasi Soal</p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            <span class="font-bold text-white" x-text="soalTerjawab"></span> / {{ $totalSoal }} terjawab
                        </p>
                    @endif
                </div>
                <button @click="showMobileNav = false" class="p-1.5 rounded-lg hover:bg-slate-700 text-slate-400 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            @if ($hasSections && $navigasiSeksi === 'bebas')
            {{-- Section switcher for bebas mode --}}
            <div class="px-4 py-2.5 border-b border-slate-700 shrink-0 flex flex-wrap gap-1.5">
                @foreach ($seksiList->sortBy('urutan') as $s)
                <button @click="switchSection({{ $s->id }}); showMobileNav = false"
                    :disabled="sectionLoading"
                    :class="activeSectionId === {{ $s->id }} ? 'bg-indigo-600 text-white' : 'bg-slate-700 text-slate-300'"
                    class="text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors disabled:opacity-50">
                    {{ $s->urutan }}. {{ $s->nama }}
                </button>
                @endforeach
            </div>
            @endif

            {{-- Soal grid --}}
            <div class="flex-1 overflow-y-auto p-4 grid grid-cols-8 sm:grid-cols-10 gap-2">
                @foreach ($soalList as $aq)
                    <button
                        @click="scrollToSoal({{ $loop->index }}); showMobileNav = false"
                        :class="[getSoalButtonClass({{ $aq->question_id }}), activeSoal === {{ $loop->index }} ? 'ring-2 ring-white ring-inset' : '']"
                        class="w-full aspect-square rounded text-xs font-semibold transition-colors"
                    >{{ $loop->iteration }}</button>
                @endforeach
            </div>

            {{-- Legend --}}
            <div class="px-5 py-3 border-t border-slate-700 shrink-0 flex items-center gap-5 text-xs text-slate-300">
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-green-600 inline-block"></span>Terjawab</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-yellow-500 inline-block"></span>Ragu-ragu</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-slate-600 inline-block"></span>Belum jawab</span>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
    /* ── UJIAN LIGHT MODE ── */
    body[data-theme="light"] {
        background-color: #f1f5f9 !important;
        color: #1e293b;
    }
    /* Header */
    body[data-theme="light"] .exam-header {
        background-color: #ffffff;
        border-color: #e2e8f0;
    }
    body[data-theme="light"] .exam-hd-title { color: #1e293b !important; }
    body[data-theme="light"] .exam-hd-sub   { color: #64748b !important; }
    body[data-theme="light"] .exam-timer:not(.bg-red-600):not(.animate-pulse) {
        background-color: #e2e8f0 !important;
        color: #1e293b !important;
    }
    body[data-theme="light"] .exam-seksi-timer:not(.bg-orange-500):not(.animate-pulse) {
        background-color: #e0e7ff !important;
        color: #3730a3 !important;
    }
    body[data-theme="light"] .exam-pb-bg { background-color: #e2e8f0; }
    body[data-theme="light"] .exam-theme-btn { color: #64748b; }
    body[data-theme="light"] .exam-theme-btn:hover { background-color: #f1f5f9; color: #1e293b; }
    /* Sidebar */
    body[data-theme="light"] .exam-sidebar {
        background-color: #ffffff;
        border-color: #e2e8f0;
    }
    body[data-theme="light"] .exam-sb-border { border-color: #e2e8f0; }
    body[data-theme="light"] .exam-sb-title  { color: #94a3b8; }
    body[data-theme="light"] .exam-sb-subtitle { color: #94a3b8; }
    body[data-theme="light"] .exam-sb-footer { border-color: #e2e8f0; }
    body[data-theme="light"] .exam-sb-legend { color: #475569 !important; }
    body[data-theme="light"] .exam-sb-count-green  { color: #16a34a !important; }
    body[data-theme="light"] .exam-sb-count-yellow { color: #d97706 !important; }
    body[data-theme="light"] .exam-sb-count-slate  { color: #94a3b8 !important; }
    body[data-theme="light"] .exam-nav-empty {
        background-color: #e2e8f0 !important;
        color: #475569 !important;
    }
    body[data-theme="light"] .exam-nav-empty:hover {
        background-color: #cbd5e1 !important;
    }
    body[data-theme="light"] .exam-card-body {
        background-color: #ffffff;
    }
    /* Soal cards */
    body[data-theme="light"] .exam-card {
        background-color: #ffffff;
        border-color: #e2e8f0;
    }
    body[data-theme="light"] .exam-card-hd {
        background-color: #f8fafc;
        border-color: #e2e8f0;
    }
    body[data-theme="light"] .exam-card-label { color: #475569 !important; }
    body[data-theme="light"] .exam-card-tipe  { color: #94a3b8 !important; }
    body[data-theme="light"] .exam-soal-text,
    body[data-theme="light"] .exam-soal-text * {
        color: #1e293b !important;
        --tw-prose-body: #374151;
        --tw-prose-headings: #111827;
        --tw-prose-bold: #111827;
        --tw-prose-links: #4f46e5;
    }
    /* Options */
    body[data-theme="light"] .exam-option { border-color: transparent; }
    body[data-theme="light"] .exam-option:hover { background-color: #f1f5f9 !important; }
    body[data-theme="light"] .exam-option:has(input:checked) {
        background-color: #eef2ff !important;
        border-color: #818cf8 !important;
    }
    body[data-theme="light"] .exam-opt-text { color: #1e293b !important; }
    /* Inputs / Selects / Textareas */
    body[data-theme="light"] .exam-input {
        background-color: #f8fafc !important;
        border-color: #cbd5e1 !important;
        color: #1e293b !important;
    }
    body[data-theme="light"] .exam-input option {
        background-color: #ffffff;
        color: #1e293b;
    }
    </style>
    @endpush

    @push('scripts')
    @php
        $_ujianData = [
            'attemptId'      => $attempt->id,
            'sisaDetik'      => $attempt->sisaWaktuDetik(),
            'soalTerjawab'   => $soalList->filter(fn ($q) => $q->isDijawab())->count(),
            'totalSoal'      => $totalSoal,
            'tabSwitchCount' => $attempt->tabSwitchCount(),
            'states'         => $soalList->mapWithKeys(fn ($aq) => [
                $aq->question_id => ['is_ragu' => (bool) $aq->is_ragu, 'dijawab' => $aq->isDijawab()],
            ])->all(),
            'pgjStates'      => $soalList->where('question.tipe', 'PGJ')
                ->mapWithKeys(fn ($aq) => [
                    $aq->question_id => json_decode($aq->jawaban_peserta ?: '[]') ?? [],
                ])->all(),
            'jodohStates'    => $soalList->where('question.tipe', 'JODOH')
                ->mapWithKeys(fn ($aq) => [
                    $aq->question_id => json_decode($aq->jawaban_peserta ?: '{}') ?? (object)[],
                ])->all(),
            'hasSections'    => $hasSections,
            'sisaSeksiDetik' => $hasSections ? $sisaSeksiDetik : null,
            'sectionId'      => $hasSections ? $seksiAktif->id : null,
            'sectionNama'    => $hasSections ? $seksiAktif->nama : null,
            'sectionUrutan'  => $hasSections ? $seksiAktif->urutan : null,
            'totalSeksi'      => $hasSections ? $seksiList->count() : null,
            'navigasiSeksi'   => $hasSections ? $navigasiSeksi : null,
            'visitedSections' => $hasSections ? $visitedSections->values()->all() : [],
            'seksiList'       => $hasSections ? $seksiList->sortBy('urutan')->map(fn ($s) => [
                'id'           => $s->id,
                'nama'         => $s->nama,
                'urutan'       => $s->urutan,
                'durasi_menit' => $s->durasi_menit,
            ])->values()->all() : [],
            'routes'         => [
                'jawab'      => route('ujian.jawab'),
                'uploadFile' => route('ujian.upload-file'),
                'baseUrl'    => url('/ujian'),
            ],
            'tabSwitchAction'    => $tabSwitchAction,
            'autoSubmitOnMaxTab' => $autoSubmitOnMaxTab,
            'preventCopyPaste'   => $preventCopyPaste,
            'preventRightClick'  => $preventRightClick,
            'requireFullscreen'  => $requireFullscreen,
            'pesertaName'        => auth()->user()->name,
            'pesertaNomor'       => auth()->user()->nomor_peserta ?? '',
            'maxTabSwitch'       => $maxTabSwitch ?? 3,
            'waktuPerSoalDetik'  => $waktuPerSoalDetik ?? 0,
            'navigasiPerSoal'    => $navigasiPerSoal ?? 'bebas',
        ];
    @endphp
    {{-- Exam init data (not parsed as JS by IDE) --}}
    <script type="application/json" id="ujian-init-data">@json($_ujianData)</script>
    <script>
    (function () { window.__ujian = JSON.parse(document.getElementById('ujian-init-data').textContent); })();

    window.ujianApp = function () {
        const _d = window.__ujian;
        return {
            attemptId: _d.attemptId,
            sisaDetik: _d.sisaDetik,
            sisaSeksiDetik: _d.sisaSeksiDetik ?? 0,
            navigasiSeksi: _d.navigasiSeksi,
            activeSectionId: _d.sectionId,
            visitedSections: _d.visitedSections ?? [],
            soalTerjawab: _d.soalTerjawab,
            totalSoal: _d.totalSoal,
            activeSoal: 0,
            tabWarning: false,
            tabWarningMsg: '',
            showConfirmSubmit: false,
            showSectionComplete: false,
            sectionCompleteTitle: '',
            sectionCompleteMsg: '',
            sectionLoading: false,
            sectionCompleteIsTimeout: false,
            tabSwitchCount: _d.tabSwitchCount,
            maxTabSwitch: _d.maxTabSwitch,
            lightMode: localStorage.getItem('examTheme') === 'light',
            showMobileNav: false,
            states: _d.states,
            pgjStates: _d.pgjStates,
            jodohStates: _d.jodohStates,
            waktuPerSoalDetik: _d.waktuPerSoalDetik ?? 0,
            navigasiPerSoal: _d.navigasiPerSoal ?? 'bebas',
            timerSoalSisa: _d.waktuPerSoalDetik ?? 0,

            init() {
                document.body.dataset.theme = this.lightMode ? 'light' : 'dark';
                this.$watch('lightMode', val => {
                    document.body.dataset.theme = val ? 'light' : 'dark';
                });
                this._submitted = false;
                const timerInterval = setInterval(() => {
                    if (this.sisaDetik > 0) this.sisaDetik--;
                    if (this.sisaDetik <= 0) {
                        clearInterval(timerInterval);
                        this.doAutoSubmit('timeout');
                    }
                }, 1000);

                // Section timer
                // Hanya mode 'urut' yang force-advance saat timer habis.
                // Mode urut_kembali / bebas: hitung mundur saja, tidak paksa pindah.
                if (_d.hasSections) {
                    const strictTimer = _d.navigasiSeksi === 'urut';
                    const seksiInterval = setInterval(() => {
                        if (this.sisaSeksiDetik > 0) this.sisaSeksiDetik--;
                        if (this.sisaSeksiDetik <= 0) {
                            clearInterval(seksiInterval);
                            if (strictTimer) this.triggerSectionComplete(true);
                        }
                    }, 1000);
                }

                setInterval(() => this.pollStatus(), 30000);

                // Per-question timer
                if (this.waktuPerSoalDetik > 0) {
                    this.timerSoalSisa = this.waktuPerSoalDetik;
                    this._soalTimerInterval = setInterval(() => {
                        if (this.timerSoalSisa > 0) this.timerSoalSisa--;
                        if (this.timerSoalSisa <= 0) {
                            if (this.navigasiPerSoal === 'maju') {
                                // Auto-advance to next question
                                const next = this.activeSoal + 1;
                                if (next < this.totalSoal) {
                                    this.activeSoal = next;
                                }
                            }
                            // Reset timer for next soal
                            this.timerSoalSisa = this.waktuPerSoalDetik;
                        }
                    }, 1000);

                    // Reset timer when active question changes
                    this.$watch('activeSoal', () => {
                        this.timerSoalSisa = this.waktuPerSoalDetik;
                    });
                }

                // Anti-kecurangan client-side
                if (_d.preventCopyPaste) {
                    ['copy', 'paste', 'cut'].forEach(ev => {
                        document.addEventListener(ev, e => e.preventDefault());
                    });
                }
                if (_d.preventRightClick) {
                    document.addEventListener('contextmenu', e => e.preventDefault());
                    document.addEventListener('selectstart', e => e.preventDefault());
                }
                if (_d.requireFullscreen) {
                    try { document.documentElement.requestFullscreen?.(); } catch (e) {}
                    document.addEventListener('fullscreenchange', () => {
                        if (!document.fullscreenElement) { this.handleVisibility(); }
                    });
                }
            },

            triggerSectionComplete(timeout = false) {
                if (this._submitted || this.showSectionComplete) return;
                this.sectionCompleteIsTimeout = timeout;
                const canReturn = _d.navigasiSeksi === 'urut_kembali' || _d.navigasiSeksi === 'bebas';
                const isLast = _d.sectionUrutan === _d.totalSeksi;
                if (isLast) {
                    this.sectionCompleteTitle = timeout ? 'Waktu Habis' : 'Selesaikan Ujian?';
                    this.sectionCompleteMsg = timeout
                        ? 'Waktu untuk bagian terakhir telah habis. Ujian akan dikumpulkan.'
                        : 'Anda akan mengakhiri ujian ini. Seluruh jawaban akan dikumpulkan dan tidak dapat diubah lagi.';
                } else if (timeout) {
                    this.sectionCompleteTitle = 'Waktu Bagian Ini Habis';
                    this.sectionCompleteMsg = `Waktu untuk Bagian ${_d.sectionUrutan} (${_d.sectionNama}) telah habis. Klik untuk melanjutkan ke bagian berikutnya.`;
                } else if (canReturn) {
                    this.sectionCompleteTitle = 'Pindah Bagian?';
                    this.sectionCompleteMsg = `Anda akan pindah dari Bagian ${_d.sectionUrutan} (${_d.sectionNama}). Anda masih dapat kembali ke bagian ini kapan saja.`;
                } else {
                    this.sectionCompleteTitle = 'Selesaikan Bagian Ini?';
                    this.sectionCompleteMsg = `Anda akan mengakhiri Bagian ${_d.sectionUrutan} (${_d.sectionNama}). Jawaban tidak dapat diubah setelah ini.`;
                }
                this.showSectionComplete = true;
            },

            async lanjutSeksi() {
                await this._doSeksiRequest(null);
            },

            async kembaliSeksi() {
                if (this.sectionLoading) return;
                const seksiList = _d.seksiList ?? [];
                const prev = seksiList.slice().reverse().find(s => s.urutan < _d.sectionUrutan);
                if (!prev) return;
                await this._doSeksiRequest(prev.id);
            },

            async switchSection(targetId) {
                if (this.sectionLoading || targetId === this.activeSectionId) return;
                await this._doSeksiRequest(targetId);
            },

            async _doSeksiRequest(targetSectionId) {
                this.sectionLoading = true;
                try {
                    const body = {};
                    if (targetSectionId !== null && targetSectionId !== undefined) {
                        body.target_section_id = targetSectionId;
                    }
                    const res = await fetch(`${_d.routes.baseUrl}/${this.attemptId}/seksi/${_d.sectionId}/selesai`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        },
                        body: JSON.stringify(body),
                    });
                    const data = await res.json();
                    if (data.success) {
                        if (data.is_last) {
                            window.location.href = data.redirect_url;
                        } else {
                            window.location.reload();
                        }
                    } else {
                        this.sectionLoading = false;
                    }
                } catch (e) {
                    this.sectionLoading = false;
                }
            },

            toggleTheme() {
                this.lightMode = !this.lightMode;
                localStorage.setItem('examTheme', this.lightMode ? 'light' : 'dark');
            },

            countDijawab() {
                return Object.values(this.states).filter(s => s.dijawab).length;
            },

            countRagu() {
                return Object.values(this.states).filter(s => s.is_ragu).length;
            },

            countBelum() {
                return Object.values(this.states).filter(s => !s.dijawab && !s.is_ragu).length;
            },

            formatWaktu(detik) {
                const h = Math.floor(detik / 3600);
                const m = Math.floor((detik % 3600) / 60);
                const s = detik % 60;
                if (h > 0) {
                    return `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
                }
                return `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
            },

            getSoalButtonClass(questionId) {
                const state = this.states[questionId];
                if (state?.is_ragu) return 'bg-yellow-500 text-slate-900';
                if (state?.dijawab) return 'bg-green-600 text-white';
                return 'bg-slate-600 text-slate-300';
            },

            scrollToSoal(index) {
                // When navigasiPerSoal=maju and per-question timer is active, disallow going back
                if (this.waktuPerSoalDetik > 0 && this.navigasiPerSoal === 'maju' && index < this.activeSoal) {
                    return;
                }
                this.activeSoal = index;
                const main = document.getElementById('soal-container');
                const el   = document.getElementById('soal-' + index);
                if (el && main) {
                    const targetTop = el.getBoundingClientRect().top
                        - main.getBoundingClientRect().top
                        + main.scrollTop
                        - 16;
                    main.scrollTo({ top: targetTop, behavior: 'smooth' });
                }
            },

            async simpanJawaban(attemptId, questionId, jawaban, index) {
                const isRagu = this.states[questionId]?.is_ragu ?? false;
                try {
                    const res = await fetch(_d.routes.jawab, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        },
                        body: JSON.stringify({ attempt_id: attemptId, question_id: questionId, jawaban, is_ragu: isRagu }),
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.soalTerjawab = this.countDijawab();
                        this.sisaDetik = data.sisa_waktu_detik;
                        if (this.states[questionId]) {
                            this.states[questionId].dijawab = jawaban !== null && jawaban !== '';
                            this.soalTerjawab = this.countDijawab();
                        }
                    }
                } catch (e) {}
            },

            async toggleRagu(questionId, isRagu, index) {
                if (this.states[questionId]) this.states[questionId].is_ragu = isRagu;
                try {
                    await fetch(_d.routes.jawab, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        },
                        body: JSON.stringify({
                            attempt_id: this.attemptId,
                            question_id: questionId,
                            jawaban: null,
                            is_ragu: isRagu,
                        }),
                    });
                } catch (e) {}
            },

            togglePgj(attemptId, questionId, optionId, checked, index) {
                let arr = this.pgjStates[questionId] ?? [];
                if (typeof arr === 'string') arr = JSON.parse(arr);
                if (checked) {
                    if (!arr.includes(optionId)) arr.push(optionId);
                } else {
                    arr = arr.filter(id => id !== optionId);
                }
                this.pgjStates[questionId] = arr;
                this.simpanJawaban(attemptId, questionId, arr, index);
            },

            simpanJodoh(attemptId, questionId, matchId, value, index) {
                let obj = this.jodohStates[questionId] ?? {};
                if (typeof obj === 'string') obj = JSON.parse(obj);
                obj[matchId] = value;
                this.jodohStates[questionId] = obj;
                this.simpanJawaban(attemptId, questionId, obj, index);
            },

            async uploadFile(attemptId, questionId, file, index) {
                if (!file) return;
                const formData = new FormData();
                formData.append('attempt_id', attemptId);
                formData.append('question_id', questionId);
                formData.append('file', file);
                formData.append('_token', document.querySelector('meta[name=csrf-token]').content);
                try {
                    const res = await fetch(_d.routes.uploadFile, {
                        method: 'POST',
                        body: formData,
                    });
                    const data = await res.json();
                    if (data.success && this.states[questionId]) {
                        this.states[questionId].dijawab = true;
                        this.soalTerjawab = this.countDijawab();
                    }
                } catch (e) {}
            },

            countTerjawab() {
                return Object.values(this.states).filter(s => s.dijawab).length;
            },

            async pollStatus() {
                try {
                    const res = await fetch(`${_d.routes.baseUrl}/${this.attemptId}/status`);
                    const data = await res.json();
                    if (data.success) {
                        this.sisaDetik = data.sisa_detik;
                        if (_d.hasSections && data.sisa_seksi_detik !== undefined) {
                            this.sisaSeksiDetik = data.sisa_seksi_detik;
                        }
                        if (data.sisa_detik <= 0 && data.redirect_url) {
                            this.doAutoSubmit('timeout');
                        }
                    }
                } catch (e) {}
            },

            handleBlur() {
                // no-op: visibility change is the authoritative event
            },

            handleVisibility() {
                if (document.visibilityState === 'hidden') {
                    this.tabSwitchCount++;
                    this.logTabSwitch();
                    const action = _d.tabSwitchAction ?? 'warn';
                    if (this.tabSwitchCount >= this.maxTabSwitch) {
                        if (_d.autoSubmitOnMaxTab || action === 'submit') {
                            this.doAutoSubmit('tab_switch');
                            return;
                        }
                        this.tabWarningMsg = `Anda telah berpindah tab ${this.tabSwitchCount} kali. Ujian berisiko dikumpulkan otomatis.`;
                    } else {
                        this.tabWarningMsg = `Peringatan: Jangan berpindah tab selama ujian. (${this.tabSwitchCount}/${this.maxTabSwitch})`;
                    }
                    if (action !== 'log') {
                        this.tabWarning = true;
                    }
                }
            },

            logTabSwitch() {
                const url     = `${_d.routes.baseUrl}/${this.attemptId}/log`;
                const payload = JSON.stringify({ detail: `tab_switch:${this.tabSwitchCount}` });
                const token   = document.querySelector('meta[name=csrf-token]').content;

                // sendBeacon fires even when the page is being unloaded — reliable for tab-switch events.
                // Wrap in Blob so the browser sends application/json with the CSRF token in the body.
                const sent = navigator.sendBeacon
                    ? navigator.sendBeacon(url, new Blob([JSON.stringify({ detail: `tab_switch:${this.tabSwitchCount}`, _token: token })], { type: 'application/json' }))
                    : false;

                // Also fire an async fetch to read the authoritative server-side count and act on it.
                // (sendBeacon gives no response; fetch gives us the updated count + submitted flag.)
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: payload,
                    keepalive: true,  // keeps the request alive if the page navigates
                }).then(r => r.ok ? r.json() : null).then(data => {
                    if (!data || !data.success) return;
                    // Sync counter with server-authoritative value
                    this.tabSwitchCount = data.tab_switch_count;
                    if (data.submitted) {
                        this.doAutoSubmit('tab_switch');
                    }
                }).catch(() => {});
            },

            konfirmasiSubmit() {
                this.showConfirmSubmit = true;
            },

            async doSubmit() {
                this.showConfirmSubmit = false;
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `${_d.routes.baseUrl}/${this.attemptId}/submit`;
                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = document.querySelector('meta[name=csrf-token]').content;
                form.appendChild(csrf);
                document.body.appendChild(form);
                form.submit();
            },

            doAutoSubmit(reason) {
                if (this._submitted) return;
                this._submitted = true;
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `${_d.routes.baseUrl}/${this.attemptId}/submit`;
                const csrf = document.createElement('input');
                csrf.type = 'hidden'; csrf.name = '_token';
                csrf.value = document.querySelector('meta[name=csrf-token]').content;
                form.appendChild(csrf);
                document.body.appendChild(form);
                form.submit();
            },
        };
    };
    </script>
    @endpush
</x-ujian-layout>
