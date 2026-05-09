<?php

namespace App\Services;

use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\QuestionStatistic;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ItemAnalysisService
 *
 * Menghitung statistik butir soal untuk keperluan riset:
 *  - P-value (difficulty index): AVG(is_correct) dari semua attempt yang selesai
 *  - Discrimination Index (Point-Biserial): korelasi jawaban benar vs nilai_akhir attempt
 *  - Distractor Distribution: proporsi pemilihan setiap opsi (PG, PG_BOBOT, BS)
 *  - Avg Response Seconds: rata-rata selisih waktu_jawab - waktu_mulai attempt
 *
 * Statistik bersifat kumulatif lintas semua sesi — bukan per-sesi.
 * Filter sesi hanya digunakan untuk menentukan soal mana yang relevan.
 */
class ItemAnalysisService
{
    // ── Public API ────────────────────────────────────────────────────────────

    public function calculateForQuestion(int $questionId): QuestionStatistic
    {
        $question = Question::with('options')->findOrFail($questionId);

        $rows = DB::table('attempt_questions as aq')
            ->join('exam_attempts as ea', 'aq.attempt_id', '=', 'ea.id')
            ->where('aq.question_id', $questionId)
            ->whereIn('ea.status', [
                ExamAttempt::STATUS_SELESAI,
                ExamAttempt::STATUS_TIMEOUT,
                ExamAttempt::STATUS_DISKUALIFIKASI,
            ])
            ->whereNotNull('aq.is_correct')
            ->select(
                'aq.is_correct',
                'aq.jawaban_peserta',
                'aq.waktu_jawab',
                'ea.nilai_akhir',
                'ea.waktu_mulai',
            )
            ->get();

        $totalAttempts = $rows->count();

        if ($totalAttempts === 0) {
            return QuestionStatistic::updateOrCreate(
                ['question_id' => $questionId],
                [
                    'total_attempts'          => 0,
                    'p_value'                 => null,
                    'discrimination_index'    => null,
                    'distractor_distribution' => null,
                    'avg_response_seconds'    => null,
                    'last_calculated_at'      => now(),
                ]
            );
        }

        return QuestionStatistic::updateOrCreate(
            ['question_id' => $questionId],
            [
                'total_attempts'          => $totalAttempts,
                'p_value'                 => $this->calculatePValue($rows),
                'discrimination_index'    => $this->calculatePointBiserial($rows),
                'distractor_distribution' => $this->calculateDistractorDistribution($question, $rows),
                'avg_response_seconds'    => $this->calculateAvgResponseSeconds($rows),
                'last_calculated_at'      => now(),
            ]
        );
    }

    public function calculateForSession(int $sessionId): void
    {
        $questionIds = DB::table('attempt_questions as aq')
            ->join('exam_attempts as ea', 'aq.attempt_id', '=', 'ea.id')
            ->where('ea.exam_session_id', $sessionId)
            ->distinct()
            ->pluck('aq.question_id');

        foreach ($questionIds as $qid) {
            $this->calculateForQuestion((int) $qid);
        }
    }

    public function calculateForPackage(int $packageId): void
    {
        $questionIds = DB::table('exam_package_questions')
            ->where('exam_package_id', $packageId)
            ->pluck('question_id');

        foreach ($questionIds as $qid) {
            $this->calculateForQuestion((int) $qid);
        }
    }

    // ── Kalkulasi P-value ─────────────────────────────────────────────────────

    private function calculatePValue(Collection $rows): float
    {
        $correct = $rows->where('is_correct', true)->count();
        return round($correct / $rows->count(), 3);
    }

    // ── Kalkulasi Discrimination Index (Point-Biserial) ───────────────────────

