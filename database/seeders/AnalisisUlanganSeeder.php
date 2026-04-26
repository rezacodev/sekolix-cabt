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
use App\Services\ScoringService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * AnalisisUlanganSeeder — Data Khusus Fitur Analisis Ulangan Harian
 *
 * Membuat satu sesi yang dirancang khusus untuk menguji tampilan laporan
 * analisis ulangan harian:
 *
 *   SESI: "Demo Analisis Ulangan — VII A TIK" (STATUS_SELESAI)
 *   Paket : TIK (10 soal essay / PG)
 *   KKM   : 70
 *   Peserta: 10 orang dengan distribusi nilai yang beragam:
 *
 *   Distribusi nilai:
 *   ┌─────────────────────────────────────────────────────────────────┐
 *   │ < 70  (tidak tuntas) : PST-001, PST-002, PST-003, PST-004       │
 *   │ 70-83 (tuntas rendah): PST-005, PST-006                         │
 *   │ 84-92 (tuntas sedang): PST-007, PST-008                         │
 *   │ 93-100 (tuntas tinggi): PST-009, PST-010                        │
 *   └─────────────────────────────────────────────────────────────────┘
 *
 *   Skenario remidi:
 *   - PST-001 : attempt 1 → 40, attempt 2 (remidi) → 70 (tuntas setelah perbaikan)
 *   - PST-002 : attempt 1 → 20 (tidak remidi)
 *
 *   Tujuan: memastikan semua 5 halaman laporan terisi dengan data bermakna.
 *
 * Idempotent: dikenali dari nama_sesi unik.
 */
class AnalisisUlanganSeeder extends Seeder
{
    private ScoringService $scoring;
    private User $guru;
    private array $peserta = [];
    private ExamSession $sesiAnalisis;

    // Skenario nilai peserta: [nomor_peserta => [nilai_attempt1, nilai_attempt2|null]]
    private array $skenario = [
        'PST-001' => [40.0,  70.0],  // tidak tuntas → remidi → tuntas
        'PST-002' => [20.0,  null],  // tidak tuntas, tidak remidi
        'PST-003' => [50.0,  null],  // tidak tuntas
        'PST-004' => [60.0,  null],  // tidak tuntas
        'PST-005' => [70.0,  null],  // tuntas (pas KKM)
        'PST-006' => [78.0,  null],  // tuntas rendah
        'PST-007' => [85.0,  null],  // tuntas sedang
        'PST-008' => [90.0,  null],  // tuntas sedang
        'PST-009' => [95.0,  null],  // tuntas tinggi
        'PST-010' => [100.0, null],  // tuntas sempurna
    ];

