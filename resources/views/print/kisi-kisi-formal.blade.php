<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kisi-kisi Formal — {{ $blueprint->nama }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Times New Roman', serif; font-size: 10pt; color: #000; background: #fff; }
        .page { max-width: 277mm; margin: 0 auto; padding: 10mm 15mm; }

        h1 { font-size: 13pt; font-weight: bold; text-align: center; text-transform: uppercase; margin-bottom: 2px; }
        h2 { font-size: 12pt; font-weight: bold; text-align: center; margin-bottom: 14px; }

        table.header-info { width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 10pt; }
        table.header-info td { padding: 2px 4px; vertical-align: top; }
        table.header-info td.label { width: 30%; font-weight: normal; text-transform: uppercase; }
        table.header-info td.sep { width: 2%; }
        table.header-info td.value { width: 18%; }
        table.header-info .col-right td.label { width: 30%; }

        table.kisi { width: 100%; border-collapse: collapse; font-size: 10pt; }
        table.kisi th, table.kisi td { border: 1px solid #000; padding: 5px 7px; vertical-align: middle; }
        table.kisi thead tr { background: #d0d0d0; font-weight: bold; text-align: center; }
        table.kisi td.center { text-align: center; }
        table.kisi td.cp-cell { font-weight: bold; text-align: center; }
        table.kisi td.materi-cell { text-align: center; }

        .footer { margin-top: 24px; display: flex; justify-content: flex-end; font-size: 10pt; }
        .ttd { text-align: center; min-width: 180px; }
        .ttd .ttd-line { margin-top: 56px; border-top: 1px solid #000; padding-top: 4px; font-weight: bold; }

        .print-btn { position: fixed; bottom: 20px; right: 20px; padding: 10px 20px;
                     background: #d97706; color: white; border: none; border-radius: 6px;
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

    {{-- Judul --}}
    <h1>KISI-KISI PENULISAN SOAL {{ strtoupper($blueprint->jenis_ujian ?? '') }}</h1>
    <h2>TAHUN AJARAN {{ $blueprint->tahun_ajaran ?? '—' }}</h2>

    {{-- Header 2-kolom --}}
    @php
        $bentukSoalStr = implode(', ', $bentukSoalList) ?: '—';
    @endphp
    <table style="width:100%;border-collapse:collapse;margin-bottom:14px;font-size:10pt;">
        <tr>
            <td style="width:48%;vertical-align:top;">
                <table style="width:100%;border-collapse:collapse;">
                    <tr>
                        <td style="width:40%;text-transform:uppercase;font-weight:bold;padding:2px 4px;">Sekolah</td>
                        <td style="width:2%;padding:2px 2px;">:</td>
                        <td style="padding:2px 4px;">{{ strtoupper($schoolName ?: '—') }}</td>
                    </tr>
                    <tr>
                        <td style="text-transform:uppercase;font-weight:bold;padding:2px 4px;">Mata Pelajaran</td>
                        <td style="padding:2px 2px;">:</td>
                        <td style="padding:2px 4px;">{{ strtoupper($blueprint->mata_pelajaran ?? '—') }}</td>
                    </tr>
                    <tr>
                        <td style="text-transform:uppercase;font-weight:bold;padding:2px 4px;">Jenis Ujian / Ulangan</td>
                        <td style="padding:2px 2px;">:</td>
                        <td style="padding:2px 4px;">{{ strtoupper($blueprint->jenis_ujian ?? '—') }}</td>
                    </tr>
                    <tr>
                        <td style="text-transform:uppercase;font-weight:bold;padding:2px 4px;">Kelas / Semester</td>
                        <td style="padding:2px 2px;">:</td>
                        <td style="padding:2px 4px;">{{ $blueprint->kelas ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td style="text-transform:uppercase;font-weight:bold;padding:2px 4px;">Bab</td>
                        <td style="padding:2px 2px;">:</td>
                        <td style="padding:2px 4px;">{{ $blueprint->bab ?? '—' }}</td>
                    </tr>
                </table>
            </td>
            <td style="width:4%;"></td>
            <td style="width:48%;vertical-align:top;">
                <table style="width:100%;border-collapse:collapse;">
                    <tr>
                        <td style="width:40%;text-transform:uppercase;font-weight:bold;padding:2px 4px;">Bentuk Soal</td>
                        <td style="width:2%;padding:2px 2px;">:</td>
                        <td style="padding:2px 4px;">{{ $bentukSoalStr }}</td>
                    </tr>
                    <tr>
                        <td style="text-transform:uppercase;font-weight:bold;padding:2px 4px;">Jumlah Soal</td>
                        <td style="padding:2px 2px;">:</td>
                        <td style="padding:2px 4px;">{{ $blueprint->total_soal }} SOAL</td>
                    </tr>
                    <tr>
                        <td style="text-transform:uppercase;font-weight:bold;padding:2px 4px;">Penyusun</td>
                        <td style="padding:2px 2px;">:</td>
                        <td style="padding:2px 4px;">{{ strtoupper($blueprint->penyusun ?? optional($blueprint->creator)->name ?? '—') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Tabel Kisi-kisi dengan Rowspan --}}
    @php
        // Group items by capaian_pembelajaran → materi (items sudah disorting di controller)
        $grouped = [];
        foreach ($blueprint->items as $item) {
            $cp  = $item->capaian_pembelajaran ?: '(tidak diisi)';
            $mat = $item->materi ?: '(tidak diisi)';
            $grouped[$cp][$mat][] = $item;
        }

        // Hitung rowspan
        $cpSpan  = [];
        $matSpan = [];
        foreach ($grouped as $cp => $matGroup) {
            $cpSpan[$cp] = 0;
            foreach ($matGroup as $mat => $rows) {
                $matSpan[$cp][$mat] = count($rows);
                $cpSpan[$cp]       += count($rows);
            }
        }
    @endphp

    <table class="kisi">
        <thead>
            <tr>
                <th style="width:4%">NO</th>
                <th style="width:18%">CAPAIAN PEMBELAJARAN</th>
                <th style="width:15%">MATERI</th>
                <th style="width:35%">INDIKATOR</th>
                <th style="width:14%">BENTUK INSTRUMEN</th>
                <th style="width:14%">NOMOR SOAL</th>
            </tr>
        </thead>
        <tbody>
            @php $rowNum = 1; @endphp
            @foreach ($grouped as $cp => $matGroup)
                @php $cpFirst = true; @endphp
                @foreach ($matGroup as $mat => $rows)
                    @php $matFirst = true; @endphp
                    @foreach ($rows as $item)
                    <tr>
                        <td class="center">{{ $rowNum++ }}</td>

                        @if ($cpFirst && $matFirst)
                            <td class="cp-cell" rowspan="{{ $cpSpan[$cp] }}"
                                style="vertical-align:middle;">{{ $cp }}</td>
                        @endif

                        @if ($matFirst)
                            <td class="materi-cell" rowspan="{{ $matSpan[$cp][$mat] }}"
                                style="vertical-align:middle;">{{ $mat }}</td>
                        @endif

                        <td>{{ $item->indikator ?? '—' }}</td>
                        <td class="center">{{ $bentukMap[$item->tipe_soal] ?? ($item->tipe_soal ?? '—') }}</td>
                        <td class="center">{{ $item->nomor_soal ?? '—' }}</td>
                    </tr>
                    @php $cpFirst = false; $matFirst = false; @endphp
                    @endforeach
                @endforeach
            @endforeach
        </tbody>
    </table>

    {{-- TTD --}}
    <div class="footer">
        <div class="ttd">
            <div>{{ now()->translatedFormat('d F Y') }}</div>
            <div>Penyusun,</div>
            <div class="ttd-line">
                {{ $blueprint->penyusun ?? optional($blueprint->creator)->name ?? '___________________' }}
            </div>
        </div>
    </div>

</div>

<button class="print-btn no-print" onclick="window.print()">&#128438; Cetak</button>
</body>
</html>
