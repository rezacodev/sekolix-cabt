<?php

namespace Database\Seeders;

use App\Models\AttemptLog;
use App\Models\AttemptQuestion;
use App\Models\ExamAttempt;
use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Models\Question;
use App\Models\User;
use App\Services\ScoringService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * AttemptSeeder — Skenario Komprehensif untuk Phase 6
 *
 * Melengkapi data yang sudah dibuat oleh ExamSessionSeeder dengan skenario
 * khusus yang diperlukan untuk menguji fitur:
 *   - ScoringService (kalkulasi nilai otomatis semua tipe soal)
 *   - GradingResource (antrian URAIAN pending penilaian manual)
 *   - GradingDetail Page (input nilai & regrade)
 *
 * SKENARIO YANG DIBUAT:
 *
 *  A. URAIAN Pending Manual Grading                  [PST-006..007 di S9/S10]
 *     → tampil di GradingResource sebagai antrian penilaian
 *
 *  B. URAIAN Sudah Dinilai (Grading Selesai)         [PST-008 di S9]
 *     → demonstrasi after-state setelah admin menilai
 *
 *  C. File Upload URAIAN + Pending Grading           [PST-009 di S9]
 *     → jawaban_file terisi, nilai_perolehan null
 *
 *  D. Zero Score (semua kosong)                       [PST-010 di S9]
 *     → jumlah_kosong = total soal, nilai_akhir = 0
 *
 *  E. Perfect Score (semua benar)                     [PST-006 di S10]
 *     → nilai_akhir = 100 (atau pending URAIAN)
 *
 *  F. Tab Switch → Diskualifikasi                     [PST-007 di S1]
 *     → 5 log tab_switch + status diskualifikasi
 *
 *  G. Timeout                                         [PST-008 di S7]
 *     → status timeout, nilai_akhir = null (manual)
 *
 *  H. Remidi Bertahap (3 attempt, nilai meningkat)    [PST-003 di S7]
 *     → attempt_ke 1,2,3 dengan nilai 40→65→80
 *
 *  I. Ragu-Ragu flags                                 [PST-004 di S7]
 *     → beberapa soal ditandai is_ragu = true
 *
 *  J. Diskualifikasi Manual (kick by admin)           [PST-005 di S1]
 *     → AttemptLog EVENT_KICK, status diskualifikasi
 *
 * Idempotent: setiap attempt dicek lewat (session, user, attempt_ke).
 */
class AttemptSeeder extends Seeder
{
    private ScoringService $scoring;

    // Kelompok peserta
    private User $admin;
    private array $peserta = []; // keyed by nomor_peserta: PST-001..010

    // Sesi yang dipakai
    private ExamSession $s1;   // Matematika aktif
    private ExamSession $s7;   // Matematika unlimited aktif
    private ExamSession $s9;   // Sejarah selesai (manual grading)
    private ExamSession $s10;  // Bindo selesai (manual grading)

