<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Hadir — {{ $session->nama_sesi }}</title>
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
        table.dh { width: 100%; border-collapse: collapse; font-size: 10pt; }
        table.dh th, table.dh td { border: 1px solid #000; padding: 4px 6px; }
        table.dh thead tr { background: #eee; font-weight: bold; text-align: center; }
        table.dh td.center { text-align: center; }
        .ttd-row { margin-top: 24px; display: flex; justify-content: space-between; font-size: 10pt; }
        .ttd { text-align: center; }
        .ttd .ttd-line { margin-top: 50px; border-top: 1px solid #000; padding-top: 4px; }
        @media print { body { -webkit-print-color-adjust: exact; } }
    </style>
</head>
<body>
<div class="page">

    <h1>DAFTAR HADIR UJIAN</h1>
    @if ($schoolName || $schoolLogoUrl)
    <div style="text-align:center;margin-bottom:8px;">
        @if ($schoolLogoUrl)<img src="{{ $schoolLogoUrl }}" alt="Logo" style="height:56px;margin-bottom:4px;"><br>@endif
        @if ($schoolName)<div style="font-size:13pt;font-weight:bold;">{{ strtoupper($schoolName) }}</div>@endif
    </div>
    @endif
    <h2>{{ strtoupper($session->nama_sesi) }}</h2>

    <div class="meta">
        <table>
            <tr><td>Paket Ujian</td><td>: {{ $session->package?->nama ?? '—' }}</td><td>Tanggal</td><td>: {{ $session->waktu_mulai?->format('d F Y') ?? '—' }}</td></tr>
            <tr><td>Waktu</td><td>: {{ $session->waktu_mulai?->format('H:i') ?? '—' }} — {{ $session->waktu_selesai?->format('H:i') ?? '—' }}</td>
                <td>Total Peserta</td><td>: {{ $kehadiran['total'] }} orang</td></tr>
        </table>
    </div>

    <table class="dh">
        <thead>
            <tr>
                <th style="width:5%">No</th>
                <th style="width:28%">Nama Peserta</th>
                <th style="width:15%">No. Peserta</th>
                <th style="width:15%">Rombel</th>
                <th style="width:12%">Status</th>
                <th style="width:25%">Tanda Tangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($kehadiran['list'] as $i => $p)
            @php
                $statusLabel = \App\Models\ExamSessionParticipant::STATUS_LABELS[$p->status] ?? $p->status;
            @endphp
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td>{{ $p->user?->name ?? '—' }}</td>
                <td class="center">{{ $p->user?->nomor_peserta ?? '—' }}</td>
                <td>{{ $p->user?->rombel?->nama ?? '—' }}</td>
                <td class="center">{{ $statusLabel }}</td>
                <td>&nbsp;</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="ttd-row">
        <div>
            <p>Mengetahui,</p>
            <p>Kepala Sekolah</p>
            <div class="ttd-line" style="width:180px">( .................................. )</div>
        </div>
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
