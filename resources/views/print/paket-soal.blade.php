<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soal — {{ $package->nama }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10.5pt;
            color: #111;
            background: #fff;
        }

        .page {
            max-width: 210mm;
            margin: 0 auto;
            padding: 12mm 18mm;
        }

        .kop {
            text-align: center;
            margin-bottom: 14px;
        }

        .kop img {
            height: 52px;
            margin-bottom: 4px;
        }

        .kop .school {
            font-size: 14pt;
            font-weight: bold;
        }

        .kop .judul {
            font-size: 13pt;
            font-weight: bold;
            margin-top: 6px;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 5px 0;
            letter-spacing: 0.04em;
        }

        .kop .subjudul {
            font-size: 11pt;
            margin-top: 3px;
        }

        .meta {
            font-size: 9.5pt;
            border: 1px solid #ccc;
            padding: 7px 12px;
            margin-bottom: 14px;
            border-radius: 3px;
        }

        .meta table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta td {
            padding: 2px 4px;
            vertical-align: top;
        }

        .meta td:first-child {
            width: 28%;
            font-weight: bold;
            white-space: nowrap;
        }

        .toolbar {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 16px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 7px 16px;
            border-radius: 6px;
            font-size: 10pt;
            cursor: pointer;
            text-decoration: none;
        }

        .btn:hover {
            background: #1d4ed8;
        }

        .btn-outline {
            background: transparent;
            color: #374151;
            border: 1px solid #d1d5db;
        }

        .soal {
            page-break-inside: avoid;
            margin-bottom: 20px;
        }

        .soal-head {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 5px;
        }

        .soal-no {
            font-size: 10.5pt;
            font-weight: bold;
            min-width: 28px;
        }

        .soal-teks {
            font-size: 10.5pt;
            line-height: 1.55;
        }

        .soal-teks img {
            max-width: 100%;
            height: auto;
            max-height: 150px;
        }

        .soal-teks p {
            margin: 0;
        }

        .opsi-list {
            margin: 8px 0 0 20px;
        }

        .opsi-item {
            display: flex;
            gap: 8px;
            align-items: flex-start;
            padding: 2px 0;
            font-size: 10pt;
        }

        .opsi-item .kode {
            min-width: 22px;
            font-weight: bold;
        }

        .opsi-item img {
            max-width: 80px;
            height: auto;
            vertical-align: middle;
        }

        .jodoh-table {
            margin: 8px 0 0 20px;
            border-collapse: collapse;
            font-size: 10pt;
        }

        .jodoh-table th {
            background: #f3f4f6;
            padding: 5px 12px;
            border: 1px solid #d1d5db;
            font-size: 9pt;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .jodoh-table td {
            padding: 5px 12px;
            border: 1px solid #d1d5db;
            min-width: 120px;
        }

        .jawab-line {
            border-bottom: 1px solid #aaa;
            min-width: 120px;
            height: 18px;
            display: inline-block;
        }

        .isian-blank {
            margin: 8px 0 0 20px;
            font-size: 10pt;
        }

        .isian-blank .dot-line {
            border-bottom: 1px dotted #aaa;
            display: inline-block;
            width: 250px;
            height: 16px;
        }

        .soal-divider {
            border: none;
            border-bottom: 1px dashed #e5e7eb;
            margin-bottom: 20px;
        }

        @media print {
            .toolbar {
                display: none !important;
            }

            body {
                font-size: 10pt;
            }

            .page {
                padding: 10mm 15mm;
            }
        }
    </style>
</head>

<body>
    <div class="page">

        <div class="toolbar">
            <button class="btn" onclick="window.print()">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path
                        d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v8H6z" />
                </svg>
                Cetak / Simpan PDF
            </button>
            <a class="btn btn-outline" href="javascript:history.back()">← Kembali</a>
        </div>

        <div class="kop">
            @if ($schoolLogoUrl)
                <div><img src="{{ $schoolLogoUrl }}" alt="Logo"></div>
            @endif
            @if ($schoolName)
                <div class="school">{{ strtoupper($schoolName) }}</div>
            @endif
            <div class="judul">NASKAH SOAL</div>
            <div class="subjudul">{{ strtoupper($package->nama) }}</div>
        </div>

        <div class="meta">
            <table>
                <tr>
                    <td>Mata Pelajaran</td>
                    <td>: {{ $package->mataPelajaran?->nama ?? '—' }}</td>
                    <td>Jumlah Soal</td>
                    <td>: {{ $questions->count() }} soal</td>
                </tr>
                <tr>
                    <td>Durasi</td>
                    <td>: {{ $package->durasi_menit ? $package->durasi_menit . ' menit' : '—' }}</td>
                    <td>Dicetak</td>
                    <td>: {{ now()->format('d F Y, H:i') }}</td>
                </tr>
                <tr>
                    <td>Pembuat Soal</td>
                    <td>: {{ $package->creator?->name ?? '—' }}</td>
                    <td>Kategori</td>
                    <td>: {{ $package->category?->nama ?? '—' }}</td>
                </tr>
            </table>
        </div>

        @foreach ($questions as $q)
            @php $no = $loop->iteration; @endphp
            <div class="soal">
                <div class="soal-head">
                    <span class="soal-no">{{ $no }}.</span>
                    <div class="soal-teks">
                        @if ($q->gambar_soal)
                            @php
                                $gambarSoalUrl = \Illuminate\Support\Str::startsWith($q->gambar_soal, ['http://', 'https://'])
                                    ? $q->gambar_soal
                                    : \Illuminate\Support\Facades\Storage::url($q->gambar_soal);
                            @endphp
                            <img src="{{ $gambarSoalUrl }}" alt="Gambar soal"
                                style="max-height:120px;margin-bottom:5px;display:block;">
                        @endif
                        {!! $q->teks_soal !!}
                    </div>
                </div>

                {{-- Opsi untuk PG/PG_BOBOT/PGJ/BS (tanpa menandai jawaban benar) --}}
                @if (in_array($q->tipe, ['PG', 'PG_BOBOT', 'PGJ', 'BS']))
                    <div class="opsi-list">
                        @foreach ($q->options as $opt)
                            @php
                                $gambarOpsiUrl = null;
                                if ($opt->gambar_opsi) {
                                    $gambarOpsiUrl = \Illuminate\Support\Str::startsWith($opt->gambar_opsi, ['http://', 'https://'])
                                        ? $opt->gambar_opsi
                                        : \Illuminate\Support\Facades\Storage::url($opt->gambar_opsi);
                                }
                            @endphp
                            <div class="opsi-item">
                                <span class="kode">{{ $opt->kode_opsi }}.</span>
                                <span>
                                    @if ($gambarOpsiUrl)
                                        <img src="{{ $gambarOpsiUrl }}" alt="">
                                    @endif
                                    {!! $opt->teks_opsi !!}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    {{-- JODOH: premis only, blank respon --}}
                @elseif ($q->tipe === 'JODOH')
                    <table class="jodoh-table">
                        <thead>
                            <tr>
                                <th>Pernyataan</th>
                                <th>Pasangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($q->matches as $idx => $m)
                                <tr>
                                    <td>{{ chr(65 + $idx) }}. {!! strip_tags($m->premis) !!}</td>
                                    <td><span class="jawab-line"></span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{-- ISIAN: dotted answer line --}}
                @elseif (in_array($q->tipe, ['ISIAN', 'URAIAN']))
                    <div class="isian-blank">
                        Jawaban: <span class="dot-line"></span>
                        @if ($q->tipe === 'URAIAN')
                            <br><span class="dot-line" style="margin-top:6px;"></span>
                            <br><span class="dot-line" style="margin-top:6px;"></span>
                        @endif
                    </div>

                    {{-- CLOZE --}}
                @elseif ($q->tipe === 'CLOZE')
                    <div class="isian-blank">
                        @foreach ($q->clozeBlank as $blank)
                            Rumpang {{ $blank->urutan }}: <span class="dot-line"></span><br>
                        @endforeach
                    </div>
                @endif
            </div>
            @if (!$loop->last)
                <hr class="soal-divider">
            @endif
        @endforeach

        {{-- Area tanda tangan --}}
        <div style="margin-top: 40px; display: flex; justify-content: space-between; font-size: 10pt;">
            <div style="text-align: center; min-width: 180px;">
                <div>Mengetahui,</div>
                <div style="font-weight: bold;">Kepala Sekolah</div>
                <div style="margin-top: 55px; border-top: 1px solid #000; padding-top: 4px;">
                    (...................................)
                </div>
            </div>
            <div style="text-align: center; min-width: 180px;">
                <div>{{ now()->locale('id')->isoFormat('D MMMM Y') }}</div>
                <div style="font-weight: bold;">Guru Mata Pelajaran</div>
                <div style="margin-top: 55px; border-top: 1px solid #000; padding-top: 4px;">
                    {{ $package->creator?->name ?? '.................................' }}
                </div>
            </div>
        </div>

    </div>
</body>

</html>