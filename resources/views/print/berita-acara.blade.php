<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Acara — {{ $session->nama_sesi }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Times New Roman', serif; font-size: 11pt; color: #000; background: #fff; }
        .page { max-width: 210mm; margin: 0 auto; padding: 15mm 20mm; }
        h1 { font-size: 14pt; font-weight: bold; text-align: center; margin-bottom: 4px; }
        h2 { font-size: 12pt; text-align: center; margin-bottom: 16px; }
        p { line-height: 1.8; margin-bottom: 8px; text-align: justify; }
        .section-title { font-weight: bold; margin-top: 16px; margin-bottom: 6px; text-decoration: underline; }
        table.stat { width: 100%; border-collapse: collapse; font-size: 10pt; margin-bottom: 12px; }
        table.stat td { padding: 3px 8px; vertical-align: top; }
        table.stat td:first-child { width: 45%; font-weight: normal; }
        .ttd-row { margin-top: 32px; display: flex; justify-content: space-between; font-size: 10pt; }
        .ttd { text-align: center; }
        .ttd .ttd-line { margin-top: 54px; border-top: 1px solid #000; padding-top: 4px; }
        .box { border: 1px solid #000; padding: 8px 12px; margin-bottom: 12px; }
        @media print { body { -webkit-print-color-adjust: exact; } }
    </style>
</head>
<body>
<div class="page">

    <h1>BERITA ACARA PELAKSANAAN UJIAN</h1>
    @if ($schoolName || $schoolLogoUrl)
    <div style="text-align:center;margin-bottom:8px;">
        @if ($schoolLogoUrl)<img src="{{ $schoolLogoUrl }}" alt="Logo" style="height:56px;margin-bottom:4px;"><br>@endif
        @if ($schoolName)<div style="font-size:13pt;font-weight:bold;">{{ strtoupper($schoolName) }}</div>@endif
    </div>
    @endif
    <h2>(Computer Assisted Test — CAT Sekolix)</h2>

    <div class="box">
        <table class="stat">
            <tr><td>Nama Ujian</td><td>: <strong>{{ $session->nama_sesi }}</strong></td></tr>
            <tr><td>Paket Soal</td><td>: {{ $session->package?->nama ?? '—' }}</td></tr>
            <tr><td>Hari / Tanggal</td><td>: {{ $session->waktu_mulai?->translatedFormat('l') ?? '—' }}, {{ $session->waktu_mulai?->format('d F Y') ?? '—' }}</td></tr>
            <tr><td>Waktu Pelaksanaan</td><td>: {{ $session->waktu_mulai?->format('H:i') ?? '—' }} — {{ $session->waktu_selesai?->format('H:i') ?? '—' }} WIB</td></tr>
            <tr><td>Durasi</td><td>: {{ $session->package?->durasi_menit ?? '—' }} menit</td></tr>
            <tr><td>Pengawas / Guru</td><td>: {{ $session->creator?->name ?? '—' }}</td></tr>
        </table>
    </div>

    <p class="section-title">DATA PESERTA</p>
    <table class="stat">
        <tr><td>Total Peserta Terdaftar</td><td>: {{ $kehadiran['total'] }} orang</td></tr>
        <tr><td>Peserta Hadir & Menyelesaikan</td><td>: {{ $kehadiran['selesai'] }} orang</td></tr>
        <tr><td>Peserta Sedang Mengerjakan</td><td>: {{ $kehadiran['sedang'] }} orang</td></tr>
        <tr><td>Peserta Tidak Hadir / Belum Mulai</td><td>: {{ $kehadiran['belum'] }} orang</td></tr>
        <tr><td>Peserta Diskualifikasi</td><td>: {{ $kehadiran['diskualifikasi'] }} orang</td></tr>
    </table>

    @if ($rekap->isNotEmpty())
    <p class="section-title">STATISTIK NILAI</p>
    @php
        $nilaiList = $rekap->pluck('nilai_akhir')->filter()->map(fn($v) => (float)$v);
        $rataRata = round($nilaiList->avg() ?? 0, 2);
        $tertinggi = $nilaiList->max() ?? 0;
        $terendah  = $nilaiList->min() ?? 0;
    @endphp
    <table class="stat">
        <tr><td>Nilai Rata-rata</td><td>: {{ number_format($rataRata, 2) }}</td></tr>
        <tr><td>Nilai Tertinggi</td><td>: {{ number_format($tertinggi, 2) }}</td></tr>
        <tr><td>Nilai Terendah</td><td>: {{ number_format($terendah, 2) }}</td></tr>
    </table>
    @endif

    <p class="section-title">CATATAN KEJADIAN</p>
    @php $notes = $session->notes()->with('author')->orderBy('created_at')->get(); @endphp
    @if ($notes->isEmpty())
    <p style="min-height:80px; border: 1px solid #000; padding: 8px; text-align:left">&nbsp;</p>
    @else
    <table style="width:100%; border-collapse:collapse; font-size:11px; margin-bottom:4px">
        <thead>
            <tr style="background:#f3f4f6">
                <th style="border:1px solid #ccc; padding:4px 8px; width:18%; text-align:left">Waktu</th>
                <th style="border:1px solid #ccc; padding:4px 8px; width:20%; text-align:left">Pengawas</th>
                <th style="border:1px solid #ccc; padding:4px 8px; text-align:left">Catatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($notes as $note)
            <tr>
                <td style="border:1px solid #ccc; padding:4px 8px; vertical-align:top">{{ $note->created_at?->format('H:i, d M Y') }}</td>
                <td style="border:1px solid #ccc; padding:4px 8px; vertical-align:top">{{ $note->author?->name ?? '—' }}</td>
                <td style="border:1px solid #ccc; padding:4px 8px; vertical-align:top; white-space:pre-line">{{ $note->catatan }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <p style="margin-top:16px">
        Demikian Berita Acara ini dibuat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.
    </p>

    <div class="ttd-row">
        <div class="ttd">
            <p>Peserta / Perwakilan,</p>
            <div class="ttd-line" style="width:180px">( .................................. )</div>
        </div>
        <div class="ttd">
            <p>........., {{ now()->format('d F Y') }}</p>
            <p>Pengawas / Guru Mata Pelajaran,</p>
            <div class="ttd-line">( {{ $session->creator?->name ?? '...........................' }} )</div>
        </div>
    </div>

</div>
<script>window.onload = function(){ window.print(); }</script>
</body>
</html>
