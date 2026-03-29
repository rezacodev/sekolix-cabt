<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Nilai — {{ $session->nama_sesi }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Times New Roman', serif; font-size: 11pt; color: #000; background: #fff; }
        .page { max-width: 210mm; margin: 0 auto; padding: 15mm 20mm; }
        h1 { font-size: 14pt; font-weight: bold; text-align: center; margin-bottom: 4px; }
        h2 { font-size: 12pt; text-align: center; margin-bottom: 12px; }
        .meta { font-size: 10pt; border: 1px solid #000; padding: 8px 12px; margin-bottom: 16px; }
        .meta table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 2px 4px; vertical-align: top; }
        .meta td:first-child { width: 35%; font-weight: bold; }
        .stats { display: flex; gap: 8px; margin-bottom: 16px; }
        .stat-box { flex: 1; border: 1px solid #000; padding: 6px; text-align: center; }
        .stat-box .num { font-size: 18pt; font-weight: bold; }
        .stat-box .lbl { font-size: 8pt; }
        table.nilai { width: 100%; border-collapse: collapse; font-size: 10pt; }
        table.nilai th, table.nilai td { border: 1px solid #000; padding: 4px 6px; }
        table.nilai thead tr { background: #eee; font-weight: bold; text-align: center; }
        table.nilai td.center { text-align: center; }
        table.nilai td.right { text-align: right; }
        .footer { margin-top: 24px; display: flex; justify-content: space-between; font-size: 10pt; }
        .ttd { text-align: center; }
        .ttd .ttd-line { margin-top: 50px; border-top: 1px solid #000; padding-top: 4px; }
        @media print {
            body { -webkit-print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
<div class="page">

    <h1>REKAP NILAI UJIAN</h1>
    @if ($schoolName || $schoolLogoUrl)
    <div style="text-align:center;margin-bottom:8px;">
        @if ($schoolLogoUrl)<img src="{{ $schoolLogoUrl }}" alt="Logo" style="height:56px;margin-bottom:4px;"><br>@endif
        @if ($schoolName)<div style="font-size:13pt;font-weight:bold;">{{ strtoupper($schoolName) }}</div>@endif
    </div>
    @endif
    <h2>{{ strtoupper($session->nama_sesi) }}</h2>

    <div class="meta">
        <table>
            <tr><td>Paket Ujian</td><td>: {{ $session->package?->nama ?? '—' }}</td><td>Tanggal Ujian</td><td>: {{ $session->waktu_mulai?->format('d F Y') ?? '—' }}</td></tr>
            <tr><td>Waktu Mulai</td><td>: {{ $session->waktu_mulai?->format('H:i') ?? '—' }}</td><td>Waktu Selesai</td><td>: {{ $session->waktu_selesai?->format('H:i') ?? '—' }}</td></tr>
            <tr><td>Dibuat Oleh</td><td>: {{ $session->creator?->name ?? '—' }}</td><td>Dicetak</td><td>: {{ now()->format('d F Y, H:i') }}</td></tr>
        </table>
    </div>

    <div class="stats">
        <div class="stat-box"><div class="num">{{ $statistik['total_peserta'] }}</div><div class="lbl">Total Peserta</div></div>
        <div class="stat-box"><div class="num">{{ number_format($statistik['rata_rata'], 1) }}</div><div class="lbl">Rata-rata</div></div>
        <div class="stat-box"><div class="num">{{ number_format($statistik['nilai_tertinggi'], 1) }}</div><div class="lbl">Tertinggi</div></div>
        <div class="stat-box"><div class="num">{{ number_format($statistik['nilai_terendah'], 1) }}</div><div class="lbl">Terendah</div></div>
        <div class="stat-box"><div class="num">{{ number_format($statistik['median'], 1) }}</div><div class="lbl">Median</div></div>
    </div>

    <table class="nilai">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Peserta</th>
                <th>No. Peserta</th>
                <th>Rombel</th>
                <th>Nilai</th>
                <th>Benar</th>
                <th>Salah</th>
                <th>Kosong</th>
                <th>Attempt</th>
                <th>Status</th>
                <th>Durasi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rekap as $row)
            <tr>
                <td class="center">{{ $row->no }}</td>
                <td>{{ $row->nama }}</td>
                <td class="center">{{ $row->nomor_peserta }}</td>
                <td>{{ $row->rombel_nama }}</td>
                <td class="right" style="font-weight:bold">{{ $row->nilai_akhir !== null ? number_format((float)$row->nilai_akhir, 1) : '—' }}</td>
                <td class="center">{{ $row->jumlah_benar }}</td>
                <td class="center">{{ $row->jumlah_salah }}</td>
                <td class="center">{{ $row->jumlah_kosong }}</td>
                <td class="center">{{ $row->attempt_ke }}×</td>
                <td class="center">{{ \App\Models\ExamAttempt::STATUS_LABELS[$row->status] ?? $row->status }}</td>
                <td class="center">
                    @if($row->durasi_detik !== null)
                        {{ intdiv($row->durasi_detik,60) }}m {{ $row->durasi_detik % 60 }}d
                    @else —
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <div></div>
        <div class="ttd">
            <p>........., {{ now()->format('d F Y') }}</p>
            <p>Guru / Pengawas,</p>
            <div class="ttd-line">( {{ $session->creator?->name ?? '...........................' }} )</div>
        </div>
    </div>

</div>
<script>window.onload = function(){ window.print(); }</script>
</body>
</html>
