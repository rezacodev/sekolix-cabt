<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Peserta — {{ $session->nama_sesi }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 9pt; color: #000; background: #fff; }
        .page { width: 210mm; padding: 10mm; }
        .grid { display: flex; flex-wrap: wrap; gap: 6mm; }
        .card {
            width: 94mm;
            min-height: 64mm;
            border: 1.5px solid #000;
            padding: 5mm;
            page-break-inside: avoid;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .card-header {
            border-bottom: 1px solid #555;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }
        .school-name { font-size: 8pt; font-weight: bold; text-align: center; }
        .card-title { font-size: 9pt; font-weight: bold; text-align: center; margin-top: 2px; }
        .session-name { font-size: 7.5pt; text-align: center; color: #333; }
        .info-row { display: flex; gap: 4px; font-size: 8pt; }
        .info-label { width: 90px; font-weight: bold; flex-shrink: 0; }
        .info-value { flex: 1; border-bottom: 1px dotted #999; padding-bottom: 1px; }
        .ttd-area {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            font-size: 7.5pt;
        }
        .ttd-box {
            text-align: center;
            width: 38mm;
        }
        .ttd-line {
            margin-top: 14mm;
            border-top: 1px solid #000;
            padding-top: 3px;
        }
        .qr-area {
            display: flex;
            justify-content: flex-end;
            margin-top: 4px;
        }
        .qr-area img {
            width: 20mm;
            height: 20mm;
        }
        /* Page break after every 4 cards */
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
<div class="page">
    <div class="grid">
        @foreach ($participants as $i => $participant)
        <div class="card">
            <div class="card-header">
                <p class="school-name">{{ $schoolName ?: 'LEMBAGA PENDIDIKAN' }}</p>
                <p class="card-title">KARTU PESERTA UJIAN</p>
                <p class="session-name">{{ $session->nama_sesi }}</p>
            </div>

            <div class="info-row">
                <span class="info-label">Nama</span>
                <span class="info-value">{{ $participant->user?->name ?? '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">No. Peserta</span>
                <span class="info-value">{{ $participant->user?->nomor_peserta ?? '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Rombel / Kelas</span>
                <span class="info-value">{{ $participant->user?->rombel?->nama ?? '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Paket Soal</span>
                <span class="info-value">{{ $session->package?->nama ?? '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Waktu Ujian</span>
                <span class="info-value">{{ $session->waktu_mulai?->format('d M Y, H:i') ?? '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Durasi</span>
                <span class="info-value">{{ $session->durasi_menit ?? '—' }} menit</span>
            </div>

            <div class="ttd-area">
                <div class="ttd-box">
                    <div class="ttd-line">Peserta</div>
                </div>
                @if (!empty($qrCodes[$participant->user_id]))
                <div class="qr-area">
                    <img src="{{ $qrCodes[$participant->user_id] }}" alt="QR {{ $participant->user?->nomor_peserta }}">
                </div>
                @endif
                <div class="ttd-box">
                    <div class="ttd-line">Pengawas</div>
                </div>
            </div>
        </div>

        {{-- Page break after every 4 cards --}}
        @if (($i + 1) % 4 === 0 && !$loop->last)
        </div><div class="page-break"></div><div class="grid">
        @endif
        @endforeach
    </div>
</div>
</body>
</html>
