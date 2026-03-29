<?php

namespace Database\Seeders;

use App\Models\AttemptLog;
use App\Models\AttemptQuestion;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * ExamSessionSeeder — Sesi Ujian Komprehensif
 *
 * Membuat 10 sesi ujian yang mencakup semua kemungkinan kombinasi:
 *
 *  SESI AKTIF (dapat dikerjakan sekarang):
 *   S1 : Matematika tanpa token, acak, remidi 1 kali       → Andi: belum
 *   S2 : TIK, token TIK2026, acak, remidi 1 kali           → Andi: belum
 *   S3 : B.Indo, tanpa token, max 2×, tampilkan hasil      → Andi: sudah selesai 1× (bisa remidi)
 *   S4 : Sejarah, tanpa token, max 1× (no remidi)          → Andi: sudah selesai (tidak bisa lagi)
 *   S5 : Tryout, tanpa token, max 3×, tampilkan hasil      → Andi: sudah selesai 2× (1 kali lagi)
 *   S6 : IPA, token IPA2026, acak soal+opsi, max 1×        → Andi: sedang berlangsung
 *   S7 : Matematika 2, tanpa token, max 0 (unlimited)      → Andi: belum, paket beda sesi
 *
 *  SESI DRAFT (belum dibuka):
 *   S8 : IPA Draft                                         → Andi: terdaftar, belum bisa mulai
 *
 *  SESI SELESAI (waktu sudah lewat):
 *   S9 : Sejarah selesai (kemarin)                         → Andi: sudah selesai + dapat hasil
 *   S10: B.Indo selesai, tampilkan_hasil=false             → Andi: selesai, tidak dapat hasil
 *
 *  SESI DIBATALKAN:
 *   S11: TIK dibatalkan                                    → Andi: terdaftar, tidak relevan
 *
 * Idempotent: dikenali dari nama_sesi unik.
 */
class ExamSessionSeeder extends Seeder
{
    private User $admin;
    private User $andi;
    private array $pesertaRombel; // user objects PST-001..PST-010
    private array $packages;

    public function run(): void
    {
        $this->admin = User::where('level', '>=', User::LEVEL_ADMIN)->first()
            ?? throw new \RuntimeException('Tidak ada user admin. Jalankan UserSeeder terlebih dahulu.');

        $this->andi = User::where('email', 'andi@cabt.local')->first()
            ?? throw new \RuntimeException('User andi@cabt.local tidak ditemukan. Jalankan UserSeeder terlebih dahulu.');

        // Peserta untuk sesi (10 orang)
        $this->pesertaRombel = User::where('level', User::LEVEL_PESERTA)
            ->whereIn('nomor_peserta', [
                'PST-001', 'PST-002', 'PST-003', 'PST-004', 'PST-005',
                'PST-006', 'PST-007', 'PST-008', 'PST-009', 'PST-010',
            ])
            ->get()
            ->keyBy('nomor_peserta')
            ->all();

        // Load semua paket
        $this->packages = [
            'matematika' => ExamPackage::where('nama', 'like', 'Matematika%')->first(),
            'ipa'        => ExamPackage::where('nama', 'like', 'IPA%')->first(),
            'bindo'      => ExamPackage::where('nama', 'like', 'Bahasa%')->first(),
            'sejarah'    => ExamPackage::where('nama', 'like', 'Sejarah%')->first(),
            'tik'        => ExamPackage::where('nama', 'like', 'TIK%')->first(),
            'tryout'     => ExamPackage::where('nama', 'like', 'Tryout%')->first(),
        ];

        foreach ($this->packages as $key => $pkg) {
            if (! $pkg) {
                $this->command->warn("Paket '{$key}' tidak ditemukan. Jalankan ExamPackageSeeder terlebih dahulu.");
                return;
            }
        }

        // ─── Buat semua sesi ─────────────────────────────────────────────────
        $this->command->info('Membuat sesi ujian...');

        $s1  = $this->buatSesi('S1',  $this->packages['matematika'], 'Ujian Harian Matematika — X IPA 1', ExamSession::STATUS_AKTIF, now()->subHours(1), now()->addHours(2), null);
        $s2  = $this->buatSesi('S2',  $this->packages['tik'],         'Ujian TIK — X IPS 1 (Dengan Token)', ExamSession::STATUS_AKTIF, now()->subMinutes(30), now()->addHours(1), 'TIK2026');
        $s3  = $this->buatSesi('S3',  $this->packages['bindo'],       'Ujian B.Indonesia — X IPA 1 (Remidi Aktif)', ExamSession::STATUS_AKTIF, now()->subHours(2), now()->addHours(1), null);
        $s4  = $this->buatSesi('S4',  $this->packages['sejarah'],     'Ujian Sejarah — X IPA 1 (1x, No Remidi)', ExamSession::STATUS_AKTIF, now()->subHours(1), now()->addHour(), null);
        $s5  = $this->buatSesi('S5',  $this->packages['tryout'],      'Tryout Akhir Tahun — X IPA 1 (Sisa 1 Kali)', ExamSession::STATUS_AKTIF, now()->subHours(3), now()->addHours(4), null);
        $s6  = $this->buatSesi('S6',  $this->packages['ipa'],         'Ujian IPA — X IPA 2 (Andi Sedang Berlangsung)', ExamSession::STATUS_AKTIF, now()->subMinutes(15), now()->addMinutes(45), 'IPA2026');
        $s7  = $this->buatSesi('S7',  $this->packages['matematika'],  'Ujian Matematika — X IPA 2 (Unlimited)', ExamSession::STATUS_AKTIF, now()->subHour(), now()->addHours(3), null);
        $s8  = $this->buatSesi('S8',  $this->packages['ipa'],         'Ujian IPA — X IPS 1 (Draft, Belum Dibuka)', ExamSession::STATUS_DRAFT, now()->addDay(), now()->addDay()->addHours(2), null);
        $s9  = $this->buatSesi('S9',  $this->packages['sejarah'],     'Ujian Sejarah — X IPS 1 (Sudah Selesai)', ExamSession::STATUS_SELESAI, now()->subDay()->subHours(2), now()->subDay(), null);
        $s10 = $this->buatSesi('S10', $this->packages['bindo'],       'Ujian B.Indonesia — X IPS 1 (Selesai, Nilai Tersembunyi)', ExamSession::STATUS_SELESAI, now()->subDays(3)->subHour(), now()->subDays(3), null);
        $s11 = $this->buatSesi('S11', $this->packages['tik'],         'Ujian TIK — X IPA 2 (Dibatalkan)', ExamSession::STATUS_DIBATALKAN, now()->subDay(), now()->subDay()->addHours(2), null);

        // ─── Daftarkan peserta ke masing-masing sesi ─────────────────────────
        $this->command->info('Mendaftarkan peserta...');

        // S1 — semua peserta belum; Andi terdaftar tapi belum mengerjakan
        $this->daftarPeserta($s1, array_values($this->pesertaRombel));

        // S2 — token, semua terdaftar
        $this->daftarPeserta($s2, array_values($this->pesertaRombel));

        // S3 — Andi sudah selesai 1x (masih bisa remidi karena max=2)
        $this->daftarPeserta($s3, array_values($this->pesertaRombel));

        // S4 — Andi sudah selesai, max=1 → tidak bisa remidi
        $this->daftarPeserta($s4, array_values($this->pesertaRombel));

        // S5 — Andi sudah 2x, max=3 → 1 kesempatan lagi
        $this->daftarPeserta($s5, array_values($this->pesertaRombel));

        // S6 — Andi sedang berlangsung
        $this->daftarPeserta($s6, array_values($this->pesertaRombel));

        // S7 — semua belum, unlimited
        $allPeserta = User::where('level', User::LEVEL_PESERTA)->take(8)->get()->all();
        $this->daftarPeserta($s7, $allPeserta);

        // S8 — draft, peserta terdaftar tapi belum bisa mulai
        $this->daftarPeserta($s8, array_values($this->pesertaRombel));

        // S9 — selesai, semua peserta sudah selesai
        $this->daftarPeserta($s9, array_values($this->pesertaRombel));

        // S10 — selesai, nilai tersembunyi
        $this->daftarPeserta($s10, array_values($this->pesertaRombel));

        // S11 — dibatalkan, tidak ada aktivitas
        $this->daftarPeserta($s11, array_values(array_slice($this->pesertaRombel, 0, 5)));

        // ─── Buat skenario attempt untuk Andi ────────────────────────────────
        $this->command->info('Membuat skenario attempt Andi...');

        // S3: Andi selesai 1x (attempt_ke=1, selesai), participation=selesai
        $this->buatAttemptSelesai($s3, $this->andi, 1, 75.0, 7, 2, 1, ExamAttempt::STATUS_SELESAI, now()->subHour()->subMinutes(15), now()->subHour());

        // S4: Andi selesai 1x (no remidi), participation=selesai
        $this->buatAttemptSelesai($s4, $this->andi, 1, 60.0, 6, 3, 1, ExamAttempt::STATUS_SELESAI, now()->subMinutes(50), now()->subMinutes(20));

        // S5: Andi sudah 2x attempt
        $this->buatAttemptSelesai($s5, $this->andi, 1, 50.0, 15, 10, 5, ExamAttempt::STATUS_SELESAI, now()->subHours(3), now()->subHours(2)->subMinutes(30));
        $this->buatAttemptSelesai($s5, $this->andi, 2, 66.67, 20, 7, 3, ExamAttempt::STATUS_SELESAI, now()->subHours(2), now()->subHour()->subMinutes(15));

        // S6: Andi sedang berlangsung (attempt aktif masih berjalan)
        $this->buatAttemptBerlangsung($s6, $this->andi, 1);

        // S9: Sesi selesai — Andi juga selesai dengan nilai baik
        $this->buatAttemptSelesai($s9, $this->andi, 1, 85.0, 8, 1, 1, ExamAttempt::STATUS_SELESAI, now()->subDay()->subHours(2), now()->subDay()->subHour());

        // S10: Sesi selesai, nilai tersembunyi — Andi juga selesai
        $this->buatAttemptSelesai($s10, $this->andi, 1, 90.0, 9, 1, 0, ExamAttempt::STATUS_SELESAI, now()->subDays(3)->subHour(), now()->subDays(3)->subMinutes(20));

        // ─── Buat attempt untuk peserta lain (keramaian data) ────────────────
        $this->command->info('Membuat attempt peserta lain...');
        $this->buatAttemptPesertaLain($s1, $s9, $s10);

        // ─── Ringkasan ────────────────────────────────────────────────────────
        $this->printRingkasan();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function buatSesi(
        string $label,
        ExamPackage $paket,
        string $namaSesi,
        string $status,
        \Carbon\Carbon $mulai,
        \Carbon\Carbon $selesai,
        ?string $token,
    ): ExamSession {
        $session = ExamSession::firstOrCreate(
            ['nama_sesi' => $namaSesi],
            [
                'exam_package_id' => $paket->id,
                'waktu_mulai'     => $mulai,
                'waktu_selesai'   => $selesai,
                'status'          => $status,
                'token_akses'     => $token,
                'created_by'      => $this->admin->id,
            ]
        );

        $isNew   = $session->wasRecentlyCreated;
        $mark    = $isNew ? '<info>✓</info>' : '<comment>–</comment>';
        $tokenStr = $token ? " [token: {$token}]" : '';
        $this->command->line("  {$mark} [{$label}][{$status}]{$tokenStr} {$namaSesi}");

        return $session;
    }

    private function daftarPeserta(ExamSession $session, array $pesertaList): void
    {
        foreach ($pesertaList as $peserta) {
            ExamSessionParticipant::firstOrCreate(
                ['exam_session_id' => $session->id, 'user_id' => $peserta->id],
                ['status' => ExamSessionParticipant::STATUS_BELUM]
            );
        }
    }

    /**
     * Buat attempt yang sudah selesai dengan data soal yang realistis.
     */
    private function buatAttemptSelesai(
        ExamSession $session,
        User $user,
        int $attemptKe,
        float $nilaiAkhir,
        int $jumlahBenar,
        int $jumlahSalah,
        int $jumlahKosong,
        string $status,
        \Carbon\Carbon $waktuMulai,
        \Carbon\Carbon $waktuSelesai,
    ): ?ExamAttempt {
        // Idempotent: skip jika attempt ke-X sudah ada
        $existing = ExamAttempt::where('exam_session_id', $session->id)
            ->where('user_id', $user->id)
            ->where('attempt_ke', $attemptKe)
            ->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($session, $user, $attemptKe, $nilaiAkhir, $jumlahBenar, $jumlahSalah, $jumlahKosong, $status, $waktuMulai, $waktuSelesai) {
            $attempt = ExamAttempt::create([
                'exam_session_id' => $session->id,
                'user_id'         => $user->id,
                'waktu_mulai'     => $waktuMulai,
                'waktu_selesai'   => $waktuSelesai,
                'status'          => $status,
                'nilai_akhir'     => $nilaiAkhir,
                'jumlah_benar'    => $jumlahBenar,
                'jumlah_salah'    => $jumlahSalah,
                'jumlah_kosong'   => $jumlahKosong,
                'attempt_ke'      => $attemptKe,
            ]);

            // Ambil soal paket dan isi attempt_questions
            $questions = $session->package->questions()
                ->with('options', 'matches', 'keywords')
                ->get();

            foreach ($questions->values() as $urutan => $question) {
                $jawaban = $this->generateJawaban($question, $urutan, $jumlahBenar, $questions->count());

                AttemptQuestion::create([
                    'attempt_id'      => $attempt->id,
                    'question_id'     => $question->id,
                    'urutan'          => $urutan + 1,
                    'jawaban_peserta' => $jawaban['jawaban'],
                    'nilai_perolehan' => $jawaban['nilai'],
                    'is_correct'      => $jawaban['correct'],
                    'is_ragu'         => $jawaban['ragu'],
                    'waktu_jawab'     => $waktuMulai->copy()->addMinutes($urutan + 1),
                ]);
            }

            // Log event submit
            AttemptLog::create([
                'attempt_id' => $attempt->id,
                'event_type' => $status === ExamAttempt::STATUS_TIMEOUT ? AttemptLog::EVENT_TIMEOUT : AttemptLog::EVENT_SUBMIT,
                'detail'     => "status={$status}",
                'created_at' => $waktuSelesai,
            ]);

            // Update participation status
            ExamSessionParticipant::where('exam_session_id', $session->id)
                ->where('user_id', $user->id)
                ->update(['status' => ExamSessionParticipant::STATUS_SELESAI]);

            return $attempt;
        });
    }

