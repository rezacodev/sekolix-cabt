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
        .meta td:first-child { width: 22%; font-weight: bold; }
        table.kisi { width: 100%; border-collapse: collapse; font-size: 9pt; }
        table.kisi th, table.kisi td { border: 1px solid #000; padding: 4px 6px; vertical-align: top; }
        table.kisi thead tr { background: #e0e0e0; font-weight: bold; text-align: center; }
        table.kisi td.center { text-align: center; }
        table.kisi tfoot td { font-weight: bold; background: #f5f5f5; }
        .footer { margin-top: 28px; display: flex; justify-content: flex-end; font-size: 10pt; }
        .ttd { text-align: center; min-width: 180px; }
        .ttd .ttd-line { margin-top: 56px; border-top: 1px solid #000; padding-top: 4px; }
        .print-btn { position: fixed; bottom: 20px; right: 20px; padding: 10px 20px;
                     background: #2563eb; color: white; border: none; border-radius: 6px;
                     font-size: 12pt; cursor: pointer; }
        @page { size: A4 landscape; margin: 10mm 15mm; }
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
                <td style="width:22%;font-weight:bold;">Total Soal</td>
                <td>: {{ $blueprint->total_soal }}</td>
            </tr>
            @if ($blueprint->jenis_ujian)
            <tr>
                <td>Jenis Ujian</td>
                <td colspan="3">: {{ $blueprint->jenis_ujian }}</td>
            </tr>
            @endif
            @if ($blueprint->kelas || $blueprint->bab)
            <tr>
                <td>Kelas / Semester</td>
                <td>: {{ $blueprint->kelas ?? '—' }}</td>
                <td style="font-weight:bold;">Bab</td>
                <td>: {{ $blueprint->bab ?? '—' }}</td>
            </tr>
            @endif
            @if ($blueprint->penyusun || $blueprint->tahun_ajaran)
            <tr>
                <td>Penyusun</td>
                <td>: {{ $blueprint->penyusun ?? '—' }}</td>
                <td style="font-weight:bold;">Tahun Ajaran</td>
                <td>: {{ $blueprint->tahun_ajaran ?? '—' }}</td>
            </tr>
            @endif
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
        $totalSoal  = $blueprint->items->sum('jumlah_soal');
        $totalBobot = $blueprint->items->sum(fn($i) => $i->jumlah_soal * $i->bobot_per_soal);
    @endphp

    <table class="kisi">
        <thead>
            <tr>
                <th style="width:3%">No</th>
                <th style="width:12%">Capaian Pembelajaran</th>
                <th style="width:10%">Materi</th>
                <th style="width:16%">Indikator</th>
                <th style="width:10%">Kategori</th>
                <th style="width:10%">KD / CP</th>
                <th style="width:7%">Tipe Soal</th>
                <th style="width:7%">Kesulitan</th>
                <th style="width:5%">Bloom</th>
                <th style="width:5%">Tag</th>
                <th style="width:5%">Jml</th>
                <th style="width:5%">Bobot</th>
                <th style="width:5%">Total</th>
                <th style="width:5%">No. Soal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($blueprint->items as $item)
            <tr>
                <td class="center">{{ $loop->iteration }}</td>
                <td>{{ $item->capaian_pembelajaran ?? '—' }}</td>
                <td>{{ $item->materi ?? '—' }}</td>
                <td><small>{{ $item->indikator ?? '—' }}</small></td>
                <td>{{ optional($item->category)->nama ?? '—' }}</td>
                <td>
                    @if ($item->standard)
                        <strong>{{ $item->standard->kode }}</strong><br>
                        <small>{{ Str::limit($item->standard->nama, 40) }}</small>
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
                <td class="center">{{ $item->nomor_soal ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="10" class="center">TOTAL</td>
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
            <div class="ttd-line">{{ $blueprint->penyusun ?? optional($blueprint->creator)->name ?? '___________________' }}</div>
        </div>
    </div>

</div>

<button class="print-btn no-print" onclick="window.print()">&#128438; Cetak</button>
</body>
</html>
