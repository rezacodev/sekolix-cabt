<?php

namespace Database\Seeders;

use App\Models\ExamSession;
use App\Models\SessionNote;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * SessionNoteSeeder
 *
 * Membuat catatan pengawas (session notes) untuk sesi ujian yang ada.
 * Mencakup berbagai kasus:
 *
 *   – Catatan normal saat ujian berlangsung
 *   – Catatan masalah teknis (koneksi, browser)
 *   – Catatan peserta bermasalah / ketahuan curang
 *   – Catatan akhir setelah sesi selesai
 *   – Multiple catatan satu sesi (dari pengawas berbeda)
 *
 * Idempotent: cek apakah catatan identik sudah ada sebelum insert.
 * SessionNote.$timestamps = false, sehingga created_at diisi manual.
 */
class SessionNoteSeeder extends Seeder
{
  public function run(): void
  {
    $guru  = User::where('email', 'guru1@cabt.local')->first();
    $guru2 = User::where('email', 'guru2@cabt.local')->first();
    $admin = User::where('level', '>=', 3)->first();

    $pengawas1 = $guru  ?? $admin;
    $pengawas2 = $guru2 ?? $admin;

    if (! $pengawas1) {
      $this->command->warn('SessionNoteSeeder: Tidak ada user guru/admin. Lewati.');
      return;
    }

    // ─── Cari sesi yang ada ───────────────────────────────────────────────
    $sesiMatematika = ExamSession::where('nama_sesi', 'like', '%Matematika%')
      ->whereIn('status', [ExamSession::STATUS_AKTIF, ExamSession::STATUS_SELESAI])
      ->first();

    $sesiIPA = ExamSession::where('nama_sesi', 'like', '%IPA%')
      ->whereIn('status', [ExamSession::STATUS_AKTIF, ExamSession::STATUS_SELESAI])
      ->first();

    $sesiBindo = ExamSession::where('nama_sesi', 'like', '%Indonesia%')
      ->whereIn('status', [ExamSession::STATUS_AKTIF, ExamSession::STATUS_SELESAI])
      ->first();

    $sesiSelesai = ExamSession::where('status', ExamSession::STATUS_SELESAI)->first();

    // ─── Data catatan yang akan dibuat ───────────────────────────────────
    $notes = [];

    // Catatan 1 — sesi Matematika (jika ada): catatan awal sesi normal
    if ($sesiMatematika) {
      $notes[] = [
        'exam_session_id' => $sesiMatematika->id,
        'user_id'         => $pengawas1->id,
        'catatan'         => 'Sesi ujian dimulai tepat waktu. Semua peserta hadir dan login tanpa masalah. Kondisi lab komputer baik.',
        'created_at'      => now()->subHours(2),
      ];

      $notes[] = [
        'exam_session_id' => $sesiMatematika->id,
        'user_id'         => $pengawas1->id,
        'catatan'         => 'Peserta PST-003 melaporkan browser-nya freeze pada soal nomor 8. Diminta me-refresh halaman — jawaban tetap tersimpan, peserta melanjutkan ujian.',
        'created_at'      => now()->subHours(1)->subMinutes(30),
      ];
    }

    // Catatan 2 — sesi IPA (jika ada): masalah koneksi + pengawas kedua
    if ($sesiIPA) {
      $notes[] = [
        'exam_session_id' => $sesiIPA->id,
        'user_id'         => $pengawas1->id,
        'catatan'         => 'Koneksi internet sempat terputus selama ± 3 menit (~10.15 WIB). Semua peserta menunggu, tidak ada yang logout. Sistem kembali normal dan ujian dilanjutkan.',
        'created_at'      => now()->subMinutes(45),
      ];

      $notes[] = [
        'exam_session_id' => $sesiIPA->id,
        'user_id'         => $pengawas2->id,
        'catatan'         => 'Peserta PST-007 terlihat membuka tab browser lain. Sudah diperingatkan dan diminta menutup tab tersebut. Tidak ada insiden lebih lanjut.',
        'created_at'      => now()->subMinutes(20),
      ];
    }

    // Catatan 3 — sesi Bahasa Indonesia (jika ada): catatan biasa
    if ($sesiBindo) {
      $notes[] = [
        'exam_session_id' => $sesiBindo->id,
        'user_id'         => $pengawas1->id,
        'catatan'         => 'Terdapat 1 peserta (PST-005) yang terlambat masuk 15 menit karena alasan kesehatan. Sudah dilaporkan ke guru BK. Peserta diizinkan mengikuti ujian dengan sisa waktu yang ada.',
        'created_at'      => now()->subHours(1),
      ];
    }

    // Catatan 4 — sesi yang sudah selesai: catatan penutup
    if ($sesiSelesai) {
      $notes[] = [
        'exam_session_id' => $sesiSelesai->id,
        'user_id'         => $pengawas1->id,
        'catatan'         => 'Sesi ujian selesai berjalan lancar. Semua peserta berhasil submit sebelum waktu habis. Nilai sudah dapat dilihat oleh guru melalui menu Laporan.',
        'created_at'      => now()->subDay()->addHours(2),
      ];

      // Catatan dari admin setelah sesi selesai
      if ($admin && $admin->id !== $pengawas1->id) {
        $notes[] = [
          'exam_session_id' => $sesiSelesai->id,
          'user_id'         => $admin->id,
          'catatan'         => 'Rekap nilai sudah diekspor ke Excel dan diserahkan ke wali kelas. Arsip jawaban tersimpan di sistem.',
          'created_at'      => now()->subDay()->addHours(3),
        ];
      }
    }

    // ─── Insert (skip duplikat) ───────────────────────────────────────────
    $created = 0;
    foreach ($notes as $data) {
      $exists = SessionNote::where('exam_session_id', $data['exam_session_id'])
        ->where('user_id', $data['user_id'])
        ->where('catatan', $data['catatan'])
        ->exists();

      if (! $exists) {
        SessionNote::create($data);
        $created++;
      }
    }

    $total = SessionNote::count();
    $this->command->info("SessionNoteSeeder: {$created} catatan baru dibuat. Total: {$total} catatan.");
  }
}