    public function run(): void
    {
        $this->scoring = app(ScoringService::class);

        // ── Bootstrap ─────────────────────────────────────────────────────────
        $this->guru = User::where('level', '>=', User::LEVEL_GURU)->first()
            ?? throw new \RuntimeException('Tidak ada guru/admin. Jalankan UserSeeder terlebih dahulu.');

        $this->peserta = User::where('level', User::LEVEL_PESERTA)
            ->whereIn('nomor_peserta', array_keys($this->skenario))
            ->get()
            ->keyBy('nomor_peserta')
            ->all();

        if (empty($this->peserta)) {
            $this->command->warn('Peserta PST-001..010 tidak ditemukan. Jalankan UserSeeder terlebih dahulu.');
            return;
        }

        $paket = ExamPackage::where('nama', 'like', 'TIK%')->first();
        if (! $paket) {
            $this->command->warn('Paket TIK tidak ditemukan. Jalankan ExamPackageSeeder terlebih dahulu.');
            return;
        }

        // ── Buat sesi analisis ────────────────────────────────────────────────
        $this->command->info('Membuat sesi analisis ulangan...');
        $this->sesiAnalisis = ExamSession::firstOrCreate(
            ['nama_sesi' => 'Demo Analisis Ulangan — VII A TIK'],
            [
                'exam_package_id' => $paket->id,
                'waktu_mulai'     => now()->subDays(7)->setTime(8, 0),
                'waktu_selesai'   => now()->subDays(7)->setTime(10, 0),
                'status'          => ExamSession::STATUS_SELESAI,
                'token_akses'     => null,
                'created_by'      => $this->guru->id,
                'kkm'             => 70,
                'kkm_klasikal'    => 65,
            ]
        );

        $isNew = $this->sesiAnalisis->wasRecentlyCreated;
        $this->command->line('  ' . ($isNew ? '<info>✓</info>' : '<comment>–</comment>') . ' [SELESAI][kkm=70] Demo Analisis Ulangan — VII A TIK');

        // ── Daftarkan semua peserta ───────────────────────────────────────────
        $this->command->info('Mendaftarkan peserta ke sesi analisis...');
        foreach ($this->peserta as $p) {
            ExamSessionParticipant::firstOrCreate(
                ['exam_session_id' => $this->sesiAnalisis->id, 'user_id' => $p->id],
                ['status' => ExamSessionParticipant::STATUS_BELUM]
            );
        }

        // ── Buat attempt untuk setiap peserta ────────────────────────────────
        $this->command->info('Membuat attempt peserta...');
        $questions = $this->sesiAnalisis->package->questions()
            ->with('options', 'matches', 'keywords')
            ->orderByPivot('urutan')
            ->get();

        if ($questions->isEmpty()) {
            $this->command->warn('Paket TIK tidak memiliki soal. Tambahkan soal ke paket terlebih dahulu.');
            return;
        }

        foreach ($this->skenario as $nomorPeserta => [$nilai1, $nilai2]) {
            if (! isset($this->peserta[$nomorPeserta])) {
                $this->command->warn("  Peserta {$nomorPeserta} tidak ditemukan, skip.");
                continue;
            }

            $user   = $this->peserta[$nomorPeserta];
            $waktu1 = now()->subDays(7)->setTime(8, 5);

            // Attempt 1
            $this->buatAttemptDenganNilai($user, 1, $nilai1, $questions, $waktu1, 45);

            // Attempt 2 (remidi) jika ada
            if ($nilai2 !== null) {
                $waktu2 = now()->subDays(5)->setTime(14, 0);
                $this->buatAttemptDenganNilai($user, 2, $nilai2, $questions, $waktu2, 45);
            }

            $this->command->line("  <info>✓</info> {$nomorPeserta} ({$user->name}): nilai1={$nilai1}" . ($nilai2 !== null ? ", nilai2={$nilai2}" : ''));
        }

        $this->printRingkasan();
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    /**
     * Buat satu attempt dengan nilai akhir yang ditentukan.
     * Distribusi benar/salah soal dihitung dari target nilai terhadap total bobot.
     */
    private function buatAttemptDenganNilai(
        User $user,
        int $attemptKe,
        float $nilaiTarget,
        \Illuminate\Database\Eloquent\Collection $questions,
        Carbon $waktuMulai,
        int $durasiMenit,
    ): void {
        $existing = ExamAttempt::where('exam_session_id', $this->sesiAnalisis->id)
            ->where('user_id', $user->id)
            ->where('attempt_ke', $attemptKe)
            ->first();

        if ($existing) {
            return;
        }

        DB::transaction(function () use ($user, $attemptKe, $nilaiTarget, $questions, $waktuMulai, $durasiMenit) {
            $waktuSelesai = (clone $waktuMulai)->addMinutes($durasiMenit);

            // Hitung berapa soal yang harus benar untuk mencapai nilai target
            $totalBobot  = $questions->sum(fn($q) => (float) $q->bobot);
            $targetBobot = ($nilaiTarget / 100) * $totalBobot;
            $bobotAkum   = 0.0;
            $benar       = 0;
            $salah       = 0;
            $kosong      = 0;

            $attempt = ExamAttempt::create([
                'exam_session_id' => $this->sesiAnalisis->id,
                'user_id'         => $user->id,
                'waktu_mulai'     => $waktuMulai,
                'waktu_selesai'   => $waktuSelesai,
                'status'          => ExamAttempt::STATUS_SELESAI,
                'nilai_akhir'     => $nilaiTarget,
                'jumlah_benar'    => 0,
                'jumlah_salah'    => 0,
                'jumlah_kosong'   => 0,
                'attempt_ke'      => $attemptKe,
            ]);

            foreach ($questions->values() as $idx => $question) {
                $bobot         = (float) $question->bobot;
                $shouldBeBenar = $bobotAkum < $targetBobot;
                $nilaiPerolehan = 0.0;
                $isCorrect      = false;

                if ($shouldBeBenar) {
                    $jawaban        = $this->jawabanBenar($question);
                    $nilaiPerolehan = $bobot;
                    $isCorrect      = true;
                    $bobotAkum     += $bobot;
                    $benar++;
                } else {
                    $jawaban = $this->jawabanSalah($question);
                    $salah++;
                }

                AttemptQuestion::create([
                    'attempt_id'      => $attempt->id,
                    'question_id'     => $question->id,
                    'urutan'          => $idx + 1,
                    'jawaban_peserta' => $jawaban,
                    'nilai_perolehan' => $nilaiPerolehan,
                    'is_correct'      => $isCorrect,
                    'is_ragu'         => false,
                    'waktu_jawab'     => (clone $waktuMulai)->addMinutes($idx + 1),
                ]);
            }

            // Update jumlah_benar/salah/kosong yang akurat
            $attempt->update([
                'jumlah_benar'  => $benar,
                'jumlah_salah'  => $salah,
                'jumlah_kosong' => $kosong,
            ]);

            // Log submit
            AttemptLog::create([
                'attempt_id' => $attempt->id,
                'event_type' => AttemptLog::EVENT_SUBMIT,
                'detail'     => 'status=selesai',
                'created_at' => $waktuSelesai,
            ]);

            // Update status partisipasi
            ExamSessionParticipant::where('exam_session_id', $this->sesiAnalisis->id)
                ->where('user_id', $user->id)
                ->update(['status' => ExamSessionParticipant::STATUS_SELESAI]);
        });
    }

    /**
     * Generate jawaban benar sesuai tipe soal.
     */
    private function jawabanBenar(Question $question): ?string
    {
        return match ($question->tipe) {
            Question::TIPE_PG, Question::TIPE_PG_BOBOT => (function () use ($question) {
                $correct = $question->options()->where('is_correct', true)->where('aktif', true)->first();
                return $correct ? (string) $correct->id : null;
            })(),
            Question::TIPE_PGJ => (function () use ($question) {
                $ids = $question->options()->where('is_correct', true)->where('aktif', true)->pluck('id')->all();
                return json_encode($ids);
            })(),
            Question::TIPE_JODOH => (function () use ($question) {
                $ids = $question->matches()->orderBy('urutan')->pluck('id')->all();
                return json_encode(array_combine($ids, $ids));
            })(),
            Question::TIPE_ISIAN => (function () use ($question) {
                return $question->keywords()->first()?->keyword ?? 'benar';
            })(),
            Question::TIPE_URAIAN => 'Jawaban uraian yang lengkap dan sesuai kunci jawaban.',
            default              => null,
        };
    }

    /**
     * Generate jawaban salah sesuai tipe soal.
     */
    private function jawabanSalah(Question $question): ?string
    {
        return match ($question->tipe) {
            Question::TIPE_PG, Question::TIPE_PG_BOBOT => (function () use ($question) {
                $wrong = $question->options()->where('is_correct', false)->where('aktif', true)->first();
                return $wrong ? (string) $wrong->id : null;
            })(),
            Question::TIPE_PGJ => (function () use ($question) {
                $ids = $question->options()->where('is_correct', false)->where('aktif', true)->pluck('id')->all();
                return json_encode(array_slice($ids, 0, 1));
            })(),
            Question::TIPE_JODOH => (function () use ($question) {
                $ids = $question->matches()->orderBy('urutan')->pluck('id')->all();
                if (count($ids) <= 1) return json_encode(array_combine($ids, $ids));
                $rotated = array_merge(array_slice($ids, 1), array_slice($ids, 0, 1));
                return json_encode(array_combine($ids, $rotated));
            })(),
            Question::TIPE_ISIAN  => 'jawaban salah',
            Question::TIPE_URAIAN => 'Jawaban uraian yang kurang tepat dan tidak lengkap.',
            default               => null,
        };
    }

    private function printRingkasan(): void
    {
        $sesiId       = $this->sesiAnalisis->id;
        $totalAttempt = ExamAttempt::where('exam_session_id', $sesiId)->count();
        $tuntas        = ExamAttempt::where('exam_session_id', $sesiId)
            ->where('nilai_akhir', '>=', 70)
            ->whereIn('status', [ExamAttempt::STATUS_SELESAI, ExamAttempt::STATUS_TIMEOUT])
            ->distinct('user_id')->count('user_id');

        $this->command->newLine();
        $this->command->info('╔══════════════════════════════════════════════════════════╗');
        $this->command->info('║       RINGKASAN ANALISIS ULANGAN SEEDER                 ║');
        $this->command->info('╠══════════════════════════════════════════════════════════╣');
        $this->command->line("  Sesi ID    : {$sesiId}");
        $this->command->line("  Nama Sesi  : Demo Analisis Ulangan — VII A TIK");
        $this->command->line("  KKM        : 70");
        $this->command->line("  Total Attempt : {$totalAttempt}");
        $this->command->info('╠══════════════════════════════════════════════════════════╣');
        $this->command->line('  PST-001  : nilai 40  → remidi → 70  (tuntas setelah perbaikan)');
        $this->command->line('  PST-002  : nilai 20  (tidak tuntas)');
        $this->command->line('  PST-003  : nilai 50  (tidak tuntas)');
        $this->command->line('  PST-004  : nilai 60  (tidak tuntas)');
        $this->command->line('  PST-005  : nilai 70  (tuntas pas KKM)');
        $this->command->line('  PST-006  : nilai 78  (tuntas rendah)');
        $this->command->line('  PST-007  : nilai 85  (tuntas sedang)');
        $this->command->line('  PST-008  : nilai 90  (tuntas sedang)');
        $this->command->line('  PST-009  : nilai 95  (tuntas tinggi)');
        $this->command->line('  PST-010  : nilai 100 (tuntas sempurna)');
        $this->command->info('╠══════════════════════════════════════════════════════════╣');
        $this->command->line("  URL Analisis : /cabt/analisis/{$sesiId}");
        $this->command->line("  URL PDF      : /cabt/analisis/{$sesiId}/pdf");
        $this->command->line("  URL Excel    : /cabt/analisis/{$sesiId}/export");
        $this->command->info('╚══════════════════════════════════════════════════════════╝');
    }
}
