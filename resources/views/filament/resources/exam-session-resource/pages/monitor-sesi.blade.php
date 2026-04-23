{{-- resources/views/filament/resources/exam-session-resource/pages/monitor-sesi.blade.php --}}
<x-filament-panels::page>

<div wire:poll.10000ms class="flex flex-col gap-4">

{{-- ── Stat Cards ───────────────────────────────────────────────────────────── --}}
<div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 1rem;">
    @foreach ([
        ['label' => 'Total Peserta',     'value' => $stats['total'],          'color' => '#111827'],
        ['label' => 'Sedang Mengerjakan','value' => $stats['sedang'],         'color' => '#2563eb'],
        ['label' => 'Selesai',           'value' => $stats['selesai'],        'color' => '#16a34a'],
        ['label' => 'Belum Mulai',       'value' => $stats['belum'],          'color' => '#d97706'],
        ['label' => 'Diskualifikasi',    'value' => $stats['diskualifikasi'], 'color' => '#dc2626'],
    ] as $stat)
    <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4 text-center">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
        <p class="mt-1 text-3xl font-bold" style="color:{{ $stat['color'] }};">{{ $stat['value'] }}</p>
    </div>
    @endforeach
</div>

{{-- ── Session Info Strip ───────────────────────────────────────────────────── --}}
<div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 px-6 py-3">
    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-500 dark:text-gray-400">
        <span class="flex items-center gap-1.5">
            <x-heroicon-o-academic-cap class="w-4 h-4"/>
            <span class="font-medium text-gray-900 dark:text-white">{{ $session->package?->nama ?? '—' }}</span>
        </span>
        <span class="text-gray-300">|</span>
        <span class="flex items-center gap-1.5">
            <x-heroicon-o-clock class="w-4 h-4"/>
            {{ $session->waktu_mulai?->format('d M Y H:i') }} – {{ $session->waktu_selesai?->format('H:i') }}
        </span>
        <span class="text-gray-300">|</span>
        @php
            $statusBg = match($session->status) {
                \App\Models\ExamSession::STATUS_AKTIF      => 'background:#dcfce7;color:#15803d;',
                \App\Models\ExamSession::STATUS_SELESAI    => 'background:#dbeafe;color:#1d4ed8;',
                \App\Models\ExamSession::STATUS_DIBATALKAN => 'background:#fee2e2;color:#b91c1c;',
                default                                     => 'background:#f3f4f6;color:#4b5563;',
            };
        @endphp
        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold" style="{{ $statusBg }}">
            {{ \App\Models\ExamSession::STATUS_LABELS[$session->status] ?? $session->status }}
        </span>
        <span class="ml-auto text-xs text-gray-400">Auto-refresh 10 detik &middot; {{ now()->format('H:i:s') }}</span>
    </div>
</div>

{{-- ── Livescore Link Card ───────────────────────────────────────────────────── --}}
@if ($livescoreUrl)
<div class="rounded-xl px-6 py-4" style="background:#eef2ff;outline:1px solid #c7d2fe;">
    <div class="flex flex-wrap items-center gap-3">
        <x-heroicon-o-globe-alt class="w-4 h-4 shrink-0" style="color:#6366f1;"/>
        <span class="text-xs font-semibold uppercase tracking-wide shrink-0" style="color:#4f46e5;">Link Livescore Publik</span>
        <div class="flex-1 min-w-0">
            <code class="block w-full text-xs font-mono rounded px-3 py-1.5 truncate" style="color:#3730a3;background:#e0e7ff;" id="livescore-url">{{ $livescoreUrl }}</code>
        </div>
        <button
            type="button"
            onclick="navigator.clipboard.writeText('{{ $livescoreUrl }}').then(()=>{this.textContent='Tersalin!';setTimeout(()=>this.textContent='Salin Link',2000)})"
            class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors" style="background:#4f46e5;color:white;">
            <x-heroicon-o-clipboard-document class="w-3.5 h-3.5"/>
            Salin Link
        </button>
        <a href="{{ $livescoreUrl }}" target="_blank"
           class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold transition-colors" style="color:#3730a3;outline:1px solid #a5b4fc;">
            <x-heroicon-o-arrow-top-right-on-square class="w-3.5 h-3.5"/>
            Buka
        </a>
    </div>
</div>
@endif

