<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kunci Jawaban — {{ $package->nama }}</title>
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

        /* Header */
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

        /* Meta info */
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

        /* No-print toolbar */
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

        .btn-outline:hover {
            background: #f9fafb;
        }

        /* Question item */
        .soal {
            page-break-inside: avoid;
            margin-bottom: 18px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            overflow: hidden;
        }

        .soal-head {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            background: #f9fafb;
            padding: 7px 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        .soal-no {
            font-size: 10pt;
            font-weight: bold;
            color: #111;
            min-width: 28px;
        }

        .soal-tipe {
            display: inline-block;
            font-size: 8pt;
            font-weight: bold;
            padding: 2px 7px;
            border-radius: 99px;
            background: #e0e7ff;
            color: #3730a3;
            white-space: nowrap;
        }

        .soal-bobot {
            margin-left: auto;
            font-size: 8.5pt;
            color: #6b7280;
            white-space: nowrap;
        }

        .soal-body {
            padding: 8px 12px;
        }

        .soal-teks {
            font-size: 10pt;
            color: #111;
            margin-bottom: 8px;
            line-height: 1.5;
        }

        .soal-teks img {
            max-width: 100%;
            height: auto;
            max-height: 120px;
        }

        .soal-teks p {
            margin: 0;
        }

        /* Kunci */
        .kunci-label {
            font-size: 8.5pt;
            font-weight: bold;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 5px;
        }

        .kunci-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 5px;
            padding: 8px 12px;
        }

        .kunci-opsi {
            display: flex;
            gap: 8px;
            align-items: flex-start;
            padding: 3px 0;
            font-size: 10pt;
        }

        .kunci-opsi.correct {
            font-weight: bold;
            color: #15803d;
        }

        .kunci-opsi .kode {
            min-width: 22px;
            font-weight: bold;
        }

        .kunci-opsi img {
            max-width: 80px;
            height: auto;
            vertical-align: middle;
        }

        .kunci-manual {
            background: #fafafa;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            padding: 7px 12px;
            font-size: 9.5pt;
            color: #6b7280;
            font-style: italic;
        }

        .kunci-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
        }

        .kunci-table th {
            background: #dcfce7;
            padding: 5px 8px;
            text-align: left;
            font-size: 8.5pt;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border: 1px solid #bbf7d0;
        }

        .kunci-table td {
            padding: 5px 8px;
            border: 1px solid #bbf7d0;
            vertical-align: top;
        }

        .kunci-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .kunci-tag {
            background: #dcfce7;
            color: #15803d;
            font-size: 9pt;
            padding: 2px 9px;
            border-radius: 99px;
            border: 1px solid #bbf7d0;
        }

        .kunci-blank {
            margin-bottom: 4px;
            font-size: 9.5pt;
        }

        .kunci-blank .blank-no {
            display: inline-block;
            min-width: 60px;
            font-weight: bold;
            color: #374151;
        }

        /* Pembahasan */
        .pembahasan {
            margin-top: 8px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 5px;
            padding: 7px 12px;
            font-size: 9.5pt;
            color: #1e40af;
        }

        .pembahasan .pb-label {
            font-weight: bold;
            font-size: 8.5pt;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 4px;
        }

        .pembahasan img {
            max-width: 100%;
            height: auto;
            max-height: 120px;
        }

        /* Signature area */
        .ttd {
            margin-top: 30px;
            display: flex;
            justify-content: flex-end;
            font-size: 10pt;
        }

        .ttd-box {
            text-align: center;
        }

        .ttd-box .ttd-line {
            margin-top: 50px;
            border-top: 1px solid #000;
            padding-top: 4px;
            min-width: 160px;
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

            .soal {
                border: none;
                border-bottom: 1px dashed #ccc;
                border-radius: 0;
            }

            .soal-head {
                background: transparent;
                border-bottom: 1px solid #ddd;
            }
        }
    </style>
</head>

