<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ExamPackage;
use App\Models\ExamSection;
use App\Models\ExamSectionQuestion;
use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * MultiSectionMathSeeder
 *
 * Demo fitur Multi-Section Exam (F09):
 *   Paket  : "Ujian Harian Matematika — X IPA 1 (Multi-Bagian)"
 *   Bagian 1: Aljabar     — 10 mnt, 5 soal
 *   Bagian 2: Geometri    — 10 mnt, 5 soal
 *   Bagian 3: Statistika  — 10 mnt, 5 soal
 *
 * Sesi : "Ujian Harian Matematika Multi-Bagian — X IPA 1" (aktif, 3 jam)
 * Peserta yang didaftarkan: PST-001 s/d PST-005 + andi@cabt.local
 *
 * Idempotent — aman dijalankan berulang.
 * Requirement: QuestionSeeder sudah dijalankan terlebih dahulu.
 */
class MultiSectionMathSeeder extends Seeder
{
  private const PACKAGE_NAME = 'Ujian Harian Matematika — X IPA 1 (Multi-Bagian)';
  private const SESSION_NAME = 'Ujian Harian Matematika Multi-Bagian — X IPA 1';

  public function run(): void
  {
    $admin = User::where('level', '>=', User::LEVEL_ADMIN)->first();
    if (! $admin) {
      $this->command->error('Tidak ada user admin. Jalankan UserSeeder terlebih dahulu.');
      return;
    }

    // ── 1. Ambil sub-kategori Matematika ────────────────────────────────
    $catAljabar   = Category::where('nama', 'Aljabar')->first();
    $catGeometri  = Category::where('nama', 'Geometri')->first();
    $catStatistika = Category::where('nama', 'Statistika')->first();

    if (! $catAljabar || ! $catGeometri || ! $catStatistika) {
      $this->command->error('Kategori Aljabar/Geometri/Statistika tidak ditemukan. Jalankan QuestionSeeder terlebih dahulu.');
      return;
    }

    // ── 2. Buat / ambil paket multi-bagian ──────────────────────────────
    [$package, $isNew] = $this->firstOrCreatePackage($admin->id);

    if ($isNew) {
      $this->command->line("  <info>✓</info> Paket baru: [{$package->nama}]");
    } else {
      $this->command->line("  <comment>–</comment> Paket sudah ada: [{$package->nama}]");
    }

    // ── 3. Buat / sync seksi ujian ───────────────────────────────────────
    $this->syncSeksi($package, [
      [
        'nama'                => 'Aljabar',
        'urutan'              => 1,
        'durasi_menit'        => 10,
        'waktu_minimal_menit' => 0,
        'acak_soal'           => true,
        'acak_opsi'           => false,
        'kategori'            => $catAljabar,
        'limit_soal'          => 5,
      ],
      [
        'nama'                => 'Geometri',
        'urutan'              => 2,
        'durasi_menit'        => 10,
        'waktu_minimal_menit' => 0,
        'acak_soal'           => true,
        'acak_opsi'           => false,
        'kategori'            => $catGeometri,
        'limit_soal'          => 5,
      ],
      [
        'nama'                => 'Statistika',
        'urutan'              => 3,
        'durasi_menit'        => 10,
        'waktu_minimal_menit' => 0,
        'acak_soal'           => true,
        'acak_opsi'           => false,
        'kategori'            => $catStatistika,
        'limit_soal'          => 5,
      ],
    ]);

    // ── 4. Buat / ambil sesi ujian ───────────────────────────────────────
    $session = $this->firstOrCreateSession($package, $admin->id);
    $this->command->line("  <info>✓</info> Sesi: [{$session->nama_sesi}] status={$session->status}");

    // ── 5. Daftarkan peserta ─────────────────────────────────────────────
    $pesertaList = collect();

    // PST-001 .. PST-005
    $pesertaRombel = User::where('level', User::LEVEL_PESERTA)
      ->whereIn('nomor_peserta', ['PST-001', 'PST-002', 'PST-003', 'PST-004', 'PST-005'])
      ->get();
    $pesertaList = $pesertaList->merge($pesertaRombel);

    // andi@cabt.local
    $andi = User::where('email', 'andi@cabt.local')->first();
    if ($andi) {
      $pesertaList = $pesertaList->push($andi);
    }

    $didaftar = 0;
    foreach ($pesertaList->unique('id') as $user) {
      $exists = ExamSessionParticipant::where('exam_session_id', $session->id)
        ->where('user_id', $user->id)
        ->exists();
      if (! $exists) {
        ExamSessionParticipant::create([
          'exam_session_id' => $session->id,
          'user_id'         => $user->id,
          'status'          => ExamSessionParticipant::STATUS_BELUM,
        ]);
        $didaftar++;
      }
    }

    $totalPeserta = ExamSessionParticipant::where('exam_session_id', $session->id)->count();
    $this->command->info(
      "MultiSectionMathSeeder selesai. Paket ID={$package->id}, Sesi ID={$session->id}. " .
        "{$didaftar} peserta baru didaftarkan ({$totalPeserta} total)."
    );

    // Ringkasan seksi
    foreach ($package->sections as $s) {
      $jml = ExamSectionQuestion::where('section_id', $s->id)->count();
      $this->command->line("    Bagian {$s->urutan}: {$s->nama} — {$jml} soal, {$s->durasi_menit} mnt");
    }
  }