{{-- ── Participant Table ─────────────────────────────────────────────────────── --}}
<div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
    <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <x-heroicon-o-table-cells class="w-5 h-5 text-gray-500 dark:text-gray-400"/>
        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Daftar Peserta</h3>
        <span class="ml-auto text-sm text-gray-400">{{ $list->count() }} peserta terdaftar</span>
    </div>

    @if ($list->isEmpty())
        <div class="px-6 py-8 text-center text-gray-400">
            Belum ada peserta yang didaftarkan pada sesi ini.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800 text-left">
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 w-10 text-center">No</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Nama</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">No. Peserta</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Rombel</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" style="min-width:10rem">Progress</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 text-center" style="min-width:7rem">Sisa Waktu</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 text-center">Tab Switch</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 text-right">Nilai</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 text-center">Rank</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($list as $idx => $p)
                    @php
                        $statusClass = match($p->participant_status) {
                            \App\Models\ExamSessionParticipant::STATUS_SEDANG         => 'background:#dbeafe;color:#1d4ed8;',
                            \App\Models\ExamSessionParticipant::STATUS_SELESAI        => 'background:#dcfce7;color:#15803d;',
                            \App\Models\ExamSessionParticipant::STATUS_DISKUALIFIKASI => 'background:#fee2e2;color:#b91c1c;',
                            default                                                    => 'background:#f3f4f6;color:#4b5563;',
                        };
                        $statusLabel   = \App\Models\ExamSessionParticipant::STATUS_LABELS[$p->participant_status] ?? $p->participant_status;
                        $progressPct   = ($p->total_soal > 0) ? round($p->dijawab / $p->total_soal * 100) : 0;
                        $canKick       = !in_array($p->participant_status, [
                            \App\Models\ExamSessionParticipant::STATUS_DISKUALIFIKASI,
                            \App\Models\ExamSessionParticipant::STATUS_SELESAI,
                        ]);
                        $nilaiColor = match(true) {
                            $p->nilai_sementara === null                => '#9ca3af',
                            $p->nilai_sementara >= 75                  => '#16a34a',
                            $p->nilai_sementara >= 50                  => '#d97706',
                            default                                     => '#dc2626',
                        };
                        $rankDisplay = match($p->rank) {
                            1       => '🥇',
                            2       => '🥈',
                            3       => '🥉',
                            null    => '—',
                            default => '#' . $p->rank,
                        };
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="px-4 py-3 text-center text-gray-400">{{ $idx + 1 }}</td>

                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $p->nama }}</td>

                        <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ $p->nomor_peserta }}</td>

                        <td class="px-4 py-3 text-gray-500">{{ $p->rombel }}</td>

                        <td class="px-4 py-3">
                            <div class="flex">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold" style="{{ $statusClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </div>
                        </td>

                        <td class="px-4 py-3">
                            @if ($p->total_soal > 0)
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-1.5 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                                        <div class="h-full rounded-full bg-primary-500 transition-all duration-500"
                                             @style(['width: ' . $progressPct . '%'])></div>
                                    </div>
                                    <span class="text-xs text-gray-500 shrink-0 tabular-nums">{{ $p->dijawab }}/{{ $p->total_soal }}</span>
                                </div>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>

                        <td class="px-4 py-3 text-center font-mono text-xs">
                            @if ($p->sisa_waktu === null)
                                <span class="text-gray-300">—</span>
                            @elseif ($p->sisa_waktu <= 0)
                                <span class="text-danger-600 font-semibold text-xs">Waktu Habis</span>
                            @else
                                @php
                                    $menit = intdiv((int)$p->sisa_waktu, 60);
                                    $detik = (int)$p->sisa_waktu % 60;
                                @endphp
                                <span class="{{ $p->sisa_waktu <= 300 ? 'font-bold' : 'text-gray-700 dark:text-gray-200' }}" style="{{ $p->sisa_waktu <= 300 ? 'color:#d97706;' : '' }}">
                                    {{ str_pad($menit, 2, '0', STR_PAD_LEFT) }}:{{ str_pad($detik, 2, '0', STR_PAD_LEFT) }}
                                </span>
                            @endif
                        </td>

                        <td class="px-4 py-3 text-center">
                            @if ($p->tab_switch > 0)
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold" style="background:#fee2e2;color:#b91c1c;">
                                    {{ $p->tab_switch }}
                                </span>
                            @else
                                <span class="text-gray-300">0</span>
                            @endif
                        </td>

                        <td class="px-4 py-3 text-right">
                            <span class="font-bold text-base" style="color:{{ $nilaiColor }};">
                                {{ $p->nilai_sementara !== null ? number_format((float)$p->nilai_sementara, 1) : '—' }}
                            </span>
                        </td>

                        <td class="px-4 py-3 text-center text-base">
                            {{ $rankDisplay }}
                        </td>

                        <td class="px-4 py-3 text-center">
                            @if ($canKick)
                                <button
                                    wire:click="confirmKick({{ $p->user_id }}, '{{ addslashes($p->nama) }}')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold transition-colors" style="background:#fef2f2;color:#b91c1c;outline:1px solid #fecaca;">
                                    <x-heroicon-o-x-mark class="w-3.5 h-3.5"/>
                                    Paksa Keluar
                                </button>
                            @else
                                <span class="text-gray-300 text-xs">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

</div>{{-- /wire:poll --}}

{{-- ── Catatan Pengawas ─────────────────────────────────────────────────────── --}}
<div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden mt-4">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-white/10 flex items-center gap-2">
        <x-heroicon-o-pencil-square class="w-4 h-4 text-gray-400"/>
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Catatan Pengawas</h3>
    </div>

    {{-- Daftar catatan --}}
    <div class="divide-y divide-gray-50 dark:divide-white/5 max-h-64 overflow-y-auto">
        @forelse ($notes as $note)
        <div class="px-6 py-3">
            <div class="flex items-center gap-2 mb-0.5">
                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ $note->author?->name ?? '—' }}</span>
                <span class="text-xs text-gray-400">{{ $note->created_at?->format('H:i, d M Y') }}</span>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-line">{{ $note->catatan }}</p>
        </div>
        @empty
        <div class="px-6 py-6 text-center text-sm text-gray-400 dark:text-gray-500">
            Belum ada catatan.
        </div>
        @endforelse
    </div>

    {{-- Form tambah catatan --}}
    <div class="px-6 py-4 border-t border-gray-100 dark:border-white/10">
        <textarea
            wire:model="newNote"
            rows="2"
            placeholder="Tulis catatan pengawas…"
            class="w-full rounded-lg border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-800 text-sm text-gray-700 dark:text-gray-200 placeholder-gray-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none"></textarea>
        <div class="mt-2 flex justify-end">
            <button
                wire:click="addNote"
                wire:loading.attr="disabled"
                type="button"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold disabled:opacity-60 transition-colors">
                <span wire:loading wire:target="addNote" class="animate-spin inline-block w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full"></span>
                Simpan Catatan
            </button>
        </div>
    </div>
</div>

{{-- ── Kick Confirmation Modal ─────────────────────────────────────────────── --}}
<div
    x-data="{ open: $wire.entangle('showKickModal') }"
    x-show="open"
    x-cloak
    x-trap.noscroll="open"
    class="fixed inset-0 z-50 flex items-center justify-center p-4">

    {{-- Backdrop --}}
    <div
        class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"
        x-on:click="$wire.resetKick()"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">
    </div>

    {{-- Modal panel --}}
    <div
        class="relative z-10 w-full max-w-sm rounded-2xl bg-white dark:bg-gray-900 shadow-2xl ring-1 ring-gray-950/10 dark:ring-white/10 p-6"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95">

        {{-- Icon --}}
        <div class="flex items-center justify-center w-12 h-12 rounded-full mx-auto mb-4" style="background:#fee2e2;">
            <x-heroicon-o-x-circle class="w-6 h-6 text-danger-600 dark:text-danger-400"/>
        </div>

        {{-- Title --}}
        <h3 class="text-base font-bold text-gray-900 dark:text-white text-center mb-2">Paksa Keluar Peserta?</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 text-center mb-2">
            <span class="font-semibold text-gray-900 dark:text-white">{{ $kickNama }}</span>
        </p>
        <p class="text-xs text-gray-400 text-center mb-6">
            Status peserta akan menjadi <span class="text-danger-600 dark:text-danger-400 font-semibold">Diskualifikasi</span> dan tidak dapat dikembalikan.
        </p>

        {{-- Actions --}}
        <div class="flex gap-3">
            <button
                type="button"
                wire:click="resetKick"
                class="flex-1 inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                Batal
            </button>
            <button
                type="button"
                wire:click="doKick"
                wire:loading.attr="disabled"
                class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold bg-danger-600 text-white hover:bg-danger-700 disabled:opacity-60 transition-colors">
                <span wire:loading wire:target="doKick" class="animate-spin inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full"></span>
                Ya, Paksa Keluar
            </button>
        </div>
    </div>
</div>

</x-filament-panels::page>
