<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reminder Ujian</title>
<style>
  body { margin: 0; padding: 0; background: #f3f4f6; font-family: 'Segoe UI', Arial, sans-serif; color: #374151; }
  .wrap { max-width: 560px; margin: 32px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
  .header { background: linear-gradient(135deg,#f59e0b,#ef4444); padding: 32px 32px 24px; text-align: center; }
  .header h1 { margin: 0; color: #fff; font-size: 22px; font-weight: 700; }
  .header p  { margin: 4px 0 0; color: #fef3c7; font-size: 13px; }
  .badge { display: inline-block; background: #fef3c7; color: #92400e; font-weight: 700; font-size: 12px; padding: 3px 10px; border-radius: 99px; margin-bottom: 6px; }
  .body  { padding: 28px 32px; }
  .body p { margin: 0 0 12px; font-size: 14px; line-height: 1.6; }
  .info-box { background: #fffbeb; border-left: 4px solid #f59e0b; border-radius: 6px; padding: 14px 16px; margin: 18px 0; }
  .info-box p { margin: 4px 0; font-size: 13.5px; }
  .info-box strong { color: #b45309; }
  .btn { display: inline-block; margin: 18px 0 0; background: #f59e0b; color: #fff !important; text-decoration: none; padding: 12px 28px; border-radius: 8px; font-weight: 600; font-size: 14px; }
  .footer { background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 16px 32px; text-align: center; font-size: 12px; color: #9ca3af; }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <div class="badge">REMINDER H-1</div>
    <h1>{{ \App\Models\AppSetting::getString('app_name', config('app.name')) }}</h1>
    <p>{{ \App\Models\AppSetting::getString('school_name', '') }}</p>
  </div>
  <div class="body">
    <p>Halo, <strong>{{ $peserta->name }}</strong>!</p>
    <p>Mengingatkan bahwa Anda memiliki ujian <strong>besok</strong>:</p>
    <div class="info-box">
      <p><strong>Nama Ujian:</strong> {{ $session->nama_sesi }}</p>
      @if ($session->waktu_mulai)
      <p><strong>Waktu Mulai:</strong> {{ $session->waktu_mulai->translatedFormat('l, d F Y \p\u\k\u\l H:i') }}</p>
      @endif
      @if ($session->waktu_selesai)
      <p><strong>Waktu Selesai:</strong> {{ $session->waktu_selesai->translatedFormat('l, d F Y \p\u\k\u\l H:i') }}</p>
      @endif
      <p><strong>Durasi:</strong> {{ $session->package->durasi_menit ?? '—' }} menit</p>
    </div>
    <p>Pastikan perangkat dan koneksi internet Anda siap sebelum ujian dimulai.</p>
    <a class="btn" href="{{ url('/peserta') }}">Buka Dashboard</a>
  </div>
  <div class="footer">
    Pesan ini dikirim otomatis oleh {{ \App\Models\AppSetting::getString('app_name', config('app.name')) }}.
    Jangan balas email ini.
  </div>
</div>
</body>
</html>
