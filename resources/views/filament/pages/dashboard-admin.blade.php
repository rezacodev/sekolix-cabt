{{-- resources/views/filament/pages/dashboard-admin.blade.php --}}
<x-filament-panels::page>

@php
    /** @var \App\Models\User $user */
    $isAdmin = $user->level >= \App\Models\User::LEVEL_ADMIN;
    $hour    = now()->hour;
    $greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));
@endphp

{{-- ══════════════════════════════════════════════════════════════════════════
     WELCOME HEADER
══════════════════════════════════════════════════════════════════════════ --}}
<div class="rounded-xl bg-gradient-to-br from-primary-600 to-primary-700 px-6 py-5 text-white shadow-md">
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <div>
            <p class="text-sm font-medium text-primary-200">{{ $greeting }},</p>
            <h2 class="text-xl font-bold mt-0.5">{{ $user->name }}</h2>
            <p class="text-xs text-primary-200 mt-1">{{ \App\Models\User::levelLabels()[$user->level] ?? '' }} &middot; {{ now()->translatedFormat('l, d F Y') }}</p>
        </div>
        @if ($isAdmin)
        <div class="flex items-center gap-3 text-xs text-primary-100">
            <x-heroicon-o-shield-check class="w-5 h-5 text-primary-200 shrink-0"/>
            <span>Panel {{ \App\Models\User::levelLabels()[$user->level] }}</span>
        </div>
        @else
        <a href="{{ \App\Filament\Pages\DashboardGuru::getUrl() }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-white/20 hover:bg-white/30 text-sm font-semibold text-white transition-colors">
            <x-heroicon-o-presentation-chart-line class="w-4 h-4"/>
            Dashboard Nilai Rombel
        </a>
        @endif
    </div>
</div>

@if ($isAdmin)
{{-- ══════════════════════════════════════════════════════════════════════════
     ADMIN / SUPER-ADMIN VIEW
══════════════════════════════════════════════════════════════════════════ --}}

