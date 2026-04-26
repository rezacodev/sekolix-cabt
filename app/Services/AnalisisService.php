<?php

namespace App\Services;

use App\Models\ExamAttempt;
use App\Models\ExamSession;
use Illuminate\Support\Collection;

class AnalisisService
{
    /**
     * Data untuk Tabel Analisis Ulangan Harian utama.
     */
    public function analisisUlangan(int $sesiId): array
    {
        $session = ExamSession::with('package.questions', 'creator')->findOrFail($sesiId);
        $kkm     = $session->kkm ?? 70;
        $max1    = $session->pengayaan_max_1 ?? 83;
        $max2    = $session->pengayaan_max_2 ?? 92;

        $soalList = $session->package->questions()
            ->withPivot('urutan')
            ->orderByPivot('urutan')
            ->get();

        $allAttempts = ExamAttempt::with(['user.rombel', 'questions'])
            ->where('exam_session_id', $sesiId)
            ->whereIn('status', [
                ExamAttempt::STATUS_SELESAI,
                ExamAttempt::STATUS_TIMEOUT,
                ExamAttempt::STATUS_DISKUALIFIKASI,
            ])
            ->get();

        // Best attempt per user (highest nilai_akhir)
        $bestAttempts = $allAttempts
            ->groupBy('user_id')
            ->map(fn($g) => $g->sortByDesc('nilai_akhir')->first());

        // All attempts per user for perbaikan sheet
        $allAttemptsByUser = $allAttempts
            ->groupBy('user_id')
            ->map(fn($g) => $g->sortBy('attempt_ke')->values());

        $pesertaData = $bestAttempts->values()
            ->sortBy(fn($a) => strtolower($a->user?->name ?? ''))
            ->values()
            ->map(function ($attempt) use ($soalList, $kkm) {
                $jawabanMap = $attempt->questions
                    ->mapWithKeys(fn($aq) => [$aq->question_id => (float) ($aq->nilai_perolehan ?? 0)]);

                $skorPerSoal = $soalList->map(fn($q) => $jawabanMap->get($q->id, 0))->values();
                $total       = (float) ($attempt->nilai_akhir ?? 0);

                return (object) [
                    'user_id'       => $attempt->user_id,
                    'nama'          => $attempt->user?->name ?? '—',
                    'nomor_peserta' => $attempt->user?->nomor_peserta ?? '—',
                    'rombel'        => $attempt->user?->rombel?->nama ?? '—',
                    'skor_per_soal' => $skorPerSoal,
                    'total'         => $total,
                    'persen'        => round($total, 0),
                    'tuntas'        => $total >= $kkm,
                    'attempt_ke'    => $attempt->attempt_ke,
                ];
            });

        $soalStats = $soalList->values()->map(function ($q, $idx) use ($pesertaData, $session) {
            $bobot      = (float) ($q->pivot->bobot ?? $q->bobot ?? 10);
            $jmlSkor    = $pesertaData->sum(fn($p) => $p->skor_per_soal[$idx] ?? 0);
            $jmlSkorMax = $bobot * $pesertaData->count();
            $persen     = $jmlSkorMax > 0 ? round($jmlSkor / $jmlSkorMax * 100) : 0;

            return (object) [
                'urutan'          => $q->pivot->urutan ?? ($idx + 1),
                'question_id'     => $q->id,
                'bobot'           => $bobot,
                'jml_skor'        => $jmlSkor,
                'jml_skor_max'    => $jmlSkorMax,
                'persen_skor'     => $persen,
                'tuntas_klasikal' => $persen >= ($session->kkm_klasikal ?? 65),
            ];
        });

        $distribusi = $this->distribusiNilai($pesertaData, $kkm, $max1, $max2);

        return [
            'session'            => $session,
            'soal_list'          => $soalList,
            'peserta_data'       => $pesertaData,
            'soal_stats'         => $soalStats,
            'distribusi'         => $distribusi,
            'total_tuntas'       => $pesertaData->where('tuntas', true)->count(),
            'total_tidak_tuntas' => $pesertaData->where('tuntas', false)->count(),
            'all_attempts_by_user' => $allAttemptsByUser,
        ];
    }

