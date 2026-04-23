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
     Note: gradient uses inline style — from-primary-* not in app's tailwind.config
══════════════════════════════════════════════════════════════════════════ --}}
<div class="rounded-xl px-6 py-5 shadow-md" style="background:linear-gradient(135deg,#2563eb,#1e40af);">
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <div>
            <p class="text-sm font-medium" style="color:rgba(255,255,255,0.75);">{{ $greeting }},</p>
            <h2 class="text-xl font-bold mt-0.5 text-white">{{ $user->name }}</h2>
            <p class="text-xs mt-1" style="color:rgba(255,255,255,0.65);">{{ \App\Models\User::levelLabels()[$user->level] ?? '' }} &middot; {{ now()->translatedFormat('l, d F Y') }}</p>
        </div>
        @if ($isAdmin)
        <div class="flex items-center gap-3" style="color:rgba(255,255,255,0.75);">
            <x-heroicon-o-shield-check class="w-5 h-5 shrink-0" style="color:rgba(255,255,255,0.75);"/>
            <span class="text-xs">Panel {{ \App\Models\User::levelLabels()[$user->level] }}</span>
        </div>
        @else
        <a href="{{ \App\Filament\Pages\DashboardGuru::getUrl() }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold text-white transition-colors"
           style="background:rgba(255,255,255,0.2);">
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
            ['label' => 'Total Peserta',  'value' => $totalPeserta,            'icon' => 'heroicon-o-users',           'color' => '#2563eb', 'bg' => '#eff6ff'],
            ['label' => 'Total Guru',     'value' => $totalGuru,               'icon' => 'heroicon-o-academic-cap',    'color' => '#9333ea', 'bg' => '#faf5ff'],
            ['label' => 'Rombel Aktif',   'value' => $totalRombelAktif,        'icon' => 'heroicon-o-user-group',      'color' => '#4f46e5', 'bg' => '#eef2ff'],
            ['label' => 'Bank Soal',      'value' => number_format($totalSoal),'icon' => 'heroicon-o-document-text',  'color' => '#d97706', 'bg' => '#fffbeb'],
            ['label' => 'Paket Ujian',    'value' => $totalPaket,              'icon' => 'heroicon-o-folder-open',     'color' => '#0891b2', 'bg' => '#ecfeff'],
            ['label' => 'Total Sesi',     'value' => $totalSesi,               'icon' => 'heroicon-o-play-circle',     'color' => '#16a34a', 'bg' => '#f0fdf4'],
        ];
    @endphp
    @foreach ($stats as $stat)
    <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10" style="padding:1rem;">
        <div class="flex items-center justify-between" style="margin-bottom:0.75rem;">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg" style="background-color:{{ $stat['bg'] }};">
                <x-dynamic-component :component="$stat['icon']" class="w-4 h-4" style="color:{{ $stat['color'] }};"/>
            </span>
        </div>
        <p class="text-2xl font-bold" style="color:{{ $stat['color'] }};">{{ $stat['value'] }}</p>
    </div>
    @endforeach
</div>

