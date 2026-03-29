<?php

namespace App\Services;

use App\Models\AttemptQuestion;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\Question;
use Illuminate\Support\Facades\DB;

/**
 * ScoringService
 *
 * Menghitung nilai_perolehan per soal dan nilai_akhir per attempt.
 *
 * Aturan penskoran:
 *  PG       → bobot soal jika jawaban benar, 0 jika salah/kosong
 *  PG_BOBOT → bobot_soal × (bobot_persen / 100) sesuai opsi yang dipilih
 *  PGJ      → bobot_soal × max(0, (benar_dipilih − salah_dipilih) / total_kunci_benar)
 *  JODOH    → bobot_soal × (pasangan_benar / total_pasangan)
 *  ISIAN    → bobot soal jika keyword cocok (case-insensitive/trim), 0 jika tidak
 *  URAIAN   → null / null (tunggu penilaian manual)
 *
 * nilai_akhir = (Σ nilai_perolehan / Σ bobot_max) × 100
 *
 * Jika ada soal URAIAN yang belum dinilai, nilai_akhir tetap null.
 */
class ScoringService
{
    // ─────────────────────────────────────────────────────────────────────────
    // PUBLIC API
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Nilai sebuah attempt untuk pertama kali (dipanggil saat finalize).
     * URAIAN dilewati — hanya ditangani via regrade() setelah penilaian manual.
     */
    public function grade(int|ExamAttempt $attempt): ExamAttempt
    {
        $attempt = $this->resolveAttempt($attempt);

        return DB::transaction(function () use ($attempt) {
            return $this->calculate($attempt, skipAlreadyScored: false);
        });
    }

