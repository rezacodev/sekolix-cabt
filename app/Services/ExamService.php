<?php

namespace App\Services;

use App\Models\AttemptLog;
use App\Models\AttemptQuestion;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Models\Question;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamService
{
    public function __construct(
        private readonly ShuffleService $shuffle,
        private readonly ScoringService $scoring,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // MULAI
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Mulai atau lanjutkan attempt.
     *
     * @throws ValidationException
     */
    public function mulai(int $sesiId, int $userId): ExamAttempt
    {
        $session = ExamSession::with('package.questions.options', 'package.questions.matches', 'package.questions.keywords')
            ->findOrFail($sesiId);

        // 1. Pastikan sesi aktif
        if (! $session->isAktif()) {
            throw ValidationException::withMessages(['sesi' => 'Sesi ujian tidak aktif.']);
        }

        // 2. Pastikan peserta terdaftar
        $participant = ExamSessionParticipant::where('exam_session_id', $sesiId)
            ->where('user_id', $userId)
            ->first();

        if (! $participant) {
            throw ValidationException::withMessages(['peserta' => 'Anda tidak terdaftar dalam sesi ini.']);
        }

        if ($participant->status === ExamSessionParticipant::STATUS_DISKUALIFIKASI) {
            throw ValidationException::withMessages(['peserta' => 'Anda telah didiskualifikasi dari sesi ini.']);
        }

        // 3. Cek attempt aktif (lanjutkan jika ada)
        $existing = ExamAttempt::where('exam_session_id', $sesiId)
            ->where('user_id', $userId)
            ->where('status', ExamAttempt::STATUS_BERLANGSUNG)
            ->first();

        if ($existing) {
            // Cek apakah waktu sudah habis
            if ($existing->sisaWaktuDetik() <= 0) {
                $this->autoSubmit($existing->id, AttemptLog::EVENT_TIMEOUT);
                throw ValidationException::withMessages(['waktu' => 'Waktu ujian telah habis.']);
            }
            return $existing;
        }

        // 4. Cek batas pengulangan
        $package       = $session->package;
        $attemptCount  = ExamAttempt::where('exam_session_id', $sesiId)
            ->where('user_id', $userId)
            ->count();

        if ($package->max_pengulangan > 0 && $attemptCount >= $package->max_pengulangan) {
            throw ValidationException::withMessages(['attempt' => 'Batas pengulangan ujian telah tercapai.']);
        }

        // 5. Buat attempt baru dalam transaksi
        return DB::transaction(function () use ($session, $userId, $package, $attemptCount) {
            $attempt = ExamAttempt::create([
                'exam_session_id' => $session->id,
                'user_id'         => $userId,
                'waktu_mulai'     => now(),
                'status'          => ExamAttempt::STATUS_BERLANGSUNG,
                'attempt_ke'      => $attemptCount + 1,
            ]);

            // Ambil & shuffle soal
            $questions = $package->questions()->with('options', 'matches', 'keywords')->get();
            if ($package->acak_soal) {
                $questions = $this->shuffle->shuffleQuestions($questions);
            }

            // Simpan urutan soal ke attempt_questions
            foreach ($questions->values() as $urutan => $question) {
                AttemptQuestion::create([
                    'attempt_id'  => $attempt->id,
                    'question_id' => $question->id,
                    'urutan'      => $urutan + 1,
                ]);
            }

            // Update status peserta → sedang
            ExamSessionParticipant::where('exam_session_id', $session->id)
                ->where('user_id', $userId)
                ->update(['status' => ExamSessionParticipant::STATUS_SEDANG]);

            return $attempt;
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SIMPAN JAWABAN
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Simpan atau update jawaban satu soal.
     *
     * @throws ValidationException
     */
    public function simpanJawaban(int $attemptId, int $questionId, mixed $jawaban, bool $isRagu): AttemptQuestion
    {
        $attempt = ExamAttempt::findOrFail($attemptId);

        if (! $attempt->isBerlangsung()) {
            throw ValidationException::withMessages(['attempt' => 'Attempt sudah selesai.']);
        }

        // Cek timeout
        if ($attempt->sisaWaktuDetik() <= 0) {
            $this->autoSubmit($attemptId, AttemptLog::EVENT_TIMEOUT);
            throw ValidationException::withMessages(['waktu' => 'Waktu ujian telah habis.']);
        }

        $aq = AttemptQuestion::where('attempt_id', $attemptId)
            ->where('question_id', $questionId)
            ->firstOrFail();

        // Normalise jawaban: array → JSON string
        $jawabanStr = is_array($jawaban)
            ? json_encode($jawaban, JSON_UNESCAPED_UNICODE)
            : ($jawaban === '' ? null : (string) $jawaban);

        $aq->update([
            'jawaban_peserta' => $jawabanStr,
            'is_ragu'         => $isRagu,
            'waktu_jawab'     => now(),
        ]);

        return $aq;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SUBMIT
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Submit ujian secara manual. Idempotent — jika sudah selesai, return attempt apa adanya.
     */
    public function submit(int $attemptId): ExamAttempt
    {
        $attempt = ExamAttempt::findOrFail($attemptId);

        if ($attempt->isSelesai()) {
            return $attempt;
        }

        return $this->finalize($attempt, ExamAttempt::STATUS_SELESAI, AttemptLog::EVENT_SUBMIT);
    }

    /**
     * Auto-submit karena timeout atau tab switch berlebih.
     */
    public function autoSubmit(int $attemptId, string $reason): ExamAttempt
    {
        $attempt = ExamAttempt::findOrFail($attemptId);

        if ($attempt->isSelesai()) {
            return $attempt;
        }

        $status = $reason === AttemptLog::EVENT_TIMEOUT
            ? ExamAttempt::STATUS_TIMEOUT
            : ExamAttempt::STATUS_DISKUALIFIKASI;

        return $this->finalize($attempt, $status, $reason);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // VALIDASI WAKTU
    // ─────────────────────────────────────────────────────────────────────────

    public function validasiWaktu(int $attemptId): array
    {
        $attempt = ExamAttempt::with('session.package')->findOrFail($attemptId);

        if ($attempt->isSelesai()) {
            return ['sisa_detik' => 0, 'status' => $attempt->status];
        }

        $sisa = $attempt->sisaWaktuDetik();

        if ($sisa <= 0) {
            $this->autoSubmit($attemptId, AttemptLog::EVENT_TIMEOUT);
            return ['sisa_detik' => 0, 'status' => ExamAttempt::STATUS_TIMEOUT];
        }

        return ['sisa_detik' => $sisa, 'status' => $attempt->status];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CATAT LOG
    // ─────────────────────────────────────────────────────────────────────────

    public function catatLog(int $attemptId, string $eventType, ?string $detail = null): void
    {
        AttemptLog::create([
            'attempt_id' => $attemptId,
            'event_type' => $eventType,
            'detail'     => $detail,
            'created_at' => now(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function finalize(ExamAttempt $attempt, string $status, string $logEvent): ExamAttempt
    {
        return DB::transaction(function () use ($attempt, $status, $logEvent) {
            $questions = $attempt->questions()->with('question')->get();
            $total     = $questions->count();
            $dijawab   = $questions->filter(fn ($aq) => $aq->isDijawab())->count();

            $attempt->update([
                'status'         => $status,
                'waktu_selesai'  => now(),
                'jumlah_kosong'  => $total - $dijawab,
            ]);

            // Catat log submit/timeout/kick
            AttemptLog::create([
                'attempt_id' => $attempt->id,
                'event_type' => $logEvent,
                'detail'     => "status={$status}",
                'created_at' => now(),
            ]);

            // Update status peserta
            $participantStatus = in_array($status, [ExamAttempt::STATUS_DISKUALIFIKASI])
                ? ExamSessionParticipant::STATUS_DISKUALIFIKASI
                : ExamSessionParticipant::STATUS_SELESAI;

            ExamSessionParticipant::where('exam_session_id', $attempt->exam_session_id)
                ->where('user_id', $attempt->user_id)
                ->update(['status' => $participantStatus]);

            // Hitung nilai jika grading_mode = realtime
            $attempt->refresh();
            $gradingMode = $attempt->session->package->grading_mode ?? ExamPackage::GRADING_REALTIME;
            if ($gradingMode === ExamPackage::GRADING_REALTIME) {
                $this->scoring->grade($attempt);
            }

            return $attempt->fresh();
        });
    }
}