{{-- ── Sesi Aktif Sekarang ─────────────────────────────────────────────────── --}}
<div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
    <div class="flex items-center justify-between px-6 py-3 border-b border-gray-100 dark:border-white/10">
        <div class="flex items-center gap-2">
            @if ($sesiAktif->isNotEmpty())
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" style="background:#4ade80;"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5" style="background:#22c55e;"></span>
                </span>
            @endif
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                Sesi Aktif Sekarang
                @if ($sesiAktif->isNotEmpty())
                    <span class="ml-1.5 inline-flex items-center rounded-full text-xs font-semibold px-2 py-0.5" style="background:#dcfce7;color:#15803d;">{{ $sesiAktif->count() }}</span>
                @endif
            </h3>
        </div>
        <a href="{{ \App\Filament\Resources\ExamSessionResource::getUrl() }}"
           class="text-xs text-primary-600 dark:text-primary-400 hover:underline font-medium">
            Kelola Sesi →
        </a>
    </div>

    @if ($sesiAktif->isEmpty())
        <div class="px-6 py-8 text-center">
            <x-heroicon-o-play-circle class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600 mb-2"/>
            <p class="text-sm text-gray-400 dark:text-gray-500">Tidak ada sesi ujian yang sedang aktif.</p>
        </div>
    @else
        <div class="divide-y divide-gray-100 dark:divide-white/5">
            @foreach ($sesiAktif as $s)
            <div class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-900 dark:text-white truncate">{{ $s->nama }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ $s->paket }} &middot; oleh <span class="font-medium">{{ $s->guru }}</span>
                        &middot; mulai {{ $s->waktu_mulai?->format('H:i') }}
                    </p>
                </div>
                <div class="flex items-center gap-4 shrink-0">
                    <div class="text-center">
                        <p class="text-lg font-bold" style="color:#d97706;">{{ $s->sedang_mengerjakan }}</p>
                        <p class="text-xs text-gray-400">Mengerjakan</p>
                    </div>
                    <div class="text-center">
                        <p class="text-lg font-bold" style="color:#16a34a;">{{ $s->sudah_selesai }}</p>
                        <p class="text-xs text-gray-400">Selesai</p>
                    </div>
                    <div class="text-center">
                        <p class="font-mono text-sm font-bold text-gray-700 dark:text-gray-300 tracking-widest">{{ $s->token }}</p>
                        <p class="text-xs text-gray-400">Token</p>
                    </div>
                    <a href="{{ \App\Filament\Resources\ExamSessionResource\Pages\MonitorSesi::getUrl(['record' => $s->id]) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors whitespace-nowrap" style="background:#f0fdf4;color:#15803d;outline:1px solid #bbf7d0;">
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
        <div class="flex items-center justify-between px-6 py-3 border-b border-gray-100 dark:border-white/10">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Sesi Ujian Terbaru</h3>
            <a href="{{ \App\Filament\Resources\ExamSessionResource::getUrl() }}"
               class="text-xs text-primary-600 dark:text-primary-400 hover:underline font-medium">
                Lihat Semua →
            </a>
        </div>
        @if ($sesiTerbaru->isEmpty())
            <div class="px-6 py-8 text-center text-sm text-gray-400">Belum ada sesi ujian.</div>
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
                    $statusStyle = match ($s->status) {
                        'aktif'     => 'background:#dcfce7;color:#15803d;',
                        'selesai'   => 'background:#dbeafe;color:#1d4ed8;',
                        'draft'     => 'background:#f3f4f6;color:#4b5563;',
                        default     => 'background:#fee2e2;color:#b91c1c;',
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
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold" style="{{ $statusStyle }}">
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
        <div class="flex items-center justify-between px-6 py-3 border-b border-gray-100 dark:border-white/10">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Pengumuman Aktif</h3>
        </div>
        @if ($pengumuman->isEmpty())
            <div class="px-6 py-8 text-center">
                <x-heroicon-o-megaphone class="w-8 h-8 mx-auto text-gray-300 dark:text-gray-600 mb-2"/>
                <p class="text-sm text-gray-400 dark:text-gray-500">Tidak ada pengumuman aktif.</p>
            </div>
        @else
        <div class="divide-y divide-gray-100 dark:divide-white/5">
            @foreach ($pengumuman as $p)
            @php
                $tipeBadgeStyle = match ($p->tipe) {
                    'penting' => 'background:#fee2e2;color:#b91c1c;',
                    'warning' => 'background:#fef3c7;color:#b45309;',
                    default   => 'background:#dbeafe;color:#1d4ed8;',
                };
            @endphp
            <div class="px-6 py-4">
                <div class="flex items-start gap-2">
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold shrink-0" style="{{ $tipeBadgeStyle }}">
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
            ['label' => 'Rombel Diampu',   'value' => $rombelCount,        'icon' => 'heroicon-o-user-group',  'color' => '#4f46e5', 'bg' => '#eef2ff'],
            ['label' => 'Total Sesi Saya', 'value' => $totalSesi,          'icon' => 'heroicon-o-play-circle', 'color' => '#16a34a', 'bg' => '#f0fdf4'],
            ['label' => 'Sesi Aktif',      'value' => $sesiAktif->count(), 'icon' => 'heroicon-o-signal',      'color' => $sesiAktif->isNotEmpty() ? '#16a34a' : '#9ca3af', 'bg' => $sesiAktif->isNotEmpty() ? '#f0fdf4' : '#f9fafb'],
        ];
    @endphp
    @foreach ($guruStats as $stat)
    <div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10" style="padding:1.25rem 1rem;">
        <div class="flex items-center justify-between" style="margin-bottom:0.75rem;">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg" style="background-color:{{ $stat['bg'] }};">
                <x-dynamic-component :component="$stat['icon']" class="w-4 h-4" style="color:{{ $stat['color'] }};"/>
            </span>
        </div>
        <p class="text-3xl font-bold" style="color:{{ $stat['color'] }};">{{ $stat['value'] }}</p>
    </div>
    @endforeach
