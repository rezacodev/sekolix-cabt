<x-ujian-layout>
    <x-slot name="title">{{ $session->nama_sesi }}</x-slot>

    @php
        $package = $session->package;
        $totalSoal = $soalList->count();
        $soalCount = $soalList->filter(fn ($q) => $q->isDijawab())->count();
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
                    <p class="text-xs text-slate-400 exam-hd-sub">{{ auth()->user()->name }}</p>
                </div>

                {{-- Timer --}}
                <div class="flex items-center gap-2 shrink-0">
                    <div class="text-center">
                        <p class="text-xs text-slate-400 mb-0.5 exam-hd-sub">Sisa Waktu</p>
                        <div
                            class="font-mono text-lg font-bold tabular-nums px-3 py-1 rounded-md transition-colors exam-timer"
                            :class="sisaDetik <= 300 ? 'bg-red-600 text-white animate-pulse' : 'bg-slate-700 text-white'"
                            x-text="formatWaktu(sisaDetik)"
                        ></div>
                    </div>
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
                <form @submit.prevent="konfirmasiSubmit()" class="shrink-0">
                    <button type="submit"
                        class="bg-green-600 hover:bg-green-500 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
                        Selesai
                    </button>
                </form>
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
                    <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider exam-sb-title">Navigasi Soal</p>
                </div>
                <div class="flex-1 overflow-y-auto p-3 grid grid-cols-5 gap-1.5 content-start">
                    @foreach ($soalList as $aq)
                        <button
                            @click="scrollToSoal({{ $loop->index }})"
                            :class="getSoalButtonClass({{ $aq->question_id }})"
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

                {{-- ── WATERMARK peserta (fixed over soal area, pointer-events-none) ── --}}
                <div aria-hidden="true"
                    class="pointer-events-none select-none fixed inset-0 z-10 overflow-hidden"
                    style="user-select:none;-webkit-user-select:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%">
                        <defs>
                            <pattern id="wm" x="0" y="0" width="340" height="140" patternUnits="userSpaceOnUse" patternTransform="rotate(-35)">
                                <text x="10" y="60" font-family="sans-serif" font-size="13" fill="rgba(255,255,255,0.045)" font-weight="600">
                                    {{ auth()->user()->name }}
                                </text>
                                <text x="10" y="82" font-family="sans-serif" font-size="11" fill="rgba(255,255,255,0.035)">
                                    {{ auth()->user()->nomor_peserta ?? '' }}
                                </text>
                            </pattern>
                        </defs>
                        <rect width="100%" height="100%" fill="url(#wm)"/>
                    </svg>
                </div>

                <div class="max-w-3xl mx-auto space-y-6 relative z-20">
                    @foreach ($soalList as $aq)
                        @php $q = $aq->question; @endphp
                        <div
                            id="soal-{{ $loop->index }}"
                            class="bg-slate-800 rounded-xl border border-slate-700 overflow-hidden exam-card"
                            :class="activeSoal === {{ $loop->index }} ? 'ring-2 ring-indigo-500' : ''"
                        >
                            {{-- Header soal --}}
                            <div class="flex items-center justify-between px-5 py-3 bg-slate-750 border-b border-slate-700 exam-card-hd">
                                <span class="text-slate-300 text-sm font-medium exam-card-label">Soal {{ $loop->iteration }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-slate-500 uppercase exam-card-tipe">{{ $q->tipe }}</span>
                                    {{-- Ragu-ragu toggle --}}
                                    <button type="button"
                                        @click.prevent="toggleRagu({{ $aq->question_id }}, !(states[{{ $aq->question_id }}]?.is_ragu), {{ $loop->index }})"
                                        class="flex items-center gap-1.5 focus:outline-none"
                                        title="Tandai ragu-ragu">
                                        <span class="w-4 h-4 rounded transition-colors"
                                            :class="states[{{ $aq->question_id }}]?.is_ragu ? 'bg-yellow-500' : 'bg-slate-600'">
                                        </span>
                                        <span class="text-xs text-slate-400 exam-card-label">Ragu</span>
                                    </button>
                                </div>
                            </div>

                            {{-- Konten soal --}}
                            <div class="p-5">
                                <div class="text-gray-100 mb-5 leading-relaxed prose prose-invert prose-sm max-w-none exam-soal-text">
                                    {!! $q->teks_soal !!}
                                </div>

                                {{-- ── PG / PG_BOBOT ── --}}
                                @if (in_array($q->tipe, ['PG', 'PG_BOBOT']))
                                    <div class="space-y-2">
                                        @foreach ($q->options as $opt)
                                            <label class="exam-option flex items-start gap-3 p-3 rounded-lg cursor-pointer transition-colors
                                                hover:bg-slate-700 has-[:checked]:bg-indigo-900/50 has-[:checked]:border-indigo-500 border border-transparent">
                                                <input type="radio"
                                                    name="jawaban_{{ $aq->question_id }}"
                                                    value="{{ $opt->id }}"
                                                    class="mt-0.5 accent-indigo-500"
                                                    @change="simpanJawaban({{ $attempt->id }}, {{ $aq->question_id }}, '{{ $opt->id }}', {{ $loop->parent->index }})"
                                                    @if ($aq->jawaban_peserta == $opt->id) checked @endif
                                                >
                                                <span class="text-gray-200 text-sm leading-relaxed exam-opt-text">{!! $opt->teks_opsi !!}</span>
                                            </label>
                                        @endforeach
                                    </div>

                                {{-- ── PGJ (pilih ganda jamak) ── --}}
                                @elseif ($q->tipe === 'PGJ')
                                    @php $jawabanPgj = json_decode($aq->jawaban_peserta ?: '[]', true) ?: []; @endphp
                                    <div class="space-y-2">
                                        @foreach ($q->options as $opt)
                                            <label class="exam-option flex items-start gap-3 p-3 rounded-lg cursor-pointer transition-colors
                                                hover:bg-slate-700 has-[:checked]:bg-indigo-900/50 has-[:checked]:border-indigo-500 border border-transparent">
                                                <input type="checkbox"
                                                    name="jawaban_{{ $aq->question_id }}[]"
                                                    value="{{ $opt->id }}"
                                                    class="mt-0.5 accent-indigo-500"
                                                    @change="togglePgj({{ $attempt->id }}, {{ $aq->question_id }}, {{ $opt->id }}, $event.target.checked, {{ $loop->parent->index }})"
                                                    @if (in_array($opt->id, $jawabanPgj)) checked @endif
                                                >
                                                <span class="text-gray-200 text-sm leading-relaxed exam-opt-text">{!! $opt->teks_opsi !!}</span>
                                            </label>
                                        @endforeach
                                    </div>

                                {{-- ── JODOH ── --}}
                                @elseif ($q->tipe === 'JODOH')
                                    @php $jawabanJodoh = json_decode($aq->jawaban_peserta ?: '{}', true) ?: []; @endphp
                                    <div class="space-y-3">
                                        @foreach ($q->matches as $match)
                                            <div class="flex items-center gap-3">
                                                <span class="text-gray-200 text-sm flex-1 exam-opt-text">{!! $match->premis !!}</span>
                                                <select
                                                    class="exam-input bg-slate-700 border-slate-600 rounded-lg text-sm text-gray-200 flex-1
                                                        focus:ring-indigo-500 focus:border-indigo-500"
                                                    @change="simpanJodoh({{ $attempt->id }}, {{ $aq->question_id }}, {{ $match->id }}, $event.target.value, {{ $loop->parent->index }})"
                                                >
                                                    <option value="">-- Pilih --</option>
                                                    @foreach ($q->matches as $opt)
                                                        <option value="{{ $opt->id }}"
                                                            @if (($jawabanJodoh[$match->id] ?? null) == $opt->id) selected @endif>
                                                            {{ $opt->respon }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endforeach
                                    </div>

                                {{-- ── ISIAN ── --}}
                                @elseif ($q->tipe === 'ISIAN')
                                    <input type="text"
                                        class="exam-input w-full bg-slate-700 border-slate-600 rounded-lg text-gray-200 text-sm
                                            focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Tulis jawaban Anda…"
                                        value="{{ $aq->jawaban_peserta ?? '' }}"
                                        @input.debounce.800ms="simpanJawaban({{ $attempt->id }}, {{ $aq->question_id }}, $event.target.value, {{ $loop->index }})"
                                    >

                                {{-- ── URAIAN ── --}}
                                @elseif ($q->tipe === 'URAIAN')
                                    <textarea
                                        rows="5"
                                        class="exam-input w-full bg-slate-700 border-slate-600 rounded-lg text-gray-200 text-sm
                                            focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Tulis jawaban Anda…"
                                        @input.debounce.800ms="simpanJawaban({{ $attempt->id }}, {{ $aq->question_id }}, $event.target.value, {{ $loop->index }})"
                                    >{{ $aq->jawaban_peserta ?? '' }}</textarea>
                                    {{-- Upload file --}}
                                    <div class="mt-3">
                                        <label class="text-xs text-slate-400">Atau lampirkan file (PDF/JPG/PNG, maks 5MB):</label>
                                        <input type="file" accept=".pdf,.jpg,.jpeg,.png"
                                            class="mt-1 w-full text-sm text-slate-300 file:mr-3 file:py-1.5 file:px-3
                                                file:rounded file:border-0 file:text-xs file:bg-indigo-600 file:text-white
                                                hover:file:bg-indigo-500 cursor-pointer"
                                            @change="uploadFile({{ $attempt->id }}, {{ $aq->question_id }}, $event.target.files[0], {{ $loop->index }})"
                                        >
                                        @if ($aq->jawaban_file)
                                            <p class="text-xs text-green-400 mt-1">
                                                ✓ File sudah diunggah
                                            </p>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    {{-- Bottom submit button --}}
                    <div class="pb-8 text-center">
                        <button @click="konfirmasiSubmit()"
                            class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-500 text-white font-semibold
                                px-8 py-3 rounded-xl transition-colors text-base">
                            ✓ Selesaikan Ujian
                        </button>
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
                    <p class="text-sm font-semibold text-white">Navigasi Soal</p>
                    <p class="text-xs text-slate-400 mt-0.5">
                        <span class="font-bold text-white" x-text="soalTerjawab"></span> / {{ $totalSoal }} terjawab
                    </p>
                </div>
                <button @click="showMobileNav = false" class="p-1.5 rounded-lg hover:bg-slate-700 text-slate-400 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Soal grid --}}
            <div class="flex-1 overflow-y-auto p-4 grid grid-cols-8 sm:grid-cols-10 gap-2">
                @foreach ($soalList as $aq)
                    <button
                        @click="scrollToSoal({{ $loop->index }}); showMobileNav = false"
                        :class="getSoalButtonClass({{ $aq->question_id }})"
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
            soalTerjawab: _d.soalTerjawab,
            totalSoal: _d.totalSoal,
            activeSoal: 0,
            tabWarning: false,
            tabWarningMsg: '',
            showConfirmSubmit: false,
            tabSwitchCount: _d.tabSwitchCount,
            maxTabSwitch: _d.maxTabSwitch,
            lightMode: localStorage.getItem('examTheme') === 'light',
            showMobileNav: false,
            states: _d.states,
            pgjStates: _d.pgjStates,
            jodohStates: _d.jodohStates,

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
                setInterval(() => this.pollStatus(), 30000);

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
                this.activeSoal = index;
                const main = document.getElementById('soal-container');
                const el = document.getElementById('soal-' + index);
                if (el && main) {
                    const top = el.getBoundingClientRect().top
                        - main.getBoundingClientRect().top
                        + main.scrollTop
                        - 16;
                    main.scrollTo({ top, behavior: 'smooth' });
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
                        this.soalTerjawab = data.soal_terjawab;
                        this.sisaDetik = data.sisa_waktu_detik;
                        if (this.states[questionId]) {
                            this.states[questionId].dijawab = jawaban !== null && jawaban !== '';
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
