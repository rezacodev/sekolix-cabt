<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisis Ulangan Harian — {{ $session->nama_sesi }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Times New Roman', serif; font-size: 10pt; color: #000; background: #fff; }

        /* ── Layout ─────────────────────────────────── */
        .halaman { max-width: 297mm; margin: 0 auto; padding: 12mm 15mm; page-break-after: always; }
        .halaman:last-child { page-break-after: auto; }
        .halaman-portrait { max-width: 210mm; }

        /* ── Header ─────────────────────────────────── */
        .judul { font-size: 13pt; font-weight: bold; text-align: center; margin-bottom: 2px; text-transform: uppercase; }
        .sekolah { font-size: 11pt; font-weight: bold; text-align: center; margin-bottom: 10px; }

        /* ── Meta info ──────────────────────────────── */
        .info { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 10pt; }
        .info td { padding: 1px 4px; vertical-align: top; }
        .info td.lbl { width: 25%; }
        .info td.sep { width: 1%; }

        /* ── Tabel utama ────────────────────────────── */
        table.data { width: 100%; border-collapse: collapse; font-size: 9pt; }
        table.data th, table.data td { border: 1px solid #000; padding: 3px 4px; text-align: center; vertical-align: middle; }
        table.data th { background: #e8e8e8; font-weight: bold; }
        table.data td.nama { text-align: left; white-space: nowrap; }
        table.data tr.total-row td { font-weight: bold; background: #f0f0f0; }
        table.data tr.persen-row td { font-style: italic; }

        /* ── Distribusi ─────────────────────────────── */
        .distribusi { display: flex; gap: 6px; margin-bottom: 12px; }
        .dist-box { flex: 1; border: 1px solid #000; padding: 4px; text-align: center; font-size: 9pt; }
        .dist-box .num { font-size: 16pt; font-weight: bold; }

        /* ── TTD ────────────────────────────────────── */
        .ttd-area { margin-top: 20px; display: flex; justify-content: space-between; font-size: 10pt; }
        .ttd-block { text-align: center; width: 200px; }
        .ttd-block .ttd-line { margin-top: 50px; border-top: 1px solid #000; padding-top: 2px; font-weight: bold; }
        .ttd-block .nip { font-size: 9pt; }

        /* ── Program tabel ──────────────────────────── */
        table.prog { width: 100%; border-collapse: collapse; font-size: 10pt; }
        table.prog th, table.prog td { border: 1px solid #000; padding: 4px 6px; vertical-align: top; }
        table.prog th { background: #e8e8e8; text-align: center; font-weight: bold; }
        table.prog td.center { text-align: center; }

        /* ── Hasil Analisis ─────────────────────────── */
        .has-section { margin-bottom: 16px; }
        .has-section h3 { font-size: 11pt; font-weight: bold; margin-bottom: 6px; }
        .has-list { margin-left: 20px; font-size: 10pt; }
        .has-list li { margin-bottom: 2px; }

        /* ── Print ──────────────────────────────────── */
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            @page { size: A4 landscape; margin: 10mm 12mm; }
            @page portrait { size: A4 portrait; margin: 10mm 15mm; }
            .halaman-portrait { page: portrait; }
        }
    </style>
</head>
<body>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- HALAMAN COVER — SAMPUL ANALISIS ULANGAN HARIAN                            --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="halaman halaman-portrait" style="display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;">

    @if ($schoolLogoUrl)
        <div style="margin-bottom: 20px;">
            <img src="{{ $schoolLogoUrl }}" alt="Logo" style="height: 80px; margin-bottom: 10px;">
        </div>
    @endif

    @if ($schoolName)
        <div style="font-size: 14pt; font-weight: bold; margin-bottom: 30px; text-transform: uppercase;">
            {{ $schoolName }}
        </div>
    @endif

    <div style="margin-bottom: 50px;">
        <div style="font-size: 16pt; font-weight: bold; margin-bottom: 15px; text-transform: uppercase;">
            Analisis Ulangan Harian
        </div>
        <div style="font-size: 12pt; margin-bottom: 10px;">
            Program Perbaikan dan Pengayaan
        </div>
        <div style="font-size: 12pt;">
            Laporan Pelaksanaan Perbaikan dan Pengayaan
        </div>
    </div>

    <div style="margin-bottom: 50px; font-size: 24pt; font-weight: bold; letter-spacing: 8px;">
        O<br>L<br>E<br>H
    </div>

    <div style="text-align: left; margin-top: auto; font-size: 11pt; line-height: 1.8;">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="width: 40%; border: none; padding: 3px 0;">NAMA</td>
                <td style="border: none; padding: 3px 0;">: {{ $session->creator?->name ?? '—' }}</td>
            </tr>
            <tr>
                <td style="border: none; padding: 3px 0;">NIP</td>
                <td style="border: none; padding: 3px 0;">: —</td>
            </tr>
            <tr>
                <td style="border: none; padding: 3px 0;">KELAS / SEMESTER</td>
                <td style="border: none; padding: 3px 0;">: {{ $session->nama_sesi }}</td>
            </tr>
            <tr>
                <td style="border: none; padding: 3px 0;">TAHUN PELAJARAN</td>
                <td style="border: none; padding: 3px 0;">: {{ $session->waktu_mulai?->format('Y') ?? '—' }}</td>
            </tr>
            <tr>
                <td style="border: none; padding: 3px 0;">POKOK BAHASAN</td>
                <td style="border: none; padding: 3px 0;">: {{ $session->package?->nama ?? '—' }}</td>
            </tr>
            <tr>
                <td style="border: none; padding: 3px 0;">MATA PELAJARAN</td>
                <td style="border: none; padding: 3px 0;">: {{ $session->package?->nama ?? '—' }}</td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 60px; font-size: 10pt;">
        {{ $session->waktu_mulai?->format('F Y') ?? now()->format('F Y') }}
    </div>

</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- HALAMAN 1 — ANALISIS ULANGAN HARIAN                                       --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="halaman">

    <div class="judul">Analisis Ulangan Harian</div>
    @if ($schoolName)
        <div class="sekolah">{{ strtoupper($schoolName) }}</div>
    @endif

    {{-- Info sesi --}}
    <table class="info">
        <tr>
            <td class="lbl">Mata Pelajaran</td><td class="sep">:</td><td>{{ $analisisData['session']->package?->nama ?? '—' }}</td>
            <td class="lbl">Jumlah Peserta</td><td class="sep">:</td><td>{{ $analisisData['peserta_data']->count() }}</td>
        </tr>
        <tr>
            <td class="lbl">Kelas/Semester</td><td class="sep">:</td><td>{{ $session->nama_sesi }}</td>
            <td class="lbl">Jumlah Soal</td><td class="sep">:</td><td>{{ $analisisData['soal_list']->count() }}</td>
        </tr>
        <tr>
            <td class="lbl">Tanggal</td><td class="sep">:</td><td>{{ $session->waktu_mulai?->format('d F Y') ?? '—' }}</td>
            <td class="lbl">Bentuk Soal</td><td class="sep">:</td><td>Essay / Pilihan Ganda</td>
        </tr>
        <tr>
            <td class="lbl">KKM</td><td class="sep">:</td><td>{{ $analisisData['session']->kkm ?? 70 }}</td>
            <td class="lbl">Guru</td><td class="sep">:</td><td>{{ $session->creator?->name ?? '—' }}</td>
        </tr>
    </table>

    {{-- Tabel Utama --}}
    <table class="data">
        <thead>
            <tr>
                <th rowspan="2">No</th>
                <th rowspan="2">Nama Peserta</th>
                {{-- Kolom soal --}}
                @foreach ($analisisData['soal_list'] as $idx => $soal)
                    <th>{{ $idx + 1 }}</th>
                @endforeach
                <th rowspan="2">Jml Skor</th>
                <th rowspan="2">%</th>
                <th colspan="2">Ketuntasan</th>
            </tr>
            <tr>
                {{-- Bobot per soal --}}
                @foreach ($analisisData['soal_stats'] as $stat)
                    <th style="font-size:8pt;">{{ number_format($stat->bobot, 0) }}</th>
                @endforeach
                <th>Ya</th>
                <th>Tdk</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($analisisData['peserta_data'] as $no => $peserta)
            <tr>
                <td>{{ $no + 1 }}</td>
                <td class="nama"><strong>{{ $peserta->nama }}</strong></td>
                @foreach ($peserta->skor_per_soal as $skor)
                    <td>{{ $skor > 0 ? number_format($skor, 0) : '0' }}</td>
                @endforeach
                <td><strong>{{ number_format($peserta->total, 0) }}</strong></td>
                <td>{{ $peserta->persen }}%</td>
                <td>{{ $peserta->tuntas ? '√' : '' }}</td>
                <td>{{ !$peserta->tuntas ? '√' : '' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            {{-- Distribusi baris --}}
            <tr class="total-row">
                <td colspan="2" style="text-align:left;">Skor &lt; {{ $analisisData['session']->kkm ?? 70 }}</td>
                @foreach ($analisisData['soal_stats'] as $stat)
                    <td></td>
                @endforeach
                <td>{{ $analisisData['distribusi']['tidak_tuntas'] }}</td>
                <td></td><td></td><td></td>
            </tr>
            <tr class="total-row">
                <td colspan="2" style="text-align:left;">Skor {{ $analisisData['session']->kkm ?? 70 }} – {{ $analisisData['session']->pengayaan_max_1 ?? 83 }}</td>
                @foreach ($analisisData['soal_stats'] as $stat)
                    <td></td>
                @endforeach
                <td>{{ $analisisData['distribusi']['tuntas_rendah'] }}</td>
                <td></td><td></td><td></td>
            </tr>
            <tr class="total-row">
                <td colspan="2" style="text-align:left;">Skor {{ ($analisisData['session']->pengayaan_max_1 ?? 83) + 1 }} – {{ $analisisData['session']->pengayaan_max_2 ?? 92 }}</td>
                @foreach ($analisisData['soal_stats'] as $stat)
                    <td></td>
                @endforeach
                <td>{{ $analisisData['distribusi']['tuntas_sedang'] }}</td>
                <td></td><td></td><td></td>
            </tr>
            <tr class="total-row">
                <td colspan="2" style="text-align:left;">Skor {{ ($analisisData['session']->pengayaan_max_2 ?? 92) + 1 }} – 100</td>
                @foreach ($analisisData['soal_stats'] as $stat)
                    <td></td>
                @endforeach
                <td>{{ $analisisData['distribusi']['tuntas_tinggi'] }}</td>
                <td></td><td></td><td></td>
            </tr>
            <tr>
                <td colspan="2" style="text-align:left;">Jml Skor yang diperoleh</td>
                @foreach ($analisisData['soal_stats'] as $stat)
                    <td>{{ number_format($stat->jml_skor, 0) }}</td>
                @endforeach
                <td colspan="3"></td><td></td><td></td>
            </tr>
            <tr>
                <td colspan="2" style="text-align:left;">Jumlah Skor Maks</td>
                @foreach ($analisisData['soal_stats'] as $stat)
                    <td>{{ number_format($stat->jml_skor_max, 0) }}</td>
                @endforeach
                <td colspan="3"></td><td></td><td></td>
            </tr>
            <tr class="persen-row">
                <td colspan="2" style="text-align:left;">% Skor yang dicapai</td>
                @foreach ($analisisData['soal_stats'] as $stat)
                    <td>{{ $stat->persen_skor }}%</td>
                @endforeach
                <td colspan="3"></td><td></td><td></td>
            </tr>
        </tfoot>
    </table>

    <div class="ttd-area">
        <div class="ttd-block">
            <div>Mengetahui,</div>
            <div>Kepala Sekolah</div>
            <div class="ttd-line">________________________</div>
            <div class="nip">NIP.</div>
        </div>
        <div class="ttd-block">
            <div>{{ $session->waktu_mulai?->format('d F Y') ?? now()->format('d F Y') }}</div>
            <div>Guru Mata Pelajaran</div>
            <div class="ttd-line">{{ $session->creator?->name ?? '________________________' }}</div>
            <div class="nip">NIP.</div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- HALAMAN 2 — DAFTAR NILAI ULANGAN HARIAN (PERBAIKAN)                       --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="halaman halaman-portrait">

    <div class="judul">Daftar Nilai Ulangan Harian (Perbaikan)</div>
    @if ($schoolName)
        <div class="sekolah">{{ strtoupper($schoolName) }}</div>
    @endif

    <table class="info">
        <tr>
            <td class="lbl">Mata Pelajaran</td><td class="sep">:</td><td>{{ $analisisData['session']->package?->nama ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Kelas/Semester</td><td class="sep">:</td><td>{{ $session->nama_sesi }}</td>
        </tr>
        <tr>
            <td class="lbl">Jumlah Peserta</td><td class="sep">:</td>
            <td>{{ $analisisData['peserta_data']->count() }}</td>
        </tr>
    </table>

    @php
        $maxAttempt = $analisisData['all_attempts_by_user']->map->count()->max() ?? 1;
        $allUsers   = $analisisData['peserta_data'];
    @endphp

    <table class="data">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Peserta</th>
                <th>Nilai Asli</th>
                @for ($i = 1; $i <= $maxAttempt - 1; $i++)
                    <th>Perbaikan ke-{{ $i }}</th>
                @endfor
                <th>Nilai Akhir</th>
                <th>Tanda Tangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($allUsers as $no => $peserta)
            @php
                $attempts = $analisisData['all_attempts_by_user']->get($peserta->user_id, collect());
                $nilai1   = $attempts->first()?->nilai_akhir;
            @endphp
            <tr>
                <td>{{ $no + 1 }}</td>
                <td class="nama">{{ $peserta->nama }}</td>
                <td>{{ $nilai1 !== null ? number_format((float)$nilai1, 0) : '—' }}</td>
                @for ($i = 2; $i <= $maxAttempt; $i++)
                    @php $atNilai = $attempts->get($i - 1)?->nilai_akhir; @endphp
                    <td>{{ $atNilai !== null ? number_format((float)$atNilai, 0) : '-' }}</td>
                @endfor
                @for ($i = $attempts->count(); $i < $maxAttempt - 1; $i++)
                    <td>-</td>
                @endfor
                <td><strong>{{ number_format($peserta->total, 0) }}</strong></td>
                <td style="width:80px;"></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="ttd-area">
        <div class="ttd-block">
            <div>Mengetahui,</div>
            <div>Kepala Sekolah</div>
            <div class="ttd-line">________________________</div>
            <div class="nip">NIP.</div>
        </div>
        <div class="ttd-block">
            <div>{{ $session->waktu_mulai?->format('d F Y') ?? now()->format('d F Y') }}</div>
            <div>Guru Mata Pelajaran</div>
            <div class="ttd-line">{{ $session->creator?->name ?? '________________________' }}</div>
            <div class="nip">NIP.</div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- HALAMAN 3 — HASIL ANALISIS ULANGAN HARIAN                                 --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="halaman halaman-portrait">

    <div class="judul">Hasil Analisis Ulangan Harian</div>
    @if ($schoolName)
        <div class="sekolah">{{ strtoupper($schoolName) }}</div>
    @endif

    <table class="info">
        <tr>
            <td class="lbl">Mata Pelajaran</td><td class="sep">:</td><td>{{ $analisisData['session']->package?->nama ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Kelas/Semester</td><td class="sep">:</td><td>{{ $session->nama_sesi }}</td>
        </tr>
    </table>

    <div class="has-section">
        <h3>1. Ketuntasan Belajar</h3>
        <div style="margin-left:16px;">
            <p style="margin-bottom:6px;"><strong>a. Perorangan</strong></p>
            <ul class="has-list">
                <li>Jumlah peserta ujian: <strong>{{ $hasilData['total_peserta'] }}</strong> siswa</li>
                <li>Jumlah peserta yang telah tuntas belajar: <strong>{{ $hasilData['total_tuntas'] }}</strong> siswa</li>
                <li>Jumlah peserta yang belum tuntas belajar: <strong>{{ $hasilData['total_tidak_tuntas'] }}</strong> siswa</li>
            </ul>
        </div>
        <div style="margin-left:16px;margin-top:8px;">
            <p style="margin-bottom:6px;"><strong>b. Klasikal</strong></p>
            <ul class="has-list">
                <li>Jumlah soal: <strong>{{ $hasilData['total_soal'] }}</strong> butir</li>
                <li>Jumlah soal yang telah tuntas klasikal: <strong>{{ $hasilData['soal_tuntas_count'] }}</strong> butir</li>
                <li>Jumlah soal yang belum tuntas klasikal: <strong>{{ $hasilData['soal_tidak_tuntas_count'] }}</strong> butir</li>
            </ul>
        </div>
    </div>

    <div class="has-section">
        <h3>2. Kesimpulan</h3>
        <div style="margin-left:16px;">
            <p><strong>a. Perlu perbaikan secara klasikal soal nomor:</strong></p>
            <p style="margin:4px 0 10px 0;">
                @if ($hasilData['soal_tidak_tuntas']->isEmpty())
                    <em>Semua soal tuntas klasikal.</em>
                @else
                    {{ $hasilData['soal_tidak_tuntas']->pluck('urutan')->join(', ') }}
                @endif
            </p>

            <p><strong>b. Perlu perbaikan secara perorangan, yaitu:</strong></p>
            @if ($hasilData['peserta_tidak_tuntas']->isEmpty())
                <p style="margin-top:4px;"><em>Semua peserta tuntas.</em></p>
            @else
                <table class="prog" style="margin-top:6px;">
                    <thead>
                        <tr>
                            <th style="width:5%;">No</th>
                            <th>Nama Peserta</th>
                            <th>Nilai</th>
                            <th>Soal yang Perlu Diperbaiki</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($hasilData['peserta_tidak_tuntas'] as $no => $p)
                        <tr>
                            <td class="center">{{ $no + 1 }}</td>
                            <td>{{ $p->nama }}</td>
                            <td class="center">{{ number_format($p->total, 0) }}</td>
                            <td class="center">{{ $p->soal_salah->join(', ') ?: '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <div class="ttd-area">
        <div class="ttd-block">
            <div>Mengetahui,</div>
            <div>Kepala Sekolah</div>
            <div class="ttd-line">________________________</div>
            <div class="nip">NIP.</div>
        </div>
        <div class="ttd-block">
            <div>{{ $session->waktu_mulai?->format('d F Y') ?? now()->format('d F Y') }}</div>
            <div>Guru Mata Pelajaran</div>
            <div class="ttd-line">{{ $session->creator?->name ?? '________________________' }}</div>
            <div class="nip">NIP.</div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- HALAMAN 4 — PROGRAM PERBAIKAN                                              --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="halaman halaman-portrait">

    <div class="judul">Program Perbaikan</div>
    @if ($schoolName)
        <div class="sekolah">{{ strtoupper($schoolName) }}</div>
    @endif

    <table class="info">
        <tr>
            <td class="lbl">Mata Pelajaran</td><td class="sep">:</td><td>{{ $analisisData['session']->package?->nama ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Kelas/Semester</td><td class="sep">:</td><td>{{ $session->nama_sesi }}</td>
        </tr>
        <tr>
            <td class="lbl">Jumlah Peserta</td><td class="sep">:</td>
            <td>{{ $hasilData['total_tidak_tuntas'] }}</td>
        </tr>
    </table>

    @if ($hasilData['peserta_tidak_tuntas']->isEmpty())
        <p><em>Tidak ada peserta yang memerlukan perbaikan.</em></p>
    @else
        <table class="prog">
            <thead>
                <tr>
                    <th style="width:5%;">No</th>
                    <th>Nama Peserta</th>
                    <th>Nilai</th>
                    <th>No. Soal yang Belum Tuntas</th>
                    <th>Kegiatan yang Dilaksanakan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($hasilData['peserta_tidak_tuntas'] as $no => $p)
                <tr>
                    <td class="center">{{ $no + 1 }}</td>
                    <td>{{ $p->nama }}</td>
                    <td class="center">{{ number_format($p->total, 0) }}</td>
                    <td class="center">{{ $p->soal_salah->join(', ') ?: '—' }}</td>
                    <td class="center">Ulangan Perbaikan</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="ttd-area">
        <div class="ttd-block">
            <div>Mengetahui,</div>
            <div>Kepala Sekolah</div>
            <div class="ttd-line">________________________</div>
            <div class="nip">NIP.</div>
        </div>
        <div class="ttd-block">
            <div>{{ $session->waktu_mulai?->format('d F Y') ?? now()->format('d F Y') }}</div>
            <div>Guru Mata Pelajaran</div>
            <div class="ttd-line">{{ $session->creator?->name ?? '________________________' }}</div>
            <div class="nip">NIP.</div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- HALAMAN 5 — PROGRAM PENGAYAAN                                              --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="halaman halaman-portrait">

    <div class="judul">Program Pengayaan</div>
    @if ($schoolName)
        <div class="sekolah">{{ strtoupper($schoolName) }}</div>
    @endif

    <table class="info">
        <tr>
            <td class="lbl">Mata Pelajaran</td><td class="sep">:</td><td>{{ $analisisData['session']->package?->nama ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Kelas/Semester</td><td class="sep">:</td><td>{{ $session->nama_sesi }}</td>
        </tr>
        <tr>
            <td class="lbl">Jumlah Peserta Tuntas</td><td class="sep">:</td>
            <td>{{ $hasilData['total_tuntas'] }}</td>
        </tr>
    </table>

    <table class="prog">
        <thead>
            <tr>
                <th style="width:5%;">No</th>
                <th>Rentang Nilai</th>
                <th>Nama Peserta</th>
                <th>Jumlah Siswa</th>
                <th>Kegiatan yang Dilaksanakan</th>
                <th>Ket</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($pengayaanData['kelompok'] as $no => $kelompok)
            <tr>
                <td class="center">{{ $no + 1 }}</td>
                <td class="center">{{ $kelompok->label }}</td>
                <td>
                    @foreach ($kelompok->peserta as $p)
                        {{ $p->nama }}<br>
                    @endforeach
                    @if ($kelompok->peserta->isEmpty())
                        <em>—</em>
                    @endif
                </td>
                <td class="center">{{ $kelompok->peserta->count() }}</td>
                <td>Menyelesaikan tugas yang diberikan guru berkaitan dengan materi yang telah dipelajari</td>
                <td></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="ttd-area">
        <div class="ttd-block">
            <div>Mengetahui,</div>
            <div>Kepala Sekolah</div>
            <div class="ttd-line">________________________</div>
            <div class="nip">NIP.</div>
        </div>
        <div class="ttd-block">
            <div>{{ $session->waktu_mulai?->format('d F Y') ?? now()->format('d F Y') }}</div>
            <div>Guru Mata Pelajaran</div>
            <div class="ttd-line">{{ $session->creator?->name ?? '________________________' }}</div>
            <div class="nip">NIP.</div>
        </div>
    </div>
</div>

<script>
    window.onload = function () { window.print(); };
</script>
</body>
</html>