{{-- ── Stat Cards ─────────────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:1rem;">
    @php
        $stats = [
            ['label' => 'Total Peserta',  'value' => $totalPeserta,     'icon' => 'heroicon-o-users',              'color' => 'text-blue-600 dark:text-blue-400',    'bg' => 'bg-blue-50 dark:bg-blue-950/30'],
            ['label' => 'Total Guru',     'value' => $totalGuru,        'icon' => 'heroicon-o-academic-cap',       'color' => 'text-purple-600 dark:text-purple-400', 'bg' => 'bg-purple-50 dark:bg-purple-950/30'],
            ['label' => 'Rombel Aktif',   'value' => $totalRombelAktif, 'icon' => 'heroicon-o-user-group',         'color' => 'text-indigo-600 dark:text-indigo-400', 'bg' => 'bg-indigo-50 dark:bg-indigo-950/30'],
            ['label' => 'Bank Soal',      'value' => number_format($totalSoal), 'icon' => 'heroicon-o-document-text', 'color' => 'text-amber-600 dark:text-amber-400', 'bg' => 'bg-amber-50 dark:bg-amber-950/30'],
            ['label' => 'Paket Ujian',    'value' => $totalPaket,       'icon' => 'heroicon-o-folder-open',        'color' => 'text-cyan-600 dark:text-cyan-400',    'bg' => 'bg-cyan-50 dark:bg-cyan-950/30'],
            ['label' => 'Total Sesi',     'value' => $totalSesi,        'icon' => 'heroicon-o-play-circle',        'color' => 'text-green-600 dark:text-green-400',  'bg' => 'bg-green-50 dark:bg-green-950/30'],
        ];
    @endphp
    @foreach ($stats as $stat)
    <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 px-4 py-4">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg {{ $stat['bg'] }}">
                <x-dynamic-component :component="$stat['icon']" class="w-4 h-4 {{ $stat['color'] }}"/>
            </span>
        </div>
        <p class="text-2xl font-bold {{ $stat['color'] }}">{{ $stat['value'] }}</p>
    </div>
    @endforeach
</div>

{{-- ── Sesi Aktif Sekarang ─────────────────────────────────────────────────── --}}
<div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
    <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100 dark:border-white/10">
        <div class="flex items-center gap-2">
            @if ($sesiAktif->isNotEmpty())
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-success-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-success-500"></span>
                </span>
            @endif
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                Sesi Aktif Sekarang
                @if ($sesiAktif->isNotEmpty())
                    <span class="ml-1.5 inline-flex items-center rounded-full bg-success-100 dark:bg-success-900/30 text-success-700 dark:text-success-400 text-xs font-semibold px-2 py-0.5">{{ $sesiAktif->count() }}</span>
                @endif
            </h3>
        </div>
        <a href="{{ \App\Filament\Resources\ExamSessionResource::getUrl() }}"
           class="text-xs text-primary-600 dark:text-primary-400 hover:underline font-medium">
            Kelola Sesi →
        </a>
    </div>

    @if ($sesiAktif->isEmpty())
        <div class="px-5 py-10 text-center">
            <x-heroicon-o-play-circle class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600 mb-2"/>
            <p class="text-sm text-gray-400 dark:text-gray-500">Tidak ada sesi ujian yang sedang aktif.</p>
        </div>
    @else
        <div class="divide-y divide-gray-100 dark:divide-white/5">
            @foreach ($sesiAktif as $s)
            <div class="flex items-center gap-4 px-5 py-4 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-900 dark:text-white truncate">{{ $s->nama }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ $s->paket }} &middot; oleh <span class="font-medium">{{ $s->guru }}</span>
                        &middot; mulai {{ $s->waktu_mulai?->format('H:i') }}
                    </p>
                </div>
                <div class="flex items-center gap-5 shrink-0">
                    <div class="text-center">
                        <p class="text-lg font-bold text-warning-600 dark:text-warning-400">{{ $s->sedang_mengerjakan }}</p>
                        <p class="text-xs text-gray-400">Mengerjakan</p>
                    </div>
                    <div class="text-center">
                        <p class="text-lg font-bold text-success-600 dark:text-success-400">{{ $s->sudah_selesai }}</p>
                        <p class="text-xs text-gray-400">Selesai</p>
                    </div>
                    <div class="text-center">
                        <p class="font-mono text-sm font-bold text-gray-700 dark:text-gray-300 tracking-widest">{{ $s->token }}</p>
                        <p class="text-xs text-gray-400">Token</p>
                    </div>
                    <a href="{{ \App\Filament\Resources\ExamSessionResource\Pages\MonitorSesi::getUrl(['record' => $s->id]) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-success-50 text-success-700 hover:bg-success-100 dark:bg-success-900/20 dark:text-success-400 ring-1 ring-success-200 dark:ring-success-800 transition-colors whitespace-nowrap">
                        <x-heroicon-o-eye class="w-3.5 h-3.5"/>
                        Monitor
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>

{{-- ── Baris bawah: Sesi Terbaru + Pengumuman ─────────────────────────────── --}}
<div style="display:grid;grid-template-columns:1fr 340px;gap:1rem;align-items:start;">

    {{-- Sesi Terbaru --}}
    <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100 dark:border-white/10">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Sesi Ujian Terbaru</h3>
            <a href="{{ \App\Filament\Resources\ExamSessionResource::getUrl() }}"
               class="text-xs text-primary-600 dark:text-primary-400 hover:underline font-medium">
                Lihat Semua →
            </a>
        </div>
        @if ($sesiTerbaru->isEmpty())
            <div class="px-5 py-10 text-center text-sm text-gray-400">Belum ada sesi ujian.</div>
        @else
        <table class="w-full text-sm table-fixed">
            <colgroup>
                <col style="width:35%">
                <col style="width:25%">
                <col style="width:15%">
                <col style="width:14%">
                <col style="width:11%">
            </colgroup>
            <thead>
                <tr class="border-b border-gray-100 dark:border-white/10 bg-gray-50 dark:bg-gray-900/50">
                    <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Sesi</th>
                    <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Dibuat Oleh</th>
                    <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Waktu</th>
                    <th class="px-4 py-2.5 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-4 py-2.5 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                @foreach ($sesiTerbaru as $s)
                @php
                    $statusBadge = match ($s->status) {
                        'aktif'     => 'bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400',
                        'selesai'   => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                        'draft'     => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
                        default     => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                    };
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                    <td class="px-4 py-2.5">
                        <p class="font-medium text-gray-900 dark:text-white truncate">{{ $s->nama_sesi }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ $s->package?->nama ?? '—' }}</p>
                    </td>
                    <td class="px-4 py-2.5 text-xs text-gray-500 dark:text-gray-400 truncate">{{ $s->creator?->name ?? '—' }}</td>
                    <td class="px-4 py-2.5 text-xs text-gray-500 dark:text-gray-400">
                        {{ $s->waktu_mulai?->format('d M, H:i') ?? '—' }}
                    </td>
                    <td class="px-4 py-2.5 text-center">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $statusBadge }}">
                            {{ \App\Models\ExamSession::STATUS_LABELS[$s->status] ?? $s->status }}
                        </span>
                    </td>
                    <td class="px-4 py-2.5 text-center">
                        <a href="{{ \App\Filament\Resources\ExamSessionResource\Pages\MonitorSesi::getUrl(['record' => $s->id]) }}"
                           class="text-xs text-primary-600 dark:text-primary-400 hover:underline font-medium">
                            Monitor
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    {{-- Pengumuman --}}
    <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100 dark:border-white/10">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Pengumuman Aktif</h3>
        </div>
        @if ($pengumuman->isEmpty())
            <div class="px-5 py-10 text-center">
                <x-heroicon-o-megaphone class="w-8 h-8 mx-auto text-gray-300 dark:text-gray-600 mb-2"/>
                <p class="text-sm text-gray-400 dark:text-gray-500">Tidak ada pengumuman aktif.</p>
            </div>
        @else
        <div class="divide-y divide-gray-100 dark:divide-white/5">
            @foreach ($pengumuman as $p)
            @php
                $tipeBadge = match ($p->tipe) {
                    'penting' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                    'warning' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                    default   => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                };
            @endphp
            <div class="px-5 py-4">
                <div class="flex items-start gap-2">
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold shrink-0 {{ $tipeBadge }}">
                        {{ \App\Models\Announcement::TIPE_LABELS[$p->tipe] ?? $p->tipe }}
                    </span>
                    @if ($p->target === \App\Models\Announcement::TARGET_PER_ROMBEL && $p->rombel)
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 shrink-0">
                        {{ $p->rombel->kode }}
                    </span>
                    @endif
                </div>
                <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1.5">{{ $p->judul }}</p>
                @if ($p->tanggal_selesai)
                <p class="text-xs text-gray-400 mt-0.5">s.d. {{ $p->tanggal_selesai->format('d M Y') }}</p>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>

</div>

@else
{{-- ══════════════════════════════════════════════════════════════════════════
     GURU VIEW
══════════════════════════════════════════════════════════════════════════ --}}

{{-- Stat Cards --}}
<div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem;">
    @php
        $guruStats = [
            ['label' => 'Rombel Diampu',  'value' => $rombelCount,             'icon' => 'heroicon-o-user-group',  'color' => 'text-indigo-600 dark:text-indigo-400', 'bg' => 'bg-indigo-50 dark:bg-indigo-950/30'],
            ['label' => 'Total Sesi Saya','value' => $totalSesi,               'icon' => 'heroicon-o-play-circle',  'color' => 'text-green-600 dark:text-green-400',  'bg' => 'bg-green-50 dark:bg-green-950/30'],
            ['label' => 'Sesi Aktif',     'value' => $sesiAktif->count(),      'icon' => 'heroicon-o-signal',      'color' => $sesiAktif->isNotEmpty() ? 'text-success-600 dark:text-success-400' : 'text-gray-400', 'bg' => $sesiAktif->isNotEmpty() ? 'bg-success-50 dark:bg-success-950/30' : 'bg-gray-50 dark:bg-gray-800'],
        ];
    @endphp
    @foreach ($guruStats as $stat)
    <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 px-5 py-4">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg {{ $stat['bg'] }}">
                <x-dynamic-component :component="$stat['icon']" class="w-4 h-4 {{ $stat['color'] }}"/>
            </span>
        </div>
        <p class="text-3xl font-bold {{ $stat['color'] }}">{{ $stat['value'] }}</p>
    </div>
    @endforeach
</div>

