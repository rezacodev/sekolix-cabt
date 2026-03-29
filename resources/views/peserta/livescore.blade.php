{{-- resources/views/peserta/livescore.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Livescore — {{ $session->nama_sesi }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-950 min-h-screen">

{{-- ── Header ─────────────────────────────────────────────────────────────── --}}
<div class="bg-gradient-to-r from-indigo-700 to-violet-700 px-6 py-5 text-white shadow-xl">
    <div class="max-w-4xl mx-auto flex items-start justify-between gap-4">
        <div>
            <p class="text-indigo-200 text-xs font-semibold uppercase tracking-widest mb-1">Livescore</p>
            <h1 class="text-2xl font-bold leading-tight">{{ $session->nama_sesi }}</h1>
            <p class="text-indigo-200 text-sm mt-1">{{ $session->package?->nama }}</p>
        </div>
        <div class="text-right shrink-0 text-white/80 text-xs" x-data="{}" x-text="'Diperbarui: ' + (window._lsUpdated ?? '—')"></div>
    </div>
</div>

{{-- ── Summary Cards ───────────────────────────────────────────────────────── --}}
<div class="max-w-4xl mx-auto px-4 py-6">
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="rounded-2xl bg-gray-900 ring-1 ring-white/10 p-4 text-center">
            <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide mb-1">Total Peserta</p>
            <p class="text-3xl font-bold text-white" id="ls-total">—</p>
        </div>
        <div class="rounded-2xl bg-gray-900 ring-1 ring-white/10 p-4 text-center">
            <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide mb-1">Sudah Selesai</p>
            <p class="text-3xl font-bold text-green-400" id="ls-selesai">—</p>
        </div>
        <div class="rounded-2xl bg-gray-900 ring-1 ring-white/10 p-4 text-center">
            <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide mb-1">Rata-rata Nilai</p>
            <p class="text-3xl font-bold text-indigo-400" id="ls-rata">—</p>
        </div>
    </div>

    {{-- ── Leaderboard ──────────────────────────────────────────────────────── --}}
    <div class="rounded-2xl bg-gray-900 ring-1 ring-white/10 overflow-hidden">
        <div class="px-6 py-4 border-b border-white/10 flex items-center gap-3">
            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 1l2.5 5.5H18l-4.5 4 1.8 6.2L10 13.3l-5.3 3.4 1.8-6.2L2 7.5h5.5L10 1z" clip-rule="evenodd"/>
            </svg>
            <h2 class="font-bold text-white text-base">Leaderboard</h2>
            <span class="ml-auto text-xs text-gray-400" id="ls-timestamp">Memuat...</span>
        </div>

        <div id="ls-table-wrapper" class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-800/50 text-left">
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-400 w-12 text-center">Rank</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-400">Nama</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-400">No. Peserta</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-400">Rombel</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-400 text-right">Nilai</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-400 text-center">Benar</th>
                    </tr>
                </thead>
                <tbody id="ls-tbody" class="divide-y divide-white/5">
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-400">Memuat data...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="ls-empty" class="hidden px-6 py-10 text-center text-gray-400">
            Belum ada peserta yang menyelesaikan ujian.
        </div>
    </div>
</div>

<script>
(function () {
    const dataUrl   = "{{ route('livescore.data', $session->id) }}";
    const interval  = 15000;

    function renderRank(rank) {
        if (rank === 1) return '<span style="color:#FFD700;font-size:1.2em">🥇</span>';
        if (rank === 2) return '<span style="color:#C0C0C0;font-size:1.2em">🥈</span>';
        if (rank === 3) return '<span style="color:#CD7F32;font-size:1.2em">🥉</span>';
        return '<span style="color:#9ca3af">' + rank + '</span>';
    }

    function colorNilai(nilai) {
        if (nilai >= 80) return '#34d399';
        if (nilai >= 60) return '#fbbf24';
        return '#f87171';
    }

    function fetchData() {
        fetch(dataUrl)
            .then(r => r.json())
            .then(data => {
                window._lsUpdated = data.updated_at;
                document.getElementById('ls-total').textContent    = data.total ?? '—';
                document.getElementById('ls-selesai').textContent  = data.total ?? '—';
                document.getElementById('ls-rata').textContent     = data.rata_rata !== null ? Number(data.rata_rata).toFixed(1) : '—';
                document.getElementById('ls-timestamp').textContent = 'Update: ' + data.updated_at;

                const tbody = document.getElementById('ls-tbody');
                const empty = document.getElementById('ls-empty');

                if (!data.rankings || data.rankings.length === 0) {
                    tbody.innerHTML = '';
                    document.getElementById('ls-table-wrapper').style.display = 'none';
                    empty.classList.remove('hidden');
                    return;
                }

                document.getElementById('ls-table-wrapper').style.display = '';
                empty.classList.add('hidden');

                tbody.innerHTML = data.rankings.map(r => `
                    <tr style="transition:background 0.3s" onmouseover="this.style.background='rgba(255,255,255,0.04)'" onmouseout="this.style.background=''">
                        <td style="padding:0.75rem 1rem;text-align:center">${renderRank(r.rank)}</td>
                        <td style="padding:0.75rem 1rem;color:#f9fafb;font-weight:500">${r.nama}</td>
                        <td style="padding:0.75rem 1rem;color:#9ca3af;font-family:monospace;font-size:0.75rem">${r.nomor_peserta}</td>
                        <td style="padding:0.75rem 1rem;color:#9ca3af">${r.rombel}</td>
                        <td style="padding:0.75rem 1rem;text-align:right;font-size:1.25rem;font-weight:700;color:${colorNilai(r.nilai)}">${Number(r.nilai).toFixed(1)}</td>
                        <td style="padding:0.75rem 1rem;text-align:center;color:#34d399;font-weight:600">${r.benar ?? '—'}</td>
                    </tr>
                `).join('');
            })
            .catch(() => {
                document.getElementById('ls-timestamp').textContent = 'Gagal memuat data';
            });
    }

    fetchData();
    setInterval(fetchData, interval);
})();
</script>
</body>
</html>