<body>
    <div class="page">

        {{-- Toolbar (no-print) --}}
        <div class="toolbar">
            <button class="btn" onclick="window.print()">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path
                        d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v8H6z" />
                </svg>
                Cetak / Simpan PDF
            </button>
            <a class="btn btn-outline" href="javascript:history.back()">← Kembali</a>
            <span style="font-size:9pt;color:#6b7280;margin-left:auto;">Tip: di dialog print, pilih "Simpan sebagai PDF"
                untuk ekspor PDF.</span>
        </div>

        {{-- Kop --}}
        <div class="kop">
            @if ($schoolLogoUrl)
                <div><img src="{{ $schoolLogoUrl }}" alt="Logo"></div>
            @endif
            @if ($schoolName)
                <div class="school">{{ strtoupper($schoolName) }}</div>
            @endif
            <div class="judul">KUNCI JAWABAN</div>
            <div class="subjudul">{{ strtoupper($package->nama) }}</div>
        </div>

        {{-- Meta --}}
        <div class="meta">
            <table>
                <tr>
                    <td>Mata Pelajaran</td>
                    <td>: {{ $package->mataPelajaran?->nama ?? '—' }}</td>
                    <td>Jumlah Soal</td>
                    <td>: {{ $questions->count() }} soal</td>
                </tr>
                <tr>
                    <td>Total Bobot</td>
                    <td>: {{ $questions->sum('bobot') }}</td>
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

        {{-- Questions --}}
        @foreach ($questions as $q)
            @php
                $no = $loop->iteration;
                $tipeLabel = \App\Models\Question::TIPE_LABELS[$q->tipe] ?? $q->tipe;
            @endphp
            <div class="soal">
                <div class="soal-head">
                    <span class="soal-no">{{ $no }}.</span>
                    <span class="soal-tipe">{{ $tipeLabel }}</span>
                    @if ($q->category)
                        <span style="font-size:8.5pt;color:#6b7280;">{{ $q->category->nama }}</span>
                    @endif
                    @if ($q->kelas)
                        <span style="font-size:8.5pt;color:#6b7280;">Kelas {{ $q->kelas }}</span>
                    @endif
                    <span class="soal-bobot">Bobot: {{ $q->bobot }}</span>
                </div>

                <div class="soal-body">
                    {{-- Teks soal (singkat) --}}
                    <div class="soal-teks">
                        @if ($q->gambar_soal)
                            @php
                                $gambarSoalUrl = \Illuminate\Support\Str::startsWith($q->gambar_soal, ['http://', 'https://'])
                                    ? $q->gambar_soal
                                    : \Illuminate\Support\Facades\Storage::url($q->gambar_soal);
                            @endphp
                            <img src="{{ $gambarSoalUrl }}" alt="Gambar soal" style="max-height:100px;margin-bottom:5px;">
                        @endif
                        {!! $q->teks_soal !!}
                    </div>

                    {{-- Kunci Jawaban --}}
                    <div class="kunci-label">Kunci Jawaban</div>

                    @if (in_array($q->tipe, ['PG', 'PG_BOBOT', 'PGJ', 'BS']))
                        <div class="kunci-box">
                            @foreach ($q->options as $opt)
                                @php
                                    $gambarOpsiUrl = null;
                                    if ($opt->gambar_opsi) {
                                        $gambarOpsiUrl = \Illuminate\Support\Str::startsWith($opt->gambar_opsi, ['http://', 'https://'])
                                            ? $opt->gambar_opsi
                                            : \Illuminate\Support\Facades\Storage::url($opt->gambar_opsi);
                                    }
                                @endphp
                                <div class="kunci-opsi {{ $opt->is_correct ? 'correct' : '' }}">
                                    <span class="kode">{{ $opt->kode_opsi }}.</span>
                                    <span>
                                        @if ($gambarOpsiUrl)
                                            <img src="{{ $gambarOpsiUrl }}" alt="">
                                        @endif
                                        {!! $opt->teks_opsi !!}
                                        @if ($q->tipe === 'PG_BOBOT' && $opt->bobot_persen !== null)
                                            <span style="font-size:8.5pt;color:#6b7280;">({{ $opt->bobot_persen }}%)</span>
                                        @endif
                                        @if ($opt->is_correct) ✓ @endif
                                    </span>
                                </div>
                            @endforeach
                        </div>

                    @elseif ($q->tipe === 'JODOH')
                        <div class="kunci-box">
                            <table class="kunci-table">
                                <thead>
                                    <tr>
                                        <th>Premis</th>
                                        <th>Jawaban</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($q->matches as $m)
                                        <tr>
                                            <td>{!! strip_tags($m->premis) !!}</td>
                                            <td>{!! strip_tags($m->respon) !!}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                    @elseif ($q->tipe === 'ISIAN')
                        <div class="kunci-box">
                            <div class="kunci-label" style="margin-bottom:6px;">Kata Kunci yang Diterima:</div>
                            <div class="kunci-tags">
                                @foreach ($q->keywords as $kw)
                                    <span class="kunci-tag">{{ $kw->keyword }}</span>
                                @endforeach
                            </div>
                        </div>

                    @elseif ($q->tipe === 'CLOZE')
                        <div class="kunci-box">
                            @foreach ($q->clozeBlank as $blank)
                                <div class="kunci-blank">
                                    <span class="blank-no">Rumpang {{ $blank->urutan }}</span>
                                    {{ $blank->jawaban_benar }}
                                    @if ($blank->keywords_json)
                                        @php $synonyms = json_decode($blank->keywords_json, true) ?? []; @endphp
                                        @if (count($synonyms) > 0)
                                            <span style="font-size:8.5pt;color:#6b7280;"> (sinonim: {{ implode(', ', $synonyms) }})</span>
                                        @endif
                                    @endif
                                </div>
                            @endforeach
                        </div>

                    @elseif ($q->tipe === 'URAIAN')
                        <div class="kunci-manual">⊘ Dinilai manual oleh guru</div>
                    @endif

                    {{-- Pembahasan --}}
                    @if ($q->penjelasan)
                        <div class="pembahasan">
                            <div class="pb-label">Pembahasan</div>
                            <div>{!! $q->penjelasan !!}</div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach

        {{-- Footer tanda tangan --}}
        <div class="ttd">
            <div class="ttd-box">
                <div>{{ now()->format('d F Y') }}</div>
                <div>Guru / Pengampu</div>
                <div class="ttd-line">{{ $package->creator?->name ?? '___________________' }}</div>
            </div>
        </div>

    </div>
</body>

</html>