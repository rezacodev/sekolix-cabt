<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kisi-kisi — {{ $blueprint->nama }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Times New Roman', serif; font-size: 11pt; color: #000; background: #fff; }
        .page { max-width: 297mm; margin: 0 auto; padding: 15mm 20mm; }
        h1 { font-size: 14pt; font-weight: bold; text-align: center; margin-bottom: 4px; text-transform: uppercase; }
        h2 { font-size: 12pt; text-align: center; margin-bottom: 12px; }
        .meta { font-size: 10pt; border: 1px solid #000; padding: 8px 12px; margin-bottom: 16px; }
        .meta table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 2px 4px; vertical-align: top; }
        .meta td:first-child { width: 30%; font-weight: bold; }
        table.kisi { width: 100%; border-collapse: collapse; font-size: 10pt; }
        table.kisi th, table.kisi td { border: 1px solid #000; padding: 5px 7px; vertical-align: top; }
        table.kisi thead tr { background: #e0e0e0; font-weight: bold; text-align: center; }
        table.kisi td.center { text-align: center; }
        table.kisi tfoot td { font-weight: bold; background: #f5f5f5; }
        .footer { margin-top: 28px; display: flex; justify-content: flex-end; font-size: 10pt; }
        .ttd { text-align: center; min-width: 180px; }
        .ttd .ttd-line { margin-top: 56px; border-top: 1px solid #000; padding-top: 4px; }
        .print-btn { position: fixed; bottom: 20px; right: 20px; padding: 10px 20px;
                     background: #2563eb; color: white; border: none; border-radius: 6px;
                     font-size: 12pt; cursor: pointer; }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
<div class="page">

    @if ($schoolName || $schoolLogoUrl)
    <div style="text-align:center;margin-bottom:8px;">
        @if ($schoolLogoUrl)
            <img src="{{ $schoolLogoUrl }}" alt="logo" style="height:48px;margin-bottom:4px;display:block;margin-left:auto;margin-right:auto;">
        @endif
        @if ($schoolName)
            <div style="font-size:13pt;font-weight:bold;">{{ strtoupper($schoolName) }}</div>
        @endif
    </div>
    <hr style="border:1.5px solid #000;margin-bottom:10px;">
    @endif

    <h1>KISI-KISI SOAL</h1>
    <h2>{{ $blueprint->nama }}</h2>

    <div class="meta">
        <table>
            <tr>
                <td>Mata Pelajaran</td>
                <td>: {{ $blueprint->mata_pelajaran }}</td>
                <td style="width:25%;font-weight:bold;">Total Soal</td>
                <td>: {{ $blueprint->total_soal }}</td>
            </tr>
            @if ($blueprint->deskripsi)
            <tr>
                <td>Keterangan</td>
                <td colspan="3">: {{ $blueprint->deskripsi }}</td>
            </tr>
            @endif
            <tr>
                <td>Dibuat Oleh</td>
                <td>: {{ optional($blueprint->creator)->name ?? '—' }}</td>
                <td style="font-weight:bold;">Tanggal Cetak</td>
                <td>: {{ now()->translatedFormat('d F Y') }}</td>
            </tr>
        </table>
    </div>

    @php
        $totalBaris = $blueprint->items->count();
        $totalSoal  = $blueprint->items->sum('jumlah_soal');
        $totalBobot = $blueprint->items->sum(fn($i) => $i->jumlah_soal * $i->bobot_per_soal);
    @endphp

    <table class="kisi">
        <thead>
            <tr>
                <th style="width:4%">No</th>
                <th style="width:14%">Kategori</th>
                <th style="width:14%">KD / CP</th>
                <th style="width:10%">Tipe Soal</th>
                <th style="width:10%">Kesulitan</th>
                <th style="width:8%">Bloom</th>
                <th style="width:8%">Tag</th>
                <th style="width:8%">Jml Soal</th>
                <th style="width:9%">Bobot/Soal</th>
                <th style="width:9%">Total Bobot</th>
                <th style="width:16%">Keterangan Kriteria</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($blueprint->items as $item)
            <tr>
                <td class="center">{{ $loop->iteration }}</td>
                <td>{{ optional($item->category)->nama ?? '—' }}</td>
                <td>
                    @if ($item->standard)
                        <strong>{{ $item->standard->kode }}</strong><br>
                        <small>{{ Str::limit($item->standard->nama, 60) }}</small>
                    @else
                        —
                    @endif
                </td>
                <td class="center">{{ $item->tipe_soal ?? 'Semua' }}</td>
                <td class="center">{{ ucfirst($item->tingkat_kesulitan ?? 'Semua') }}</td>
                <td class="center">{{ $item->bloom_level ?? '—' }}</td>
                <td class="center">{{ optional($item->tag)->nama ?? '—' }}</td>
                <td class="center">{{ $item->jumlah_soal }}</td>
                <td class="center">{{ number_format($item->bobot_per_soal, 1) }}</td>
                <td class="center">{{ number_format($item->jumlah_soal * $item->bobot_per_soal, 1) }}</td>
                <td><small>{{ $item->kriteria_label }}</small></td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="7" class="center">TOTAL</td>
                <td class="center">{{ $totalSoal }}</td>
                <td class="center">—</td>
                <td class="center">{{ number_format($totalBobot, 1) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <div class="ttd">
            <div>{{ now()->translatedFormat('d F Y') }}</div>
            <div>Guru / Penyusun,</div>
            <div class="ttd-line">{{ optional($blueprint->creator)->name ?? '___________________' }}</div>
        </div>
    </div>

</div>

<button class="print-btn no-print" onclick="window.print()">&#128438; Cetak</button>
</body>
</html>