    public function run(): void
    {
        $this->scoring = app(ScoringService::class);

        // ── Bootstrap: pastikan dependensi ada ───────────────────────────────
        $this->admin = User::where('level', '>=', User::LEVEL_ADMIN)->first()
            ?? throw new \RuntimeException('Tidak ada admin. Jalankan UserSeeder + ExamSessionSeeder terlebih dahulu.');

        $this->peserta = User::where('level', User::LEVEL_PESERTA)
            ->whereIn('nomor_peserta', [
                'PST-001', 'PST-002', 'PST-003', 'PST-004', 'PST-005',
                'PST-006', 'PST-007', 'PST-008', 'PST-009', 'PST-010',
            ])
            ->get()
            ->keyBy('nomor_peserta')
            ->all();

        if (count($this->peserta) < 10) {
            $this->command->warn('Kurang dari 10 peserta ditemukan. Jalankan UserSeeder terlebih dahulu.');
            return;
        }

        foreach (['s1' => 'Ujian Harian Matematika',
                  's7' => 'Ujian Matematika — X IPA 2',
                  's9' => 'Ujian Sejarah — X IPS 1 (Sudah Selesai)',
                  's10' => 'Ujian B.Indonesia — X IPS 1 (Selesai, Nilai Tersembunyi)',
                 ] as $prop => $needle) {
            $sesi = ExamSession::where('nama_sesi', 'like', "%{$needle}%")->first();
            if (! $sesi) {
                $this->command->warn("Sesi '{$needle}' tidak ditemukan. Jalankan ExamSessionSeeder terlebih dahulu.");
                return;
            }
            $this->$prop = $sesi;
        }

        // ── Daftarkan PST-006..010 ke sesi yang belum terdaftar ─────────────
        $this->command->info('Mendaftarkan peserta tambahan ke sesi...');
        foreach (['PST-006', 'PST-007', 'PST-008', 'PST-009', 'PST-010'] as $nomor) {
            foreach ([$this->s1, $this->s7, $this->s9, $this->s10] as $sesi) {
                ExamSessionParticipant::firstOrCreate(
                    ['exam_session_id' => $sesi->id, 'user_id' => $this->peserta[$nomor]->id],
                    ['status' => ExamSessionParticipant::STATUS_BELUM]
                );
            }
        }
        foreach (['PST-003', 'PST-004', 'PST-005'] as $nomor) {
            foreach ([$this->s7] as $sesi) {
                ExamSessionParticipant::firstOrCreate(
                    ['exam_session_id' => $sesi->id, 'user_id' => $this->peserta[$nomor]->id],
                    ['status' => ExamSessionParticipant::STATUS_BELUM]
                );
            }
        }

        // ── Skenario A: URAIAN Pending Manual Grading ────────────────────────
        $this->command->info('Skenario A: URAIAN pending manual grading...');
        $this->buatAttemptUraianPending($this->s9, $this->peserta['PST-006'], 1,
            now()->subDay()->subHours(1)->subMinutes(50), now()->subDay()->subMinutes(55));
        $this->buatAttemptUraianPending($this->s10, $this->peserta['PST-007'], 1,
            now()->subDays(3)->subMinutes(55), now()->subDays(3)->subMinutes(10));

        // ── Skenario B: URAIAN Sudah Dinilai ─────────────────────────────────
        $this->command->info('Skenario B: URAIAN sudah dinilai...');
        $this->buatAttemptUraianGraded($this->s9, $this->peserta['PST-008'], 1,
            now()->subDay()->subHours(1)->subMinutes(45), now()->subDay()->subMinutes(50));

        // ── Skenario C: File Upload URAIAN ───────────────────────────────────
        $this->command->info('Skenario C: File upload URAIAN...');
        $this->buatAttemptDenganFileUpload($this->s9, $this->peserta['PST-009'], 1,
            now()->subDay()->subHours(2), now()->subDay()->subHour()->subMinutes(10));

        // ── Skenario D: Zero Score (semua kosong) ────────────────────────────
        $this->command->info('Skenario D: Zero score (semua kosong)...');
        $this->buatAttemptZeroScore($this->s9, $this->peserta['PST-010'], 1,
            now()->subDay()->subHours(3), now()->subDay()->subHour()->subMinutes(5));

        // ── Skenario E: Perfect Score ─────────────────────────────────────────
        $this->command->info('Skenario E: Perfect score...');
        $this->buatAttemptPerfectScore($this->s10, $this->peserta['PST-006'], 1,
            now()->subDays(3)->subHours(2), now()->subDays(3)->subMinutes(25));

        // ── Skenario F: Tab Switch → Diskualifikasi ───────────────────────────
        $this->command->info('Skenario F: Tab switch → diskualifikasi...');
        $this->buatAttemptDiskualifikasiTabSwitch($this->s1, $this->peserta['PST-007'], 1,
            now()->subHours(1)->subMinutes(30));

        // ── Skenario G: Timeout ───────────────────────────────────────────────
        $this->command->info('Skenario G: Timeout...');
        $this->buatAttemptTimeout($this->s7, $this->peserta['PST-008'], 1,
            now()->subMinutes(50));

        // ── Skenario H: Remidi Bertahap ───────────────────────────────────────
        $this->command->info('Skenario H: Remidi bertahap (3 attempt)...');
        $this->buatAttemptRemidi($this->s7, $this->peserta['PST-003']);

        // ── Skenario I: Ragu-Ragu Flags ───────────────────────────────────────
        $this->command->info('Skenario I: Ragu-ragu flags...');
        $this->buatAttemptDenganRagu($this->s7, $this->peserta['PST-004'], 1,
            now()->subMinutes(40), now()->subMinutes(5));

        // ── Skenario J: Kick (Diskualifikasi Manual Admin) ────────────────────
        $this->command->info('Skenario J: Kick (diskualifikasi manual)...');
        $this->buatAttemptKick($this->s1, $this->peserta['PST-005'], 1,
            now()->subHours(1)->subMinutes(20));

        $this->printRingkasan();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SKENARIO A: URAIAN Pending Manual Grading
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Attempt selesai, tapi URAIAN belum dinilai → tampil di GradingResource.
     */
    private function buatAttemptUraianPending(
        ExamSession $session,
        User $user,
        int $attemptKe,
        \Carbon\Carbon $mulai,
        \Carbon\Carbon $selesai,
    ): void {
        if ($this->attemptExists($session, $user, $attemptKe)) {
            return;
        }

        DB::transaction(function () use ($session, $user, $attemptKe, $mulai, $selesai) {
            $questions = $this->getSessionQuestions($session);
            $total     = $questions->count();

            // Hitung nilai non-URAIAN dulu
            $sumNilai  = 0.0;
            $sumBobot  = 0.0;
            $benar = $salah = $kosong = 0;

            foreach ($questions as $q) {
                $sumBobot += (float) $q->bobot;
                if ($q->tipe !== Question::TIPE_URAIAN) {
                    $shouldBeCorrect = rand(0, 100) < 70; // 70% benar
                    if ($shouldBeCorrect) {
                        $sumNilai += (float) $q->bobot;
                        $benar++;
                    } else {
                        $salah++;
                    }
                }
            }

            $attempt = ExamAttempt::create([
                'exam_session_id' => $session->id,
                'user_id'         => $user->id,
                'waktu_mulai'     => $mulai,
                'waktu_selesai'   => $selesai,
                'status'          => ExamAttempt::STATUS_SELESAI,
                'nilai_akhir'     => null, // belum final karena URAIAN pending
                'jumlah_benar'    => $benar,
                'jumlah_salah'    => $salah,
                'jumlah_kosong'   => $kosong,
                'attempt_ke'      => $attemptKe,
            ]);

            foreach ($questions->values() as $urutan => $question) {
                $isUraian         = $question->tipe === Question::TIPE_URAIAN;
                $shouldBeCorrect  = ! $isUraian && rand(0, 100) < 70;
                $jawaban          = $isUraian
                    ? 'Jawaban uraian peserta yang perlu dinilai oleh penguji secara manual.'
                    : $this->generateJawaban($question, $shouldBeCorrect);

                AttemptQuestion::create([
                    'attempt_id'      => $attempt->id,
                    'question_id'     => $question->id,
                    'urutan'          => $urutan + 1,
                    'jawaban_peserta' => $jawaban['jawaban'],
                    'nilai_perolehan' => $isUraian ? null : $jawaban['nilai'],
                    'is_correct'      => $isUraian ? null : $jawaban['correct'],
                    'is_ragu'         => false,
                    'waktu_jawab'     => $mulai->copy()->addMinutes($urutan + 1),
                ]);
            }

            $this->logEvent($attempt->id, AttemptLog::EVENT_SUBMIT, "status=selesai", $selesai);
            $this->updateParticipant($session->id, $user->id, ExamSessionParticipant::STATUS_SELESAI);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SKENARIO B: URAIAN Sudah Dinilai
    // ─────────────────────────────────────────────────────────────────────────

    private function buatAttemptUraianGraded(
        ExamSession $session,
        User $user,
        int $attemptKe,
        \Carbon\Carbon $mulai,
        \Carbon\Carbon $selesai,
    ): void {
        if ($this->attemptExists($session, $user, $attemptKe)) {
            return;
        }

        DB::transaction(function () use ($session, $user, $attemptKe, $mulai, $selesai) {
            $questions = $this->getSessionQuestions($session);

            $attempt = ExamAttempt::create([
                'exam_session_id' => $session->id,
                'user_id'         => $user->id,
                'waktu_mulai'     => $mulai,
                'waktu_selesai'   => $selesai,
                'status'          => ExamAttempt::STATUS_SELESAI,
                'nilai_akhir'     => null, // akan dihitung oleh ScoringService
                'jumlah_benar'    => 0,
                'jumlah_salah'    => 0,
                'jumlah_kosong'   => 0,
                'attempt_ke'      => $attemptKe,
            ]);

            foreach ($questions->values() as $urutan => $question) {
                $isUraian        = $question->tipe === Question::TIPE_URAIAN;
                $shouldBeCorrect = ! $isUraian && rand(0, 100) < 75;
                $jawaban         = $isUraian
                    ? 'Jawaban uraian yang sudah dinilai: menjelaskan konsep dengan baik dan komprehensif.'
                    : $this->generateJawaban($question, $shouldBeCorrect);

                // Untuk URAIAN, berikan nilai manual (75% dari bobot)
                $nilaiPerolehan = $isUraian
                    ? round((float) $question->bobot * 0.75, 2)
                    : $jawaban['nilai'];

                AttemptQuestion::create([
                    'attempt_id'      => $attempt->id,
                    'question_id'     => $question->id,
                    'urutan'          => $urutan + 1,
                    'jawaban_peserta' => $jawaban['jawaban'] ?? 'Jawaban uraian telah dinilai.',
                    'nilai_perolehan' => $nilaiPerolehan,
                    'is_correct'      => $isUraian ? null : $jawaban['correct'],
                    'is_ragu'         => false,
                    'waktu_jawab'     => $mulai->copy()->addMinutes($urutan + 1),
                ]);
            }

            $this->logEvent($attempt->id, AttemptLog::EVENT_SUBMIT, "status=selesai", $selesai);
            $this->updateParticipant($session->id, $user->id, ExamSessionParticipant::STATUS_SELESAI);

            // Panggil ScoringService untuk hitung nilai_akhir final
            $this->scoring->regrade($attempt);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SKENARIO C: File Upload URAIAN
    // ─────────────────────────────────────────────────────────────────────────

    private function buatAttemptDenganFileUpload(
        ExamSession $session,
        User $user,
        int $attemptKe,
        \Carbon\Carbon $mulai,
        \Carbon\Carbon $selesai,
    ): void {
        if ($this->attemptExists($session, $user, $attemptKe)) {
            return;
        }

        DB::transaction(function () use ($session, $user, $attemptKe, $mulai, $selesai) {
            $questions = $this->getSessionQuestions($session);

            $attempt = ExamAttempt::create([
                'exam_session_id' => $session->id,
                'user_id'         => $user->id,
                'waktu_mulai'     => $mulai,
                'waktu_selesai'   => $selesai,
                'status'          => ExamAttempt::STATUS_SELESAI,
                'nilai_akhir'     => null,
                'jumlah_benar'    => 0,
                'jumlah_salah'    => 0,
                'jumlah_kosong'   => 0,
                'attempt_ke'      => $attemptKe,
            ]);

            $uraianCounter = 1;
            foreach ($questions->values() as $urutan => $question) {
                $isUraian        = $question->tipe === Question::TIPE_URAIAN;
                $shouldBeCorrect = ! $isUraian && rand(0, 100) < 65;
                $jawaban         = $this->generateJawaban($question, $shouldBeCorrect);

                // Simulasi path file upload untuk URAIAN
                $jawabanFile = $isUraian
                    ? "uraian-uploads/{$session->id}/{$user->id}/soal-{$uraianCounter}.jpg"
                    : null;
                if ($isUraian) {
                    $uraianCounter++;
                }

                AttemptQuestion::create([
                    'attempt_id'      => $attempt->id,
                    'question_id'     => $question->id,
                    'urutan'          => $urutan + 1,
                    'jawaban_peserta' => $isUraian ? null : $jawaban['jawaban'],
                    'jawaban_file'    => $jawabanFile,
                    'nilai_perolehan' => $isUraian ? null : $jawaban['nilai'],
                    'is_correct'      => $isUraian ? null : $jawaban['correct'],
                    'is_ragu'         => false,
                    'waktu_jawab'     => $mulai->copy()->addMinutes($urutan + 1),
                ]);
            }

            $this->logEvent($attempt->id, AttemptLog::EVENT_SUBMIT, "status=selesai", $selesai);
            $this->updateParticipant($session->id, $user->id, ExamSessionParticipant::STATUS_SELESAI);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SKENARIO D: Zero Score (semua kosong / tidak menjawab)
    // ─────────────────────────────────────────────────────────────────────────

    private function buatAttemptZeroScore(
        ExamSession $session,
        User $user,
        int $attemptKe,
        \Carbon\Carbon $mulai,
        \Carbon\Carbon $selesai,
    ): void {
        if ($this->attemptExists($session, $user, $attemptKe)) {
            return;
        }

        DB::transaction(function () use ($session, $user, $attemptKe, $mulai, $selesai) {
            $questions = $this->getSessionQuestions($session);
            $total     = $questions->count();

            $attempt = ExamAttempt::create([
                'exam_session_id' => $session->id,
                'user_id'         => $user->id,
                'waktu_mulai'     => $mulai,
                'waktu_selesai'   => $selesai,
                'status'          => ExamAttempt::STATUS_SELESAI,
                'nilai_akhir'     => 0,
                'jumlah_benar'    => 0,
                'jumlah_salah'    => 0,
                'jumlah_kosong'   => $total,
                'attempt_ke'      => $attemptKe,
            ]);

            foreach ($questions->values() as $urutan => $question) {
                AttemptQuestion::create([
                    'attempt_id'      => $attempt->id,
                    'question_id'     => $question->id,
                    'urutan'          => $urutan + 1,
                    'jawaban_peserta' => null,
                    'nilai_perolehan' => 0.0,
                    'is_correct'      => false,
                    'is_ragu'         => false,
                    'waktu_jawab'     => null,
                ]);
            }

            $this->logEvent($attempt->id, AttemptLog::EVENT_SUBMIT, "status=selesai", $selesai);
            $this->updateParticipant($session->id, $user->id, ExamSessionParticipant::STATUS_SELESAI);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SKENARIO E: Perfect Score (semua benar, nilai_akhir = 100 atau pending)
    // ─────────────────────────────────────────────────────────────────────────

    private function buatAttemptPerfectScore(
        ExamSession $session,
        User $user,
        int $attemptKe,
        \Carbon\Carbon $mulai,
        \Carbon\Carbon $selesai,
    ): void {
        if ($this->attemptExists($session, $user, $attemptKe)) {
            return;
        }

        DB::transaction(function () use ($session, $user, $attemptKe, $mulai, $selesai) {
            $questions = $this->getSessionQuestions($session);

            $attempt = ExamAttempt::create([
                'exam_session_id' => $session->id,
                'user_id'         => $user->id,
                'waktu_mulai'     => $mulai,
                'waktu_selesai'   => $selesai,
                'status'          => ExamAttempt::STATUS_SELESAI,
                'nilai_akhir'     => null,
                'jumlah_benar'    => 0,
                'jumlah_salah'    => 0,
                'jumlah_kosong'   => 0,
                'attempt_ke'      => $attemptKe,
            ]);

            foreach ($questions->values() as $urutan => $question) {
                $isUraian = $question->tipe === Question::TIPE_URAIAN;
                $jawaban  = $this->generateJawaban($question, true); // selalu benar

                // URAIAN full bobot (perfect)
                $nilaiPerolehan = $isUraian
                    ? (float) $question->bobot
                    : $jawaban['nilai'];

                AttemptQuestion::create([
                    'attempt_id'      => $attempt->id,
                    'question_id'     => $question->id,
                    'urutan'          => $urutan + 1,
                    'jawaban_peserta' => $jawaban['jawaban'] ?? 'Jawaban uraian sempurna dan sangat komprehensif.',
                    'nilai_perolehan' => $nilaiPerolehan,
                    'is_correct'      => $isUraian ? null : true,
                    'is_ragu'         => false,
                    'waktu_jawab'     => $mulai->copy()->addMinutes($urutan + 1),
                ]);
            }

            $this->logEvent($attempt->id, AttemptLog::EVENT_SUBMIT, "status=selesai", $selesai);
            $this->updateParticipant($session->id, $user->id, ExamSessionParticipant::STATUS_SELESAI);

            // Hitung nilai_akhir final
            $this->scoring->regrade($attempt);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SKENARIO F: Tab Switch → Diskualifikasi
    // ─────────────────────────────────────────────────────────────────────────

    private function buatAttemptDiskualifikasiTabSwitch(
        ExamSession $session,
        User $user,
        int $attemptKe,
        \Carbon\Carbon $mulai,
    ): void {
        if ($this->attemptExists($session, $user, $attemptKe, ExamAttempt::STATUS_DISKUALIFIKASI)) {
            return;
        }

        DB::transaction(function () use ($session, $user, $attemptKe, $mulai) {
            $questions = $this->getSessionQuestions($session);
            $waktsBuat = $mulai->copy()->addMinutes(12); // didiskualifikasi di menit ke-12

            $attempt = ExamAttempt::create([
                'exam_session_id' => $session->id,
                'user_id'         => $user->id,
                'waktu_mulai'     => $mulai,
                'waktu_selesai'   => $waktsBuat,
                'status'          => ExamAttempt::STATUS_DISKUALIFIKASI,
                'nilai_akhir'     => null,
                'jumlah_benar'    => 0,
                'jumlah_salah'    => 0,
                'jumlah_kosong'   => $questions->count(),
                'attempt_ke'      => $attemptKe,
            ]);

            // Isi 3 soal saja (baru sempat menjawab 3 sebelum diskualifikasi)
            foreach ($questions->values() as $urutan => $question) {
                $dijawab = $urutan < 3;
                $jawaban = $dijawab ? $this->generateJawaban($question, rand(0, 1) === 1) : null;

                AttemptQuestion::create([
                    'attempt_id'      => $attempt->id,
                    'question_id'     => $question->id,
                    'urutan'          => $urutan + 1,
                    'jawaban_peserta' => $dijawab ? $jawaban['jawaban'] : null,
                    'nilai_perolehan' => null,
                    'is_correct'      => null,
                    'is_ragu'         => false,
                    'waktu_jawab'     => $dijawab ? $mulai->copy()->addMinutes($urutan + 1) : null,
                ]);
            }

            // Log: 5x tab switch kemudian kick
            foreach (range(1, 5) as $i) {
                AttemptLog::create([
                    'attempt_id' => $attempt->id,
                    'event_type' => AttemptLog::EVENT_TAB_SWITCH,
                    'detail'     => "tab_switch_count={$i}",
                    'created_at' => $mulai->copy()->addMinutes($i * 2),
                ]);
            }

            AttemptLog::create([
                'attempt_id' => $attempt->id,
                'event_type' => AttemptLog::EVENT_KICK,
                'detail'     => 'tab_switch_melebihi_batas',
                'created_at' => $waktsBuat,
            ]);

            $this->updateParticipant($session->id, $user->id, ExamSessionParticipant::STATUS_DISKUALIFIKASI);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SKENARIO G: Timeout
    // ─────────────────────────────────────────────────────────────────────────

    private function buatAttemptTimeout(
        ExamSession $session,
        User $user,
        int $attemptKe,
        \Carbon\Carbon $mulai,
    ): void {
        if ($this->attemptExists($session, $user, $attemptKe, ExamAttempt::STATUS_TIMEOUT)) {
            return;
        }

        DB::transaction(function () use ($session, $user, $attemptKe, $mulai) {
            $questions   = $this->getSessionQuestions($session);
            $durasi      = $session->package->durasi_menit ?? 45;
            $waktuSelesai = $mulai->copy()->addMinutes($durasi); // tepat habis waktu

            $attempt = ExamAttempt::create([
                'exam_session_id' => $session->id,
                'user_id'         => $user->id,
                'waktu_mulai'     => $mulai,
                'waktu_selesai'   => $waktuSelesai,
                'status'          => ExamAttempt::STATUS_TIMEOUT,
                'nilai_akhir'     => null, // pending (package=manual)
                'jumlah_benar'    => 0,
                'jumlah_salah'    => 0,
                'jumlah_kosong'   => 0,
                'attempt_ke'      => $attemptKe,
            ]);

            // Hanya 60% soal terjawab sebelum timeout
            $batasDijawab = (int) ceil($questions->count() * 0.6);

            foreach ($questions->values() as $urutan => $question) {
                $isUraian = $question->tipe === Question::TIPE_URAIAN;
                $dijawab  = $urutan < $batasDijawab;
                $jawaban  = ($dijawab && ! $isUraian) ? $this->generateJawaban($question, rand(0, 100) < 60) : null;

                AttemptQuestion::create([
                    'attempt_id'      => $attempt->id,
                    'question_id'     => $question->id,
                    'urutan'          => $urutan + 1,
                    'jawaban_peserta' => $jawaban ? $jawaban['jawaban'] : ($dijawab && $isUraian ? 'Jawaban uraian belum selesai saat timeout.' : null),
                    'nilai_perolehan' => $isUraian ? null : ($jawaban ? $jawaban['nilai'] : 0.0),
                    'is_correct'      => $isUraian ? null : ($jawaban ? $jawaban['correct'] : false),
                    'is_ragu'         => false,
                    'waktu_jawab'     => $dijawab ? $mulai->copy()->addMinutes($urutan + 1) : null,
                ]);
            }

            $this->logEvent($attempt->id, AttemptLog::EVENT_TIMEOUT, "status=timeout", $waktuSelesai);
            $this->updateParticipant($session->id, $user->id, ExamSessionParticipant::STATUS_SELESAI);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SKENARIO H: Remidi Bertahap (3 attempt) di S7
    // ─────────────────────────────────────────────────────────────────────────

    private function buatAttemptRemidi(ExamSession $session, User $user): void
    {
        $scenarios = [
            ['ke' => 1, 'benar_persen' => 40, 'selisih_jam' => 5],
            ['ke' => 2, 'benar_persen' => 65, 'selisih_jam' => 3],
            ['ke' => 3, 'benar_persen' => 80, 'selisih_jam' => 1],
        ];

        foreach ($scenarios as $s) {
            if ($this->attemptExists($session, $user, $s['ke'])) {
                continue;
            }

            DB::transaction(function () use ($session, $user, $s) {
                $questions  = $this->getSessionQuestions($session);
                $mulai      = now()->subHours($s['selisih_jam'] + 1);
                $selesai    = $mulai->copy()->addMinutes(40);
                $targetBenar = (int) ceil($questions->count() * ($s['benar_persen'] / 100));

                $attempt = ExamAttempt::create([
                    'exam_session_id' => $session->id,
                    'user_id'         => $user->id,
                    'waktu_mulai'     => $mulai,
                    'waktu_selesai'   => $selesai,
                    'status'          => ExamAttempt::STATUS_SELESAI,
                    'nilai_akhir'     => null,
                    'jumlah_benar'    => 0,
                    'jumlah_salah'    => 0,
                    'jumlah_kosong'   => 0,
                    'attempt_ke'      => $s['ke'],
                ]);

                foreach ($questions->values() as $urutan => $question) {
                    $isUraian        = $question->tipe === Question::TIPE_URAIAN;
                    $shouldBeCorrect = ! $isUraian && $urutan < $targetBenar;
                    $jawaban         = $this->generateJawaban($question, $shouldBeCorrect);

                    $nilaiPerolehan = $isUraian
                        ? round((float) $question->bobot * ($s['benar_persen'] / 100), 2) // simulasi dinilai
                        : $jawaban['nilai'];

                    AttemptQuestion::create([
                        'attempt_id'      => $attempt->id,
                        'question_id'     => $question->id,
                        'urutan'          => $urutan + 1,
                        'jawaban_peserta' => $jawaban['jawaban'] ?? 'Jawaban uraian attempt ke-' . $s['ke'],
                        'nilai_perolehan' => $nilaiPerolehan,
                        'is_correct'      => $isUraian ? null : $jawaban['correct'],
                        'is_ragu'         => false,
                        'waktu_jawab'     => $mulai->copy()->addMinutes($urutan + 1),
                    ]);
                }

                $this->logEvent($attempt->id, AttemptLog::EVENT_SUBMIT, "status=selesai", $selesai);
                $this->updateParticipant($session->id, $user->id, ExamSessionParticipant::STATUS_SELESAI);

                $this->scoring->regrade($attempt);
            });
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SKENARIO I: Ragu-Ragu flags
    // ─────────────────────────────────────────────────────────────────────────

    private function buatAttemptDenganRagu(
        ExamSession $session,
        User $user,
        int $attemptKe,
        \Carbon\Carbon $mulai,
        \Carbon\Carbon $selesai,
    ): void {
        if ($this->attemptExists($session, $user, $attemptKe)) {
            return;
        }

        DB::transaction(function () use ($session, $user, $attemptKe, $mulai, $selesai) {
            $questions = $this->getSessionQuestions($session);

            $attempt = ExamAttempt::create([
                'exam_session_id' => $session->id,
                'user_id'         => $user->id,
                'waktu_mulai'     => $mulai,
                'waktu_selesai'   => $selesai,
                'status'          => ExamAttempt::STATUS_SELESAI,
                'nilai_akhir'     => null,
                'jumlah_benar'    => 0,
                'jumlah_salah'    => 0,
                'jumlah_kosong'   => 0,
                'attempt_ke'      => $attemptKe,
            ]);

            foreach ($questions->values() as $urutan => $question) {
                $isUraian        = $question->tipe === Question::TIPE_URAIAN;
                $shouldBeCorrect = ! $isUraian && rand(0, 100) < 60;
                $jawaban         = $this->generateJawaban($question, $shouldBeCorrect);

                // Soal ke-2, 5, 8, 11, ... ditandai ragu
                $isRagu = ($urutan % 3 === 1) && ! $isUraian;

                $nilaiPerolehan = $isUraian
                    ? round((float) $question->bobot * 0.6, 2)
                    : $jawaban['nilai'];

                AttemptQuestion::create([
                    'attempt_id'      => $attempt->id,
                    'question_id'     => $question->id,
                    'urutan'          => $urutan + 1,
                    'jawaban_peserta' => $jawaban['jawaban'] ?? 'Jawaban uraian dengan ketidakpastian.',
                    'nilai_perolehan' => $nilaiPerolehan,
                    'is_correct'      => $isUraian ? null : $jawaban['correct'],
                    'is_ragu'         => $isRagu,
                    'waktu_jawab'     => $mulai->copy()->addMinutes($urutan + 1),
                ]);
            }

            $this->logEvent($attempt->id, AttemptLog::EVENT_SUBMIT, "status=selesai", $selesai);
            $this->updateParticipant($session->id, $user->id, ExamSessionParticipant::STATUS_SELESAI);

            $this->scoring->regrade($attempt);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SKENARIO J: Kick (Diskualifikasi Manual oleh Admin)
    // ─────────────────────────────────────────────────────────────────────────

    private function buatAttemptKick(
        ExamSession $session,
        User $user,
        int $attemptKe,
        \Carbon\Carbon $mulai,
    ): void {
        if ($this->attemptExists($session, $user, $attemptKe, ExamAttempt::STATUS_DISKUALIFIKASI)) {
            return;
        }

        DB::transaction(function () use ($session, $user, $attemptKe, $mulai) {
            $questions    = $this->getSessionQuestions($session);
            $waktuKick    = $mulai->copy()->addMinutes(18);

            $attempt = ExamAttempt::create([
                'exam_session_id' => $session->id,
                'user_id'         => $user->id,
                'waktu_mulai'     => $mulai,
                'waktu_selesai'   => $waktuKick,
                'status'          => ExamAttempt::STATUS_DISKUALIFIKASI,
                'nilai_akhir'     => null,
                'jumlah_benar'    => 0,
                'jumlah_salah'    => 0,
                'jumlah_kosong'   => $questions->count(),
                'attempt_ke'      => $attemptKe,
            ]);

            // Hanya menjawab 5 soal sebelum dikick
            foreach ($questions->values() as $urutan => $question) {
                $dijawab = $urutan < 5;
                $jawaban = $dijawab ? $this->generateJawaban($question, rand(0, 1) === 1) : null;

                AttemptQuestion::create([
                    'attempt_id'      => $attempt->id,
                    'question_id'     => $question->id,
                    'urutan'          => $urutan + 1,
                    'jawaban_peserta' => $dijawab ? ($jawaban['jawaban'] ?? null) : null,
                    'nilai_perolehan' => null,
                    'is_correct'      => null,
                    'is_ragu'         => false,
                    'waktu_jawab'     => $dijawab ? $mulai->copy()->addMinutes($urutan + 1) : null,
                ]);
            }

            // 2 tab switch kemudian kick manual
            AttemptLog::create([
                'attempt_id' => $attempt->id,
                'event_type' => AttemptLog::EVENT_TAB_SWITCH,
                'detail'     => 'tab_switch_count=1',
                'created_at' => $mulai->copy()->addMinutes(5),
            ]);
            AttemptLog::create([
                'attempt_id' => $attempt->id,
                'event_type' => AttemptLog::EVENT_BLUR,
                'detail'     => 'window_blur',
                'created_at' => $mulai->copy()->addMinutes(10),
            ]);
            AttemptLog::create([
                'attempt_id' => $attempt->id,
                'event_type' => AttemptLog::EVENT_KICK,
                'detail'     => "kicked_by={$this->admin->id};reason=kecurangan_akademik",
                'created_at' => $waktuKick,
            ]);

            $this->updateParticipant($session->id, $user->id, ExamSessionParticipant::STATUS_DISKUALIFIKASI);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Generate jawaban sesuai tipe soal.
     * Mengembalikan ['jawaban' => string|null, 'nilai' => float|null, 'correct' => bool|null]
     */
    private function generateJawaban(Question $question, bool $shouldBeCorrect): array
    {
        return match ($question->tipe) {
            Question::TIPE_PG, Question::TIPE_PG_BOBOT => $this->jawabanPg($question, $shouldBeCorrect),
            Question::TIPE_PGJ                          => $this->jawabanPgj($question, $shouldBeCorrect),
            Question::TIPE_JODOH                        => $this->jawabanJodoh($question, $shouldBeCorrect),
            Question::TIPE_ISIAN                        => $this->jawabanIsian($question, $shouldBeCorrect),
            Question::TIPE_URAIAN                       => ['jawaban' => 'Jawaban uraian peserta.', 'nilai' => null, 'correct' => null],
            default                                     => ['jawaban' => null, 'nilai' => null, 'correct' => null],
        };
    }

    private function jawabanPg(Question $question, bool $benar): array
    {
        $options     = $question->options()->where('aktif', true)->get();
        if ($options->isEmpty()) {
            return ['jawaban' => null, 'nilai' => null, 'correct' => null];
        }

        $correctOpts = $options->where('is_correct', true);
        $wrongOpts   = $options->where('is_correct', false);

        if ($benar) {
            $selected    = $correctOpts->first() ?? $options->first();
            $bobotPersen = (int) $selected->bobot_persen;
            $nilai       = $question->tipe === Question::TIPE_PG_BOBOT
                ? round((float) $question->bobot * ($bobotPersen / 100), 2)
                : (float) $question->bobot;
        } else {
            $selected    = $wrongOpts->first() ?? $correctOpts->last();
            $bobotPersen = (int) ($selected?->bobot_persen ?? 0);
            $nilai       = $question->tipe === Question::TIPE_PG_BOBOT
                ? round((float) $question->bobot * ($bobotPersen / 100), 2)
                : 0.0;
        }

        return ['jawaban' => (string) $selected->id, 'nilai' => $nilai, 'correct' => $benar];
    }

    private function jawabanPgj(Question $question, bool $benar): array
    {
        $options     = $question->options()->where('aktif', true)->get();
        if ($options->isEmpty()) {
            return ['jawaban' => '[]', 'nilai' => null, 'correct' => null];
        }

        $correctIds  = $options->where('is_correct', true)->pluck('id')->values()->all();
        $wrongIds    = $options->where('is_correct', false)->pluck('id')->values()->all();

        $selected = $benar
            ? $correctIds
            : array_merge(array_slice($correctIds, 0, 1), array_slice($wrongIds, 0, 1));

        return ['jawaban' => json_encode($selected), 'nilai' => null, 'correct' => $benar];
    }

    private function jawabanJodoh(Question $question, bool $benar): array
    {
        $matches = $question->matches()->orderBy('urutan')->get();
        if ($matches->isEmpty()) {
            return ['jawaban' => '{}', 'nilai' => null, 'correct' => null];
        }

        $ids = $matches->pluck('id')->all();
        if ($benar) {
            $map = array_combine($ids, $ids);
        } else {
            $rotated = count($ids) > 1
                ? array_merge(array_slice($ids, 1), array_slice($ids, 0, 1))
                : $ids;
            $map = array_combine($ids, $rotated);
        }

        return ['jawaban' => json_encode($map), 'nilai' => null, 'correct' => $benar];
    }

    private function jawabanIsian(Question $question, bool $benar): array
    {
        $keywords = $question->keywords()->get();
        if ($keywords->isEmpty()) {
            return ['jawaban' => null, 'nilai' => null, 'correct' => null];
        }

        $jawaban = $benar ? $keywords->first()->keyword : 'jawaban salah';
        $nilai   = $benar ? (float) $question->bobot : 0.0;

        return ['jawaban' => $jawaban, 'nilai' => $nilai, 'correct' => $benar];
    }

    private function getSessionQuestions(ExamSession $session): \Illuminate\Database\Eloquent\Collection
    {
        return $session->package->questions()
            ->with('options', 'matches', 'keywords')
            ->get();
    }

    private function attemptExists(ExamSession $session, User $user, int $attemptKe, ?string $status = null): bool
    {
        $query = ExamAttempt::where('exam_session_id', $session->id)
            ->where('user_id', $user->id)
            ->where('attempt_ke', $attemptKe);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->exists();
    }

    private function logEvent(int $attemptId, string $eventType, string $detail, \Carbon\Carbon $at): void
    {
        AttemptLog::create([
            'attempt_id' => $attemptId,
            'event_type' => $eventType,
            'detail'     => $detail,
            'created_at' => $at,
        ]);
    }

    private function updateParticipant(int $sessionId, int $userId, string $status): void
    {
        ExamSessionParticipant::where('exam_session_id', $sessionId)
            ->where('user_id', $userId)
            ->update(['status' => $status]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RINGKASAN
    // ─────────────────────────────────────────────────────────────────────────

    private function printRingkasan(): void
    {
        $totalAttempts  = ExamAttempt::count();
        $pendingUraian  = ExamAttempt::whereIn('status', [ExamAttempt::STATUS_SELESAI, ExamAttempt::STATUS_TIMEOUT])
            ->whereHas('questions', fn ($q) =>
                $q->whereHas('question', fn ($q2) => $q2->where('tipe', 'URAIAN'))
                  ->whereNull('nilai_perolehan')
            )->count();

        $this->command->newLine();
        $this->command->info('╔══════════════════════════════════════════════════════════╗');
        $this->command->info('║         RINGKASAN ATTEMPT SEEDER (PHASE 6)              ║');
        $this->command->info('╠══════════════════════════════════════════════════════════╣');
        $this->command->line("  Total attempts dalam DB   : {$totalAttempts}");
        $this->command->line("  Pending manual grading    : {$pendingUraian} attempt");
        $this->command->info('╠══════════════════════════════════════════════════════════╣');
        $this->command->line('  A. PST-006 di S9          : URAIAN pending → GradingResource');
        $this->command->line('  A. PST-007 di S10         : URAIAN pending → GradingResource');
        $this->command->line('  B. PST-008 di S9          : URAIAN sudah dinilai (nilai_akhir computed)');
        $this->command->line('  C. PST-009 di S9          : File upload URAIAN (pending)');
        $this->command->line('  D. PST-010 di S9          : Zero score (semua kosong)');
        $this->command->line('  E. PST-006 di S10         : Perfect score (nilai = 100)');
        $this->command->line('  F. PST-007 di S1          : Diskualifikasi (5x tab switch)');
        $this->command->line('  G. PST-008 di S7          : Timeout (habis waktu)');
        $this->command->line('  H. PST-003 di S7          : Remidi 3x (nilai 40→65→80)');
        $this->command->line('  I. PST-004 di S7          : Ragu-ragu flags');
        $this->command->line('  J. PST-005 di S1          : Kick (diskualifikasi manual admin)');
        $this->command->info('╚══════════════════════════════════════════════════════════╝');
    }
}
