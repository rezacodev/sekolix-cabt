{{-- resources/views/filament/resources/laporan-resource/pages/laporan-kecurangan.blade.php --}}
<x-filament-panels::page>

@php
    $statusBadgeColor = [
        \App\Models\ExamAttempt::STATUS_SELESAI        => 'success',
        \App\Models\ExamAttempt::STATUS_TIMEOUT        => 'warning',
        \App\Models\ExamAttempt::STATUS_DISKUALIFIKASI => 'danger',
    ];
@endphp

{{-- ── Summary Cards ────────────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:1rem;">
    @foreach ([
        ['label' => 'Total Peserta',    'value' => $totalPeserta,       'color' => '#111827'],
        ['label' => 'Total Tab Switch', 'value' => $totalTabSwitch,     'color' => '#d97706'],
        ['label' => 'Total Kick',       'value' => $totalKick,          'color' => '#dc2626'],
        ['label' => 'Auto-Submit',      'value' => $totalAutoSubmit,    'color' => '#d97706'],
        ['label' => '≥1 Pelanggaran',   'value' => $pesertaPelanggaran, 'color' => '#dc2626'],
    ] as $stat)
    <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4 text-center">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
        <p class="mt-1 text-3xl font-bold" style="color:{{ $stat['color'] }};">{{ $stat['value'] }}</p>
    </div>
    @endforeach
</div>

{{-- ── Filter ───────────────────────────────────────────────────────────────── --}}
<div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-4">
    <div class="flex flex-wrap items-end gap-4">
        <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2">Tab Switch ≥</label>
            <input type="number" wire:model.live="filter_tab_switch" min="0" value="{{ $filter_tab_switch }}"
                class="w-28 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white px-3 py-2">
        </div>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" wire:model.live="filter_kicked" @checked($filter_kicked)
                class="w-4 h-4 text-danger-600 rounded border-gray-300 dark:border-gray-700">
            <span class="text-sm text-gray-700 dark:text-gray-300">Hanya yang di-Kick</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" wire:model.live="filter_auto_submit" @checked($filter_auto_submit)
                class="w-4 h-4 text-orange-600 rounded border-gray-300 dark:border-gray-700">
            <span class="text-sm text-gray-700 dark:text-gray-300">Hanya Auto-Submit</span>
        </label>
        <span class="ml-auto text-sm text-gray-400">{{ $rekap->count() }} / {{ $totalPeserta }} peserta</span>
    </div>
</div>

{{-- ── Tabel Per Peserta ────────────────────────────────────────────────────── --}}
<div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
    <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <x-heroicon-o-shield-exclamation class="w-5 h-5 text-danger-500"/>
        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Detail Kecurangan Per Peserta</h3>
    </div>

    @if ($rekap->isEmpty())
        <div class="px-6 py-8 text-center text-gray-400">
            Tidak ada data yang sesuai filter.
        </div>
    @else
    <div x-data="{ open: null }" class="divide-y divide-gray-100 dark:divide-gray-800">
        @foreach ($rekap as $i => $row)
        @php
            $total = $row->tab_switch + $row->blur + $row->kick;
            $severity = $total === 0 ? 'gray' : ($total >= 5 ? 'danger' : 'warning');
        @endphp
        <div>
            {{-- Row --}}
            <div
                class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/40 cursor-pointer transition-colors"
                @click="open = (open === {{ $i }}) ? null : {{ $i }}">

                <span class="w-8 text-center text-sm text-gray-400">{{ $i + 1 }}</span>

                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 dark:text-white truncate">{{ $row->nama }}</p>
                    <p class="text-xs text-gray-400">{{ $row->nomor_peserta }} · {{ $row->rombel_nama }}</p>
                </div>

                {{-- Event counts --}}
                <div class="flex items-center gap-2 text-xs font-semibold">
                    <span title="Tab Switch" class="px-2 py-0.5 rounded" style="background:#fef3c7;color:#a16207;">
                        ⇄ {{ $row->tab_switch }}
                    </span>
                    <span title="Blur Focus" class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400">
                        ⊙ {{ $row->blur }}
                    </span>
                    <span title="Kick" class="px-2 py-0.5 rounded" style="background:#fee2e2;color:#b91c1c;">
                        ✖ {{ $row->kick }}
                    </span>
                </div>

                @if($row->auto_submitted)
                <x-filament::badge color="warning" size="sm">Auto-Submit</x-filament::badge>
                @endif

                <div class="flex">
                    <x-filament::badge :color="$statusBadgeColor[$row->status_akhir] ?? 'gray'" size="sm">
                        {{ \App\Models\ExamAttempt::STATUS_LABELS[$row->status_akhir] ?? $row->status_akhir }}
                    </x-filament::badge>
                </div>

                <span class="transition-transform" :class="open === {{ $i }} ? 'rotate-180' : ''">
                    <x-heroicon-o-chevron-down class="w-4 h-4 text-gray-400"/>
                </span>
            </div>

            {{-- Timeline expand --}}
            <div x-show="open === {{ $i }}" x-collapse class="bg-gray-50 dark:bg-gray-800/60 border-t border-gray-100 dark:border-gray-800">
                @if ($row->logs->isEmpty())
                    <p class="px-8 py-4 text-sm text-gray-400">Tidak ada log event untuk peserta ini.</p>
                @else
                    <div class="px-8 py-4 space-y-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4">Timeline Event</p>
                        @foreach ($row->logs as $log)
                        @php
                            $evColor = match($log->event_type) {
                                \App\Models\AttemptLog::EVENT_TAB_SWITCH => '#a16207',
                                \App\Models\AttemptLog::EVENT_KICK       => '#b91c1c',
                                \App\Models\AttemptLog::EVENT_TIMEOUT    => '#ea580c',
                                \App\Models\AttemptLog::EVENT_BLUR       => '#6b7280',
                                default                                  => '#4b5563',
                            };
                        @endphp
                        <div class="flex items-start gap-3 text-sm">
                            <span class="text-xs text-gray-400 w-32 flex-shrink-0 pt-0.5">
                                {{ $log->created_at?->format('H:i:s') ?? '—' }}
                            </span>
                            <span class="font-semibold uppercase text-xs w-24 flex-shrink-0 pt-0.5" style="color:{{ $evColor }};">
                                {{ $log->event_type }}
                            </span>
                            <span class="text-gray-600 dark:text-gray-400 text-xs">
                                {{ $log->detail ?: '—' }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

</x-filament-panels::page>