    /**
     * Hitung ulang seluruh nilai attempt (dipanggil setelah admin menilai URAIAN).
     * Soal URAIAN yang sudah punya nilai_perolehan (tidak null) akan ikut dihitung.
     */
    public function regrade(int|ExamAttempt $attempt): ExamAttempt
    {
        $attempt = $this->resolveAttempt($attempt);

        return DB::transaction(function () use ($attempt) {
            return $this->calculate($attempt, skipAlreadyScored: false);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // INTERNAL CALCULATION
    // ─────────────────────────────────────────────────────────────────────────

    private function calculate(ExamAttempt $attempt, bool $skipAlreadyScored): ExamAttempt
    {
        $attemptQuestions = $attempt->questions()
            ->with(['question.options', 'question.matches', 'question.keywords'])
            ->get();

        $sumNilai         = 0.0;
        $sumBobot         = 0.0;
        $jumlahBenar      = 0;
        $jumlahSalah      = 0;
        $jumlahKosong     = 0;
        $hasUnscoredUraian = false;

        foreach ($attemptQuestions as $aq) {
            $question = $aq->question;
            $bobot    = (float) $question->bobot;
            $sumBobot += $bobot;

            // ── URAIAN: tidak dihitung otomatis ──────────────────────────────
            if ($question->tipe === Question::TIPE_URAIAN) {
                if ($aq->nilai_perolehan !== null) {
                    $sumNilai += (float) $aq->nilai_perolehan;
                } else {
                    $hasUnscoredUraian = true;
                }
                continue;
            }

            // ── Soal kosong ───────────────────────────────────────────────────
            if ($aq->jawaban_peserta === null || $aq->jawaban_peserta === '') {
                $aq->update(['nilai_perolehan' => 0.0, 'is_correct' => false]);
                $jumlahKosong++;
                continue;
            }

            // ── Hitung nilai per tipe ─────────────────────────────────────────
            $nilai     = $this->scoreQuestion($aq, $question);
            $isCorrect = $this->determineIsCorrect($question->tipe, $nilai, $bobot);

            $aq->update([
                'nilai_perolehan' => round($nilai, 2),
                'is_correct'      => $isCorrect,
            ]);

            $sumNilai += $nilai;

            if ($isCorrect) {
                $jumlahBenar++;
            } else {
                $jumlahSalah++;
            }
        }

        // ── Update attempt ────────────────────────────────────────────────────
        $nilaiAkhir = ($sumBobot > 0)
            ? round(($sumNilai / $sumBobot) * 100, 2)
            : 0.0;

        $attempt->update([
            'nilai_akhir'   => $hasUnscoredUraian ? null : $nilaiAkhir,
            'jumlah_benar'  => $jumlahBenar,
            'jumlah_salah'  => $jumlahSalah,
            'jumlah_kosong' => $jumlahKosong,
        ]);

        return $attempt->fresh();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PER-TYPE SCORING
    // ─────────────────────────────────────────────────────────────────────────

    private function scoreQuestion(AttemptQuestion $aq, Question $question): float
    {
        return match ($question->tipe) {
            Question::TIPE_PG       => $this->scorePg($aq, $question),
            Question::TIPE_PG_BOBOT => $this->scorePgBobot($aq, $question),
            Question::TIPE_PGJ      => $this->scorePgj($aq, $question),
            Question::TIPE_JODOH    => $this->scoreJodoh($aq, $question),
            Question::TIPE_ISIAN    => $this->scoreIsian($aq, $question),
            default                 => 0.0,
        };
    }

    /**
     * PG — satu opsi benar, jawaban berupa ID option.
     */
    private function scorePg(AttemptQuestion $aq, Question $question): float
    {
        $selectedId = (int) $aq->jawaban_peserta;
        $isCorrect  = $question->options
            ->where('id', $selectedId)
            ->where('is_correct', true)
            ->isNotEmpty();

        return $isCorrect ? (float) $question->bobot : 0.0;
    }

    /**
     * PG_BOBOT — nilai proporsional sesuai bobot_persen opsi yang dipilih.
     */
    private function scorePgBobot(AttemptQuestion $aq, Question $question): float
    {
        $selectedId = (int) $aq->jawaban_peserta;
        $option     = $question->options->firstWhere('id', $selectedId);

        if (! $option) {
            return 0.0;
        }

        return (float) $question->bobot * ((float) $option->bobot_persen / 100);
    }

    /**
     * PGJ — jawaban berupa JSON array of option IDs.
     * Penskoran: bobot × max(0, (benar − salah) / total_kunci_benar)
     */
    private function scorePgj(AttemptQuestion $aq, Question $question): float
    {
        $selectedIds = array_map('intval', json_decode($aq->jawaban_peserta, true) ?? []);
        $correctIds  = $question->options->where('is_correct', true)->pluck('id')->all();

        if (empty($correctIds)) {
            return 0.0;
        }

        $benarDipilih = count(array_intersect($selectedIds, $correctIds));
        $salahDipilih = count(array_diff($selectedIds, $correctIds));

        $ratio = ($benarDipilih - $salahDipilih) / count($correctIds);

        return (float) $question->bobot * max(0.0, $ratio);
    }

    /**
     * JODOH — jawaban berupa JSON object {matchId: matchId}.
     * Nilai benar jika key === value (setiap premis dipasangkan ke respon dirinya sendiri).
     * Formula: bobot × (pasangan_benar / total_pasangan)
     */
    private function scoreJodoh(AttemptQuestion $aq, Question $question): float
    {
        $map           = json_decode($aq->jawaban_peserta, true) ?? [];
        $totalPasangan = $question->matches->count();

        if ($totalPasangan === 0) {
            return 0.0;
        }

        $pasanganBenar = 0;
        foreach ($map as $matchId => $selectedMatchId) {
            if ((string) $matchId === (string) $selectedMatchId) {
                $pasanganBenar++;
            }
        }

        return (float) $question->bobot * ($pasanganBenar / $totalPasangan);
    }

    /**
     * ISIAN — cocok dengan salah satu keyword (case-insensitive, trim).
     */
    private function scoreIsian(AttemptQuestion $aq, Question $question): float
    {
        $jawaban  = mb_strtolower(trim($aq->jawaban_peserta ?? ''));
        $keywords = $question->keywords
            ->pluck('keyword')
            ->map(fn ($k) => mb_strtolower(trim($k)));

        return $keywords->contains($jawaban) ? (float) $question->bobot : 0.0;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Tentukan apakah jawaban dianggap "benar" untuk flag is_correct.
     * PG_BOBOT dan JODOH dianggap benar hanya jika skor === full bobot.
     */
    private function determineIsCorrect(string $tipe, float $nilai, float $bobot): bool
    {
        if ($bobot <= 0) {
            return false;
        }

        return match ($tipe) {
            // PG_BOBOT: "benar" hanya jika dapat semua poin (100%)
            Question::TIPE_PG_BOBOT => abs($nilai - $bobot) < 0.001,
            // PGJ, JODOH: "benar" jika dapat nilai > 0 (partial credit tetap benar)
            Question::TIPE_PGJ,
            Question::TIPE_JODOH    => $nilai > 0,
            // PG, ISIAN: full or nothing
            default                 => abs($nilai - $bobot) < 0.001,
        };
    }

    private function resolveAttempt(int|ExamAttempt $attempt): ExamAttempt
    {
        return $attempt instanceof ExamAttempt
            ? $attempt
            : ExamAttempt::findOrFail($attempt);
    }
}