    /**
     * Data untuk Hasil Analisis (Ketuntasan Individual & Klasikal).
     */
    public function hasilAnalisis(array $analisisData): array
    {
        $soalStats   = $analisisData['soal_stats'];
        $pesertaData = $analisisData['peserta_data'];

        $soalTidakTuntas = $soalStats->where('tuntas_klasikal', false)->values();
        $soalTuntas      = $soalStats->where('tuntas_klasikal', true)->values();

        // Peserta tidak tuntas beserta soal-soal yang salah
        $pesertaTidakTuntas = $pesertaData->where('tuntas', false)->values()->map(function ($p) use ($soalStats) {
            $soalSalah = $soalStats->filter(fn($s, $idx) => ($p->skor_per_soal[$idx] ?? 0) == 0)->values();
            return (object) [
                'nama'          => $p->nama,
                'nomor_peserta' => $p->nomor_peserta,
                'total'         => $p->total,
                'soal_salah'    => $soalSalah->pluck('urutan'),
            ];
        });

        return [
            'total_peserta'           => $pesertaData->count(),
            'total_tuntas'            => $pesertaData->where('tuntas', true)->count(),
            'total_tidak_tuntas'      => $pesertaData->where('tuntas', false)->count(),
            'total_soal'              => $soalStats->count(),
            'soal_tuntas_count'       => $soalTuntas->count(),
            'soal_tidak_tuntas_count' => $soalTidakTuntas->count(),
            'soal_tidak_tuntas'       => $soalTidakTuntas,
            'peserta_tidak_tuntas'    => $pesertaTidakTuntas,
            'peserta_perbaikan'       => $pesertaData->where('tuntas', false)->values(),
        ];
    }

    /**
     * Data untuk Program Pengayaan.
     */
    public function programPengayaan(array $analisisData): array
    {
        $session = $analisisData['session'];
        $kkm     = $session->kkm ?? 70;
        $max1    = $session->pengayaan_max_1 ?? 83;
        $max2    = $session->pengayaan_max_2 ?? 92;
        $tuntas  = $analisisData['peserta_data']->where('tuntas', true)->values();

        $rentang = $this->makePengayaanRentang($kkm, $max1, $max2);

        $kelompok = collect($rentang)->map(function ($r) use ($tuntas) {
            return (object) [
                'label'   => $r['label'],
                'min'     => $r['min'],
                'max'     => $r['max'],
                'peserta' => $tuntas->filter(
                    fn($p) => $p->persen >= $r['min'] && $p->persen <= $r['max']
                )->values(),
            ];
        });

        return ['kelompok' => $kelompok];
    }

    private function makePengayaanRentang(int $kkm, int $max1, int $max2): array
    {
        $max1 = min(max($max1, $kkm), 100);
        $max2 = min(max($max2, $max1 + 1), 100);

        $rentang = [
            ['min' => $kkm, 'max' => $max1, 'label' => "{$kkm}–{$max1}"],
        ];

        if ($max1 + 1 <= $max2) {
            $rentang[] = [
                'min' => $max1 + 1,
                'max' => $max2,
                'label' => ($max1 + 1) . "–{$max2}",
            ];
        }

        if ($max2 < 100) {
            $rentang[] = [
                'min' => $max2 + 1,
                'max' => 100,
                'label' => ($max2 + 1) . '–100',
            ];
        }

        return $rentang;
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function distribusiNilai(Collection $pesertaData, int $kkm, int $max1, int $max2): array
    {
        $max1 = min(max($max1, $kkm), 100);
        $max2 = min(max($max2, $max1 + 1), 100);

        return [
            'tidak_tuntas'  => $pesertaData->where('tuntas', false)->count(),
            'tuntas_rendah' => $pesertaData->filter(fn($p) => $p->tuntas && $p->persen <= $max1)->count(),
            'tuntas_sedang' => $pesertaData->filter(fn($p) => $p->tuntas && $p->persen >= $max1 + 1 && $p->persen <= $max2)->count(),
            'tuntas_tinggi' => $pesertaData->filter(fn($p) => $p->tuntas && $p->persen >= $max2 + 1)->count(),
        ];
    }
}