    // ─────────────────────────────────────────────────────────────────────────

  /** @return array{ExamPackage, bool} [package, isNew] */
  private function firstOrCreatePackage(int $adminId): array
  {
    $existing = ExamPackage::where('nama', self::PACKAGE_NAME)->first();
    if ($existing) {
      return [$existing, false];
    }

    $package = ExamPackage::create([
      'nama'                => self::PACKAGE_NAME,
      'deskripsi'           => 'Demo fitur Multi-Bagian: ujian terbagi 3 bagian — Aljabar, Geometri, Statistika — masing-masing 10 menit.',
      'durasi_menit'        => 35,  // total cadangan (timer seksi yang berlaku)
      'waktu_minimal_menit' => 0,
      'acak_soal'           => false,  // acak dihandle per seksi
      'acak_opsi'           => false,
      'max_pengulangan'     => 0,
      'tampilkan_hasil'     => true,
      'tampilkan_review'    => true,
      'grading_mode'        => ExamPackage::GRADING_REALTIME,
      'has_sections'        => true,
      'navigasi_seksi'      => ExamPackage::NAV_SEKSI_URUT_KEMBALI,
      'created_by'          => $adminId,
    ]);

    return [$package, true];
  }

  private function syncSeksi(ExamPackage $package, array $seksiDefs): void
  {
    foreach ($seksiDefs as $def) {
      /** @var Category $kategori */
      $kategori  = $def['kategori'];
      $limitSoal = $def['limit_soal'];

      // Buat atau ambil seksi
      $section = ExamSection::firstOrCreate(
        ['exam_package_id' => $package->id, 'urutan' => $def['urutan']],
        [
          'nama'                => $def['nama'],
          'durasi_menit'        => $def['durasi_menit'],
          'waktu_minimal_menit' => $def['waktu_minimal_menit'],
          'acak_soal'           => $def['acak_soal'],
          'acak_opsi'           => $def['acak_opsi'],
        ]
      );

      // Simpan soal ke seksi jika belum ada
      $sudahAda = ExamSectionQuestion::where('section_id', $section->id)->count();
      if ($sudahAda > 0) {
        $this->command->line("    Bagian {$def['urutan']} ({$def['nama']}): sudah ada {$sudahAda} soal, dilewati");
        continue;
      }

      $soalIds = Question::where('kategori_id', $kategori->id)
        ->where('aktif', true)
        ->inRandomOrder()
        ->limit($limitSoal)
        ->pluck('id');

      if ($soalIds->isEmpty()) {
        $this->command->warn("    Bagian {$def['urutan']} ({$def['nama']}): tidak ada soal di kategori '{$kategori->nama}'");
        continue;
      }

      foreach ($soalIds->values() as $urutan => $qId) {
        ExamSectionQuestion::create([
          'section_id'  => $section->id,
          'question_id' => $qId,
          'urutan'      => $urutan + 1,
        ]);
      }

      $this->command->line("    <info>✓</info> Bagian {$def['urutan']} ({$def['nama']}): {$soalIds->count()} soal ditambahkan");
    }
  }

  private function firstOrCreateSession(ExamPackage $package, int $adminId): ExamSession
  {
    $existing = ExamSession::where('nama_sesi', self::SESSION_NAME)->first();
    if ($existing) {
      return $existing;
    }

    return ExamSession::create([
      'nama_sesi'       => self::SESSION_NAME,
      'exam_package_id' => $package->id,
      'status'          => ExamSession::STATUS_AKTIF,
      'waktu_mulai'     => now()->subMinutes(30),
      'waktu_selesai'   => now()->addHours(2),
      'token_akses'     => null,
      'created_by'      => $adminId,
    ]);
  }
}