</div>

{{-- Sesi Aktif Guru --}}
<div class="rounded-xl bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
    <div class="flex items-center justify-between px-6 py-3 border-b border-gray-100 dark:border-white/10">
        <div class="flex items-center gap-2">
            @if ($sesiAktif->isNotEmpty())
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" style="background:#4ade80;"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5" style="background:#22c55e;"></span>
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
        <div class="px-6 py-8 text-center">
            <x-heroicon-o-play-circle class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600 mb-2"/>
            <p class="text-sm text-gray-400 dark:text-gray-500">Tidak ada sesi ujian yang sedang aktif.</p>
        </div>
    @else
        <div class="divide-y divide-gray-100 dark:divide-white/5">
            @foreach ($sesiAktif as $s)
            <div class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-900 dark:text-white truncate">{{ $s->nama }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ $s->paket }} &middot; mulai {{ $s->waktu_mulai?->format('H:i') }}
                    </p>
                </div>
                <div class="flex items-center gap-4 shrink-0">
                    <div class="text-center">
                        <p class="text-lg font-bold" style="color:#d97706;">{{ $s->sedang_mengerjakan }}</p>
                        <p class="text-xs text-gray-400">Mengerjakan</p>
                    </div>
                    <div class="text-center">
                        <p class="text-lg font-bold" style="color:#16a34a;">{{ $s->sudah_selesai }}</p>
                        <p class="text-xs text-gray-400">Selesai</p>
                    </div>
                    <div class="text-center">
                        <p class="font-mono text-sm font-bold text-gray-700 dark:text-gray-300 tracking-widest">{{ $s->token }}</p>
                        <p class="text-xs text-gray-400">Token</p>
                    </div>
                    <a href="{{ \App\Filament\Resources\ExamSessionResource\Pages\MonitorSesi::getUrl(['record' => $s->id]) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors whitespace-nowrap" style="background:#f0fdf4;color:#15803d;outline:1px solid #bbf7d0;">
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
    <div class="flex items-center justify-between px-6 py-3 border-b border-gray-100 dark:border-white/10">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Sesi Terbaru Saya</h3>
        <a href="{{ \App\Filament\Resources\ExamSessionResource::getUrl() }}"
           class="text-xs text-primary-600 dark:text-primary-400 hover:underline font-medium">
            Lihat Semua →
        </a>
    </div>
    @if ($sesiTerakhir->isEmpty())
        <div class="px-6 py-8 text-center text-sm text-gray-400">
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
                $statusStyleGuru = match ($s->status) {
                    'aktif'   => 'background:#dcfce7;color:#15803d;',
                    'selesai' => 'background:#dbeafe;color:#1d4ed8;',
                    'draft'   => 'background:#f3f4f6;color:#4b5563;',
                    default   => 'background:#fee2e2;color:#b91c1c;',
                };
            @endphp
            <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-white truncate">{{ $s->nama_sesi }}</td>
                <td class="px-4 py-2.5 text-xs text-gray-500 dark:text-gray-400 truncate">{{ $s->package?->nama ?? '—' }}</td>
                <td class="px-4 py-2.5 text-xs text-gray-500 dark:text-gray-400">{{ $s->waktu_mulai?->format('d M, H:i') ?? '—' }}</td>
                <td class="px-4 py-2.5 text-center">
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold" style="{{ $statusStyleGuru }}">
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
<div class="rounded-xl px-6 py-4" style="background:#eff6ff;outline:1px solid #bfdbfe;">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <x-heroicon-o-presentation-chart-line class="w-5 h-5 text-primary-600 dark:text-primary-400 shrink-0"/>
            <div>
                <p class="text-sm font-semibold" style="color:#1e40af;">Dashboard Nilai Rombel</p>
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
