{{--
    Partial: peserta/_soal-card.blade.php
    Variables expected: $aq (AttemptQuestion), $q (Question), $attempt (ExamAttempt), $loop (Blade $loop)
--}}

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
    {{-- ── Audio Player ── --}}
    @if ($q->audio_url)
    @php
        $audioSrc = \Illuminate\Support\Str::startsWith($q->audio_url, ['http://', 'https://'])
            ? $q->audio_url
            : \Illuminate\Support\Facades\Storage::disk('public')->url($q->audio_url);
    @endphp
    <div x-data="{
            playCount: {{ $aq->audio_play_count ?? 0 }},
            playLimit: {{ $q->audio_play_limit ?? 0 }},
            canPlay() { return this.playLimit === 0 || this.playCount < this.playLimit; },
            async onPlay() {
                if (!this.canPlay()) { this.$refs.audioEl.pause(); return; }
                try {
                    const r = await fetch('/ujian/audio/{{ $aq->question_id }}/play', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ attempt_id: {{ $attempt->id }} })
                    });
                    const d = await r.json();
                    if (d.allowed) { this.playCount++; }
                    else { this.$refs.audioEl.pause(); }
                } catch (e) { this.$refs.audioEl.pause(); }
            }
        }"
        class="mb-4 p-3 rounded-xl border border-slate-600 bg-slate-800/60">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-indigo-400 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m9 9 10.5-3m0 6.553v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 1 1-.99-3.467l2.31-.66a2.25 2.25 0 0 0 1.632-2.163Zm0 0V2.25L9 5.25v10.303m0 0v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 0 1-.99-3.467l2.31-.66A2.25 2.25 0 0 0 9 15.553Z" />
            </svg>
            <audio x-ref="audioEl"
                src="{{ $audioSrc }}"
                controls
                class="flex-1 h-9"
                :class="!canPlay() ? 'opacity-40 pointer-events-none' : ''"
                @play="onPlay()"
                @if($q->audio_auto_play) x-init="$nextTick(() => { if (canPlay()) $refs.audioEl.play(); })" @endif
            ></audio>
            @if ($q->audio_play_limit > 0)
            <span class="text-xs shrink-0"
                :class="canPlay() ? 'text-slate-400' : 'text-red-400'"
                x-text="canPlay() ? (playLimit - playCount) + ' kali tersisa' : 'Batas tercapai'"></span>
            @endif
        </div>
    </div>
    @endif

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
                        @change="simpanJawaban({{ $attempt->id }}, {{ $aq->question_id }}, '{{ $opt->id }}', {{ $loop->index }})"
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
                        @change="togglePgj({{ $attempt->id }}, {{ $aq->question_id }}, {{ $opt->id }}, $event.target.checked, {{ $loop->index }})"
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
                        @change="simpanJodoh({{ $attempt->id }}, {{ $aq->question_id }}, {{ $match->id }}, $event.target.value, {{ $loop->index }})"
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
                <p class="text-xs text-green-400 mt-1">✓ File sudah diunggah</p>
            @endif
        </div>

    {{-- ── BS (Benar/Salah) ── --}}
    @elseif ($q->tipe === 'BS')
        <div class="flex gap-4">
            <label class="exam-option flex items-center gap-3 px-5 py-3 rounded-lg cursor-pointer transition-colors
                hover:bg-slate-700 has-[:checked]:bg-indigo-900/50 has-[:checked]:border-indigo-500 border border-transparent">
                <input type="radio"
                    name="jawaban_{{ $aq->question_id }}"
                    value="B"
                    class="accent-indigo-500"
                    @change="simpanJawaban({{ $attempt->id }}, {{ $aq->question_id }}, 'B', {{ $loop->index }})"
                    @if ($aq->jawaban_peserta === 'B') checked @endif
                >
                <span class="text-gray-200 text-sm font-medium">Benar</span>
            </label>
            <label class="exam-option flex items-center gap-3 px-5 py-3 rounded-lg cursor-pointer transition-colors
                hover:bg-slate-700 has-[:checked]:bg-red-900/50 has-[:checked]:border-red-500 border border-transparent">
                <input type="radio"
                    name="jawaban_{{ $aq->question_id }}"
                    value="S"
                    class="accent-red-500"
                    @change="simpanJawaban({{ $attempt->id }}, {{ $aq->question_id }}, 'S', {{ $loop->index }})"
                    @if ($aq->jawaban_peserta === 'S') checked @endif
                >
                <span class="text-gray-200 text-sm font-medium">Salah</span>
            </label>
        </div>

    {{-- ── CLOZE (isian dalam teks) ── --}}
    @elseif ($q->tipe === 'CLOZE')
        @php
            $clozeJawaban = json_decode($aq->jawaban_peserta ?: '{}', true) ?: [];
        @endphp
        <div x-data="{
                cloze: {{ json_encode($clozeJawaban) }},
                save() {
                    simpanJawaban({{ $attempt->id }}, {{ $aq->question_id }}, JSON.stringify(this.cloze), {{ $loop->index }});
                }
            }"
            class="space-y-3">
            @foreach ($q->clozeBlank as $blank)
                <div class="flex items-center gap-3">
                    <span class="text-slate-400 text-sm w-8 shrink-0">[{{ $blank->urutan }}]</span>
                    @if ($blank->placeholder)
                        <span class="text-slate-400 text-xs italic shrink-0">({{ $blank->placeholder }})</span>
                    @endif
                    <input type="text"
                        class="exam-input flex-1 bg-slate-700 border-slate-600 rounded-lg text-gray-200 text-sm
                            focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Isi blank {{ $blank->urutan }}…"
                        x-model="cloze['{{ $blank->urutan }}']"
                        @input.debounce.800ms="save()"
                    >
                </div>
            @endforeach
        </div>
    @endif
</div>