{{-- Sesi Aktif Guru --}}
<div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
    <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100 dark:border-white/10">
        <div class="flex items-center gap-2">
            @if ($sesiAktif->isNotEmpty())
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-success-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-success-500"></span>
                </span>
            @endif
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Sesi Aktif Saya</h3>
        </div>
        <a href="{{ \App\Filament\Resources\ExamSessionResource::getUrl() }}"
           class="text-xs text-primary-600 dark:text-primary-400 hover:underline font-medium">
            Kelola Sesi →
        </a>
    </div>

    @if ($sesiAktif->isEmpty())
        <div class="px-5 py-10 text-center">
            <x-heroicon-o-play-circle class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600 mb-2"/>
            <p class="text-sm text-gray-400 dark:text-gray-500">Tidak ada sesi ujian yang sedang aktif.</p>
        </div>
    @else
        <div class="divide-y divide-gray-100 dark:divide-white/5">
            @foreach ($sesiAktif as $s)
            <div class="flex items-center gap-4 px-5 py-4 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-900 dark:text-white truncate">{{ $s->nama }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ $s->paket }} &middot; mulai {{ $s->waktu_mulai?->format('H:i') }}
                    </p>
                </div>
                <div class="flex items-center gap-5 shrink-0">
                    <div class="text-center">
                        <p class="text-lg font-bold text-warning-600 dark:text-warning-400">{{ $s->sedang_mengerjakan }}</p>
                        <p class="text-xs text-gray-400">Mengerjakan</p>
                    </div>
                    <div class="text-center">
                        <p class="text-lg font-bold text-success-600 dark:text-success-400">{{ $s->sudah_selesai }}</p>
                        <p class="text-xs text-gray-400">Selesai</p>
                    </div>
                    <div class="text-center">
                        <p class="font-mono text-sm font-bold text-gray-700 dark:text-gray-300 tracking-widest">{{ $s->token }}</p>
                        <p class="text-xs text-gray-400">Token</p>
                    </div>
                    <a href="{{ \App\Filament\Resources\ExamSessionResource\Pages\MonitorSesi::getUrl(['record' => $s->id]) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-success-50 text-success-700 hover:bg-success-100 dark:bg-success-900/20 dark:text-success-400 ring-1 ring-success-200 dark:ring-success-800 transition-colors whitespace-nowrap">
                        <x-heroicon-o-eye class="w-3.5 h-3.5"/>
                        Monitor
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>

{{-- Sesi Terakhir --}}
<div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
    <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100 dark:border-white/10">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Sesi Terbaru Saya</h3>
        <a href="{{ \App\Filament\Resources\ExamSessionResource::getUrl() }}"
           class="text-xs text-primary-600 dark:text-primary-400 hover:underline font-medium">
            Lihat Semua →
        </a>
    </div>
    @if ($sesiTerakhir->isEmpty())
        <div class="px-5 py-10 text-center text-sm text-gray-400">
            Belum ada sesi ujian yang dibuat. <a href="{{ \App\Filament\Resources\ExamSessionResource::getUrl('create') }}" class="text-primary-600 dark:text-primary-400 hover:underline">Buat sesi →</a>
        </div>
    @else
    <table class="w-full text-sm table-fixed">
        <colgroup>
            <col style="width:40%">
            <col style="width:25%">
            <col style="width:18%">
            <col style="width:17%">
        </colgroup>
        <thead>
            <tr class="border-b border-gray-100 dark:border-white/10 bg-gray-50 dark:bg-gray-900/50">
                <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Nama Sesi</th>
                <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Paket</th>
                <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Waktu</th>
                <th class="px-4 py-2.5 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
            @foreach ($sesiTerakhir as $s)
            @php
                $statusBadge = match ($s->status) {
                    'aktif'   => 'bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400',
                    'selesai' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                    'draft'   => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
                    default   => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                };
            @endphp
            <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-white truncate">{{ $s->nama_sesi }}</td>
                <td class="px-4 py-2.5 text-xs text-gray-500 dark:text-gray-400 truncate">{{ $s->package?->nama ?? '—' }}</td>
                <td class="px-4 py-2.5 text-xs text-gray-500 dark:text-gray-400">{{ $s->waktu_mulai?->format('d M, H:i') ?? '—' }}</td>
                <td class="px-4 py-2.5 text-center">
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $statusBadge }}">
                        {{ \App\Models\ExamSession::STATUS_LABELS[$s->status] ?? $s->status }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

{{-- Link ke DashboardGuru --}}
<div class="rounded-xl bg-primary-50 dark:bg-primary-950/30 ring-1 ring-primary-200 dark:ring-primary-800 px-5 py-4">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <x-heroicon-o-presentation-chart-line class="w-5 h-5 text-primary-600 dark:text-primary-400 shrink-0"/>
            <div>
                <p class="text-sm font-semibold text-primary-800 dark:text-primary-300">Dashboard Nilai Rombel</p>
                <p class="text-xs text-primary-600 dark:text-primary-400">Lihat nilai peserta per rombel untuk setiap sesi ujian yang Anda buat.</p>
            </div>
        </div>
        <a href="{{ \App\Filament\Pages\DashboardGuru::getUrl() }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-primary-600 hover:bg-primary-700 text-sm font-semibold text-white transition-colors shrink-0">
            Buka
            <x-heroicon-o-arrow-right class="w-4 h-4"/>
        </a>
    </div>
</div>

@endif

</x-filament-panels::page>
