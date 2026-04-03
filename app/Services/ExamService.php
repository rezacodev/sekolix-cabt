<?php

namespace App\Services;

use App\Models\AttemptLog;
use App\Models\AttemptQuestion;
use App\Models\AttemptSectionStart;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSection;
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

            if ($package->has_sections) {
                // Multi-section: ambil soal per seksi
                $sections = $package->sections()
                    ->with('questions.options', 'questions.matches', 'questions.keywords')
                    ->get();
                $urutan = 1;
                foreach ($sections as $section) {
                    $sectionQuestions = $section->questions()->withPivot('urutan')->get()->sortBy('pivot.urutan');
                    if ($section->acak_soal) {
                        $sectionQuestions = $this->shuffle->shuffleQuestions($sectionQuestions->values());
                    }
                    foreach ($sectionQuestions->values() as $q) {
                        AttemptQuestion::create([
                            'attempt_id'  => $attempt->id,
                            'question_id' => $q->id,
                            'section_id'  => $section->id,
                            'urutan'      => $urutan++,
                        ]);
                    }
                }
                // Catat mulai seksi pertama
                if ($sections->isNotEmpty()) {
                    AttemptSectionStart::create([
                        'attempt_id'  => $attempt->id,
                        'section_id'  => $sections->first()->id,
                        'waktu_mulai' => now(),
                    ]);
                }
            } else {
                // Single-section (default): ambil soal dari paket langsung
                $questions = $package->questions()->with('options', 'matches', 'keywords')->get();
                if ($package->acak_soal) {
                    $questions = $this->shuffle->shuffleQuestions($questions);
                }
                foreach ($questions->values() as $urutan => $question) {
                    AttemptQuestion::create([
                        'attempt_id'  => $attempt->id,
                        'question_id' => $question->id,
                        'urutan'      => $urutan + 1,
                    ]);
                }
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

        $result = ['sisa_detik' => $sisa, 'status' => $attempt->status];

        // Tambahkan info seksi jika paket multi-seksi
        if ($attempt->session->package->has_sections) {
            $sectionStart = AttemptSectionStart::with('section')
                ->where('attempt_id', $attemptId)
                ->latest('waktu_mulai')
                ->first();
            if ($sectionStart) {
                $result['sisa_seksi_detik'] = $sectionStart->sisaWaktuDetik();
                $result['section_id']       = $sectionStart->section_id;
            }
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SELESAIKAN / PINDAH SEKSI
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Pindah ke seksi lain (atau finish jika seksi terakhir).
     *
     * @param int       $attemptId     ID attempt aktif
     * @param int       $sectionId     Seksi saat ini (harus seksi aktif)
     * @param int|null  $targetSectionId  Seksi tujuan. null = next. Hanya boleh diisi
     *                                     untuk mode urut_kembali / bebas.
     * @throws ValidationException
     */
    public function selesaikanSeksi(int $attemptId, int $sectionId, ?int $targetSectionId = null): array
    {
        $attempt = ExamAttempt::with('session.package.sections')->findOrFail($attemptId);

        if (! $attempt->isBerlangsung()) {
            throw ValidationException::withMessages(['attempt' => 'Attempt sudah selesai.']);
        }

        $package = $attempt->session->package;
        $nav     = $package->navigasi_seksi ?? ExamPackage::NAV_SEKSI_URUT;
        $sections = $package->sections;

        // Seksi aktif saat ini (latest start)
        $currentStart = AttemptSectionStart::where('attempt_id', $attemptId)
            ->latest('waktu_mulai')
            ->first();

        if (! $currentStart || $currentStart->section_id !== $sectionId) {
            throw ValidationException::withMessages(['section' => 'Seksi tidak valid atau sudah selesai.']);
        }

        $current = $sections->firstWhere('id', $sectionId);
        if (! $current) {
            throw ValidationException::withMessages(['section' => 'Seksi tidak ditemukan.']);
        }

        // Tentukan seksi tujuan
        $target = null;

        if ($targetSectionId !== null) {
            // Validasi apakah boleh lompat/kembali
            if ($nav === ExamPackage::NAV_SEKSI_URUT) {
                throw ValidationException::withMessages(['section' => 'Mode navigasi tidak mengizinkan pindah bebas antar bagian.']);
            }

            $target = $sections->firstWhere('id', $targetSectionId);
            if (! $target) {
                throw ValidationException::withMessages(['section' => 'Bagian tujuan tidak ditemukan.']);
            }

            // Mode urut_kembali: hanya boleh ke seksi dengan urutan lebih kecil (kembali) atau seksi berikutnya
            if ($nav === ExamPackage::NAV_SEKSI_URUT_KEMBALI) {
                $alreadyVisited = AttemptSectionStart::where('attempt_id', $attemptId)
                    ->pluck('section_id')
                    ->contains($targetSectionId);
                $isNext = $target->urutan === $current->urutan + 1;

                if (! $alreadyVisited && ! $isNext) {
                    throw ValidationException::withMessages(['section' => 'Hanya bisa kembali ke bagian sebelumnya atau lanjut ke bagian berikutnya.']);
                }
            }
        } else {
            // Target null → maju ke seksi berikutnya
            $target = $sections->where('urutan', '>', $current->urutan)->sortBy('urutan')->first();
        }

        return DB::transaction(function () use ($attempt, $target) {
            if ($target) {
                // Cek apakah seksi ini pernah dimulai (kembali) — jika ya, skip create baru
                $alreadyStarted = AttemptSectionStart::where('attempt_id', $attempt->id)
                    ->where('section_id', $target->id)
                    ->exists();

                if (! $alreadyStarted) {
                    AttemptSectionStart::create([
                        'attempt_id'  => $attempt->id,
                        'section_id'  => $target->id,
                        'waktu_mulai' => now(),
                    ]);
                } else {
                    // Update waktu_mulai untuk reset timer saat kembali
                    AttemptSectionStart::where('attempt_id', $attempt->id)
                        ->where('section_id', $target->id)
                        ->update(['waktu_mulai' => now()]);
                }

                return [
                    'is_last'         => false,
                    'next_section_id' => $target->id,
                    'next_section'    => [
                        'nama'         => $target->nama,
                        'durasi_menit' => $target->durasi_menit,
                    ],
                ];
            }

            // Tidak ada seksi berikutnya — finalisasi attempt
            $this->finalize($attempt, ExamAttempt::STATUS_SELESAI, AttemptLog::EVENT_SUBMIT);

            return ['is_last' => true, 'next_section_id' => null, 'next_section' => null];
        });
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
            $dijawab   = $questions->filter(fn($aq) => $aq->isDijawab())->count();

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