    private function calculatePointBiserial(Collection $rows): ?float
    {
        // Butuh nilai_akhir attempt untuk korelasi
        $withScores = $rows->filter(fn($r) => $r->nilai_akhir !== null);
        $n          = $withScores->count();

        if ($n < 5) {
            return null; // data tidak cukup untuk kalkulasi yang reliable
        }

        $allScores     = $withScores->pluck('nilai_akhir')->map(fn($v) => (float) $v);
        $correctScores = $withScores->where('is_correct', true)->pluck('nilai_akhir')->map(fn($v) => (float) $v);

        $p = $correctScores->count() / $n;
        $q = 1 - $p;

        if ($p === 0.0 || $q === 0.0) {
            return 0.0;
        }

        $totalMean   = $allScores->avg();
        $totalStd    = $this->populationStdDev($allScores->all());

        if ($totalStd === 0.0) {
            return 0.0;
        }

        $correctMean   = $correctScores->avg();
        $incorrectMean = $withScores->where('is_correct', false)
            ->pluck('nilai_akhir')
            ->map(fn($v) => (float) $v)
            ->avg() ?? 0.0;

        // r_pb = ((M_benar − M_salah) / SD_total) × √(p × q)
        $rpbis = (($correctMean - $incorrectMean) / $totalStd) * sqrt($p * $q);

        return round(min(1.0, max(-1.0, $rpbis)), 3);
    }

    // ── Kalkulasi Distractor Distribution ────────────────────────────────────

    private function calculateDistractorDistribution(Question $question, Collection $rows): ?array
    {
        $tipe  = $question->tipe;
        $total = $rows->count();

        if ($total === 0) {
            return null;
        }

        // PG / PG_BOBOT: jawaban_peserta = option_id (integer)
        if (in_array($tipe, [Question::TIPE_PG, Question::TIPE_PG_BOBOT])) {
            $distribution = [];

            foreach ($question->options as $option) {
                $count = $rows->filter(
                    fn($r) => $r->jawaban_peserta !== null && (int) $r->jawaban_peserta === $option->id
                )->count();

                $distribution[$option->kode_opsi] = [
                    'count'   => $count,
                    'persen'  => round(($count / $total) * 100, 1),
                    'correct' => (bool) $option->is_correct,
                    'teks'    => str((string) ($option->teks_opsi ?? ''))->stripTags()->limit(80)->toString(),
                ];
            }

            // Peserta yang tidak menjawab (jawaban_peserta null/kosong)
            $kosong = $rows->filter(
                fn($r) => $r->jawaban_peserta === null || $r->jawaban_peserta === ''
            )->count();

            if ($kosong > 0) {
                $distribution['—'] = [
                    'count'   => $kosong,
                    'persen'  => round(($kosong / $total) * 100, 1),
                    'correct' => false,
                    'teks'    => 'Tidak menjawab',
                ];
            }

            return $distribution;
        }

        // BS: jawaban_peserta = 'B' atau 'S'
        if ($tipe === Question::TIPE_BS) {
            $correctKode = $question->options->firstWhere('is_correct', true)?->kode_opsi;
            $distribution = [];

            foreach (['B', 'S'] as $kode) {
                $count = $rows->filter(
                    fn($r) => strtoupper(trim((string) ($r->jawaban_peserta ?? ''))) === $kode
                )->count();

                $distribution[$kode] = [
                    'count'   => $count,
                    'persen'  => round(($count / $total) * 100, 1),
                    'correct' => $kode === $correctKode,
                    'teks'    => $kode === 'B' ? 'Benar' : 'Salah',
                ];
            }

            return $distribution;
        }

        // Tipe lain (URAIAN, ISIAN, JODOH, PGJ, CLOZE) tidak memiliki distractor distribution
        return null;
    }

    // ── Kalkulasi Avg Response Seconds ────────────────────────────────────────

    private function calculateAvgResponseSeconds(Collection $rows): ?float
    {
        $withTime = $rows->filter(fn($r) => $r->waktu_jawab && $r->waktu_mulai);

        if ($withTime->isEmpty()) {
            return null;
        }

        $totalSeconds = $withTime->sum(function ($r) {
            $diff = strtotime($r->waktu_jawab) - strtotime($r->waktu_mulai);
            return max(0, $diff);
        });

        return round($totalSeconds / $withTime->count(), 2);
    }

    // ── Math helpers ──────────────────────────────────────────────────────────

    private function populationStdDev(array $values): float
    {
        $n = count($values);
        if ($n < 2) {
            return 0.0;
        }

        $mean   = array_sum($values) / $n;
        $sumSq  = array_sum(array_map(fn($v) => ($v - $mean) ** 2, $values));

        return sqrt($sumSq / $n);
    }
}