    /**
     * Buat attempt yang masih berlangsung (belum selesai).
     */
    private function buatAttemptBerlangsung(ExamSession $session, User $user, int $attemptKe): ?ExamAttempt
    {
        $existing = ExamAttempt::where('exam_session_id', $session->id)
            ->where('user_id', $user->id)
            ->where('attempt_ke', $attemptKe)
            ->where('status', ExamAttempt::STATUS_BERLANGSUNG)
            ->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($session, $user, $attemptKe) {
            $waktuMulai = now()->subMinutes(15);

            $attempt = ExamAttempt::create([
                'exam_session_id' => $session->id,
                'user_id'         => $user->id,
                'waktu_mulai'     => $waktuMulai,
                'waktu_selesai'   => null,
                'status'          => ExamAttempt::STATUS_BERLANGSUNG,
                'nilai_akhir'     => null,
                'jumlah_benar'    => 0,
                'jumlah_salah'    => 0,
                'jumlah_kosong'   => 0,
                'attempt_ke'      => $attemptKe,
            ]);

            $questions = $session->package->questions()
                ->with('options', 'matches', 'keywords')
                ->get();

            foreach ($questions->values() as $urutan => $question) {
                // Isi sebagian jawaban — simulasi sedang dikerjakan (60% terjawab)
                $dijawab = ($urutan < (int) ceil($questions->count() * 0.6));
                $jawaban = $dijawab ? $this->generateJawaban($question, $urutan, (int) ceil($questions->count() * 0.4), $questions->count()) : ['jawaban' => null, 'nilai' => null, 'correct' => null, 'ragu' => false];

                AttemptQuestion::create([
                    'attempt_id'      => $attempt->id,
                    'question_id'     => $question->id,
                    'urutan'          => $urutan + 1,
                    'jawaban_peserta' => $jawaban['jawaban'],
                    'nilai_perolehan' => null,
                    'is_correct'      => null,
                    'is_ragu'         => $dijawab && $urutan % 4 === 0, // beberapa soal ragu
                    'waktu_jawab'     => $dijawab ? $waktuMulai->copy()->addMinutes($urutan + 1) : null,
                ]);
            }

            // Update participation → sedang
            ExamSessionParticipant::where('exam_session_id', $session->id)
                ->where('user_id', $user->id)
                ->update(['status' => ExamSessionParticipant::STATUS_SEDANG]);

            return $attempt;
        });
    }

    /**
     * Generate jawaban realistis berdasarkan tipe soal.
     * Menggunakan index untuk menentukan apakah soal ini "benar" atau "salah".
     *
     * Format jawaban_peserta mengikuti apa yang disimpan oleh AJAX di exam sesungguhnya:
     *  - PG / PG_BOBOT : string ID option     → "123"
     *  - PGJ            : JSON array of IDs   → "[123, 456]"
     *  - JODOH          : JSON obj match_id→match_id → {"1":"1","2":"3",...}
     *  - ISIAN/URAIAN   : plain text string
     */
    private function generateJawaban(Question $question, int $idx, int $targetBenar, int $total): array
    {
        $shouldBeCorrect = $idx < $targetBenar;
        $isRagu = $idx % 5 === 3;
        $tipe   = $question->tipe;

        if (in_array($tipe, [Question::TIPE_PG, Question::TIPE_PG_BOBOT])) {
            $options    = $question->options()->where('aktif', true)->get();
            if ($options->isEmpty()) return ['jawaban' => null, 'nilai' => null, 'correct' => null, 'ragu' => false];
            $correctOpts = $options->where('is_correct', true);
            $wrongOpts   = $options->where('is_correct', false);

            $selected = $shouldBeCorrect
                ? ($correctOpts->first()?->id ?? $options->first()->id)
                : ($wrongOpts->first()?->id ?? $options->last()->id);

            $bobotPersen = $options->where('id', $selected)->first()?->bobot_persen ?? 0;
            $nilai = $tipe === Question::TIPE_PG_BOBOT
                ? ($bobotPersen / 100) * $question->bobot
                : ($shouldBeCorrect ? (float) $question->bobot : 0.0);

            return ['jawaban' => (string) $selected, 'nilai' => $nilai, 'correct' => $shouldBeCorrect, 'ragu' => $isRagu];
        }

        if ($tipe === Question::TIPE_PGJ) {
            $options    = $question->options()->where('aktif', true)->get();
            if ($options->isEmpty()) return ['jawaban' => '[]', 'nilai' => null, 'correct' => null, 'ragu' => $isRagu];
            $correctIds = $options->where('is_correct', true)->pluck('id')->values()->all();
            $wrongIds   = $options->where('is_correct', false)->pluck('id')->values()->all();

            $selected = $shouldBeCorrect
                ? $correctIds
                : array_merge(array_slice($correctIds, 0, 1), array_slice($wrongIds, 0, 1));

            return ['jawaban' => json_encode($selected), 'nilai' => null, 'correct' => $shouldBeCorrect, 'ragu' => $isRagu];
        }

        if ($tipe === Question::TIPE_JODOH) {
            $matches = $question->matches()->orderBy('urutan')->get();
            if ($matches->isEmpty()) return ['jawaban' => '{}', 'nilai' => null, 'correct' => null, 'ragu' => $isRagu];

            $ids = $matches->pluck('id')->all();
            if ($shouldBeCorrect) {
                // Benar: setiap premis dipasangkan ke match ID dirinya sendiri
                // (value pada <select> adalah $opt->id dari QuestionMatch yang SAMA)
                $map = array_combine($ids, $ids);
            } else {
                // Salah: geser/rotate supaya pasangannya tidak tepat
                $rotated = count($ids) > 1
                    ? array_merge(array_slice($ids, 1), array_slice($ids, 0, 1))
                    : $ids;
                $map = array_combine($ids, $rotated);
            }
            return ['jawaban' => json_encode($map), 'nilai' => null, 'correct' => $shouldBeCorrect, 'ragu' => $isRagu];
        }

        if ($tipe === Question::TIPE_ISIAN) {
            $keywords = $question->keywords()->get();
            if ($keywords->isEmpty()) return ['jawaban' => null, 'nilai' => null, 'correct' => null, 'ragu' => false];
            $jawaban = $shouldBeCorrect ? $keywords->first()->keyword : 'salah jawaban';
            return ['jawaban' => $jawaban, 'nilai' => $shouldBeCorrect ? (float) $question->bobot : 0.0, 'correct' => $shouldBeCorrect, 'ragu' => $isRagu];
        }

        if ($tipe === Question::TIPE_URAIAN) {
            $jawaban = $shouldBeCorrect
                ? 'Jawaban uraian yang komprehensif dan sesuai dengan pembahasan yang diharapkan.'
                : 'Jawaban singkat dan kurang lengkap.';
            return ['jawaban' => $jawaban, 'nilai' => null, 'correct' => null, 'ragu' => false];
        }

        return ['jawaban' => null, 'nilai' => null, 'correct' => null, 'ragu' => false];
    }

    /**
     * Buat beberapa attempt peserta lain untuk keramaian data.
     */
    private function buatAttemptPesertaLain(ExamSession $s1, ExamSession $s9, ExamSession $s10): void
    {
        $peserta = array_values($this->pesertaRombel);

        // S1 (aktif, masih berjalan) — PST-002 dan PST-003 sedang mengerjakan
        foreach (['PST-002', 'PST-003'] as $nomor) {
            if (isset($this->pesertaRombel[$nomor])) {
                $this->buatAttemptBerlangsung($s1, $this->pesertaRombel[$nomor], 1);
            }
        }

        // S9 (selesai) — PST-002 sampai PST-005 sudah selesai dengan nilai beragam
        $nilaiS9 = ['PST-002' => [78.0, 7, 2, 1], 'PST-003' => [55.0, 5, 3, 2], 'PST-004' => [90.0, 9, 0, 1], 'PST-005' => [70.0, 7, 2, 1]];
        foreach ($nilaiS9 as $nomor => [$nilai, $benar, $salah, $kosong]) {
            if (isset($this->pesertaRombel[$nomor])) {
                $this->buatAttemptSelesai($s9, $this->pesertaRombel[$nomor], 1, $nilai, $benar, $salah, $kosong, ExamAttempt::STATUS_SELESAI, now()->subDay()->subHours(2), now()->subDay()->subHour()->subMinutes(rand(5, 30)));
            }
        }

        // S10 (selesai, nilai tersembunyi) — PST-002 selesai dengan timeout
        if (isset($this->pesertaRombel['PST-002'])) {
            $this->buatAttemptSelesai($s10, $this->pesertaRombel['PST-002'], 1, 45.0, 4, 4, 2, ExamAttempt::STATUS_TIMEOUT, now()->subDays(3)->subHour(), now()->subDays(3)->subMinutes(1));
        }
    }

    private function printRingkasan(): void
    {
        $this->command->newLine();
        $this->command->info('╔══════════════════════════════════════════════════════════╗');
        $this->command->info('║         RINGKASAN SKENARIO UJI COBA                     ║');
        $this->command->info('╠══════════════════════════════════════════════════════════╣');
        $this->command->info('║  Login Andi: andi@cabt.local / peserta123                ║');
        $this->command->info('╠══════════════════════════════════════════════════════════╣');
        $this->command->line('  S1  [AKTIF]  Matematika — X IPA 1                  : Andi BELUM');
        $this->command->line('  S2  [AKTIF]  TIK + token TIK2026                   : Andi BELUM');
        $this->command->line('  S3  [AKTIF]  B.Indo max=2×                         : Andi SELESAI 1× → bisa REMIDI (sisa 1)');
        $this->command->line('  S4  [AKTIF]  Sejarah max=1× (no remidi)            : Andi SELESAI 1× → TIDAK bisa remidi');
        $this->command->line('  S5  [AKTIF]  Tryout max=3×                         : Andi SELESAI 2× → bisa REMIDI (sisa 1)');
        $this->command->line('  S6  [AKTIF]  IPA + token IPA2026                   : Andi SEDANG BERLANGSUNG (lanjutkan)');
        $this->command->line('  S7  [AKTIF]  Matematika unlimited                  : Andi BELUM');
        $this->command->line('  S8  [DRAFT]  IPA — X IPS 1                         : Andi terdaftar, belum bisa mulai');
        $this->command->line('  S9  [SELESAI] Sejarah — kemarin (hasil tampil)     : Andi SELESAI dengan nilai 85');
        $this->command->line('  S10 [SELESAI] B.Indo — 3 hari lalu (nilai hidden)  : Andi SELESAI, tidak lihat nilai');
        $this->command->line('  S11 [DIBATALKAN] TIK — dibatalkan                  : tidak bisa diakses');
        $this->command->info('╚══════════════════════════════════════════════════════════╝');
    }
}
