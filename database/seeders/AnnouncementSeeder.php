<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Rombel;
use Illuminate\Database\Seeder;

/**
 * AnnouncementSeeder
 *
 * Membuat 6 pengumuman yang mencakup semua kemungkinan kombinasi:
 *  1. Info aktif, target semua, tanpa tanggal                → selalu tampil
 *  2. Penting aktif, target semua, dengan rentang tanggal    → jadwal ujian PTS
 *  3. Warning aktif, target semua, dengan rentang tanggal    → notifikasi maintenance
 *  4. Info aktif, target per_rombel (X IPA 1)               → hanya tampil ke X IPA 1
 *  5. Info aktif, tanggal_selesai kemarin                    → expires, tidak tampil scopeAktif
 *  6. Penting nonaktif (draft)                              → aktif=false, tidak tampil
 *
 * Idempotent — updateOrCreate berdasarkan judul.
 */
class AnnouncementSeeder extends Seeder
{
  public function run(): void
  {
    $ripa1 = Rombel::where('kode', 'X-IPA-1')->first();

    $announcements = [

      // 1. Info — Semua — Tanpa tanggal (selalu aktif)
      [
        'judul'           => 'Selamat Datang di Sistem CBT Sekolix!',
        'isi'             => '<p>Sistem <strong>Computer-Based Test (CBT) Sekolix</strong> siap digunakan untuk semua kegiatan ujian berbasis komputer. '
          . 'Pastikan perangkat dan koneksi internet Anda stabil sebelum memulai ujian.</p>'
          . '<p>Bacalah petunjuk pengerjaan dengan seksama dan hubungi pengawas jika mengalami kendala teknis.</p>',
        'tipe'            => Announcement::TIPE_INFO,
        'target'          => Announcement::TARGET_SEMUA,
        'rombel_id'       => null,
        'tanggal_mulai'   => null,
        'tanggal_selesai' => null,
        'aktif'           => true,
      ],

      // 2. Penting — Semua — Jadwal PTS (aktif sekarang, selesai 14 hari ke depan)
      [
        'judul'           => 'Jadwal Penilaian Tengah Semester (PTS) Ganjil 2025/2026',
        'isi'             => '<p>Berikut jadwal <strong>Penilaian Tengah Semester (PTS) Ganjil 2025/2026</strong>:</p>'
          . '<ul>'
          . '<li><strong>Senin, 7 April 2026</strong>: Matematika (07.30–09.30 WIB)</li>'
          . '<li><strong>Selasa, 8 April 2026</strong>: Bahasa Indonesia (07.30–09.30 WIB)</li>'
          . '<li><strong>Rabu, 9 April 2026</strong>: IPA (07.30–09.30 WIB)</li>'
          . '<li><strong>Kamis, 10 April 2026</strong>: Sejarah &amp; TIK (07.30–11.00 WIB)</li>'
          . '</ul>'
          . '<p>Hadir <strong>15 menit sebelum</strong> ujian dimulai dan bawa <strong>kartu peserta</strong>. Login menggunakan username dan password yang telah dibagikan.</p>',
        'tipe'            => Announcement::TIPE_PENTING,
        'target'          => Announcement::TARGET_SEMUA,
        'rombel_id'       => null,
        'tanggal_mulai'   => now()->subDay(),
        'tanggal_selesai' => now()->addDays(14),
        'aktif'           => true,
      ],

      // 3. Warning — Semua — Maintenance (aktif 2 hari ke depan)
      [
        'judul'           => 'Pemeliharaan Sistem: Sabtu 5 April 2026 Pukul 00.00–03.00 WIB',
        'isi'             => '<p>Sistem CAT Sekolix akan menjalani <strong>pemeliharaan rutin</strong> pada:</p>'
          . '<p>📅 <strong>Sabtu, 5 April 2026 pukul 00.00 – 03.00 WIB</strong></p>'
          . '<p>Selama periode ini, sistem <strong>tidak dapat diakses</strong>. Pastikan Anda telah menyelesaikan atau menyimpan progres ujian Anda sebelum pukul 23.45 WIB. '
          . 'Setelah pemeliharaan, sistem akan berjalan normal kembali. Mohon maaf atas ketidaknyamanannya.</p>',
        'tipe'            => Announcement::TIPE_WARNING,
        'target'          => Announcement::TARGET_SEMUA,
        'rombel_id'       => null,
        'tanggal_mulai'   => now()->subDays(2),
        'tanggal_selesai' => now()->addDays(2),
        'aktif'           => true,
      ],

      // 4. Info — Per Rombel (X IPA 1) — Perubahan jadwal khusus
      [
        'judul'           => 'Khusus X IPA 1: Perubahan Jadwal Ujian Matematika',
        'isi'             => '<p>Kepada siswa/i <strong>Kelas X IPA 1</strong>,</p>'
          . '<p>Jadwal Ujian Matematika diubah menjadi <strong>Senin, 7 April 2026 pukul 10.00–12.00 WIB</strong> '
          . '(semula pukul 07.30 WIB). Perubahan ini disebabkan lab komputer digunakan untuk kegiatan lain di pagi hari.</p>'
          . '<p>Harap catat perubahan ini dan sampaikan kepada seluruh teman sekelas Anda. Terima kasih atas pengertiannya.</p>',
        'tipe'            => Announcement::TIPE_INFO,
        'target'          => Announcement::TARGET_PER_ROMBEL,
        'rombel_id'       => $ripa1?->id,
        'tanggal_mulai'   => now()->subDay(),
        'tanggal_selesai' => now()->addDays(5),
        'aktif'           => true,
      ],

      // 5. Info — Semua — Sudah expired (tanggal_selesai = kemarin)
      [
        'judul'           => 'Pengumuman Libur Nasional 1 April 2026 (Telah Berakhir)',
        'isi'             => '<p>Diberitahukan bahwa pada <strong>tanggal 1 April 2026</strong> tidak ada kegiatan ujian CBT '
          . 'karena bertepatan dengan hari libur nasional. Ujian yang semula dijadwalkan pada tanggal tersebut '
          . 'telah dijadwalkan ulang oleh masing-masing guru pengampu.</p>',
        'tipe'            => Announcement::TIPE_INFO,
        'target'          => Announcement::TARGET_SEMUA,
        'rombel_id'       => null,
        'tanggal_mulai'   => now()->subDays(5),
        'tanggal_selesai' => now()->subDay(),
        'aktif'           => true,
      ],

      // 6. Penting — Semua — Nonaktif (draft)
      [
        'judul'           => '[DRAFT] Pengumuman Jadwal Ujian Akhir Semester Genap 2025/2026',
        'isi'             => '<p>Pengumuman ini belum dipublikasikan. Jadwal Ujian Akhir Semester (UAS) Genap 2025/2026 '
          . 'akan diumumkan setelah mendapat persetujuan dari Kepala Sekolah. '
          . 'Estimasi pelaksanaan: <strong>Juni 2026</strong>.</p>',
        'tipe'            => Announcement::TIPE_PENTING,
        'target'          => Announcement::TARGET_SEMUA,
        'rombel_id'       => null,
        'tanggal_mulai'   => now()->addDays(30),
        'tanggal_selesai' => now()->addDays(60),
        'aktif'           => false,
      ],
    ];

    foreach ($announcements as $data) {
      Announcement::updateOrCreate(
        ['judul' => $data['judul']],
        $data
      );
    }

    $total = count($announcements);
    $this->command->info("AnnouncementSeeder: {$total} pengumuman dibuat/diperbarui.");
    $this->command->line('  Aktif+dalam rentang: ' . Announcement::aktif()->count());
    $this->command->line('  Nonaktif/expired: ' . (Announcement::count() - Announcement::aktif()->count()));
  }
}
