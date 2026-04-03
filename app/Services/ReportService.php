<?php

namespace App\Services;

use App\Models\AttemptQuestion;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSessionParticipant;
use Illuminate\Support\Collection;

class ReportService
{
    // ── Rekap Nilai ───────────────────────────────────────────────────────────

    /**
     * Ambil attempt terbaik (attempt_ke tertinggi) per peserta dalam satu sesi.
     *
     * Mengembalikan Collection of stdClass:
     *   no, user_id, nama, nomor_peserta, rombel_nama,
     *   nilai_akhir, jumlah_benar, jumlah_salah, jumlah_kosong,
     *   status, attempt_ke, durasi_detik, attempt (raw model)
     */
    public function rekapNilai(int $sesiId): Collection
    {
        $attempts = ExamAttempt::with(['user.rombel'])
            ->where('exam_session_id', $sesiId)
            ->whereIn('status', [
                ExamAttempt::STATUS_SELESAI,
                ExamAttempt::STATUS_TIMEOUT,
                ExamAttempt::STATUS_DISKUALIFIKASI,
            ])
            ->get()
            ->groupBy('user_id')
            ->map(fn($group) => $group->sortByDesc('attempt_ke')->first());

        return $attempts
            ->values()
            ->sortBy('user.name')
            ->values()
            ->map(function ($attempt, $index) {
                $durasi = ($attempt->waktu_mulai && $attempt->waktu_selesai)
                    ? (int) $attempt->waktu_mulai->diffInSeconds($attempt->waktu_selesai)
                    : null;

                return (object) [
                    'no'            => $index + 1,
                    'user_id'       => $attempt->user_id,
                    'nama'          => $attempt->user?->name ?? '—',
                    'nomor_peserta' => $attempt->user?->nomor_peserta ?? '—',
                    'rombel_nama'   => $attempt->user?->rombel?->nama ?? '—',
                    'nilai_akhir'   => $attempt->nilai_akhir,
                    'jumlah_benar'  => $attempt->jumlah_benar ?? 0,
                    'jumlah_salah'  => $attempt->jumlah_salah ?? 0,
                    'jumlah_kosong' => $attempt->jumlah_kosong ?? 0,
                    'status'        => $attempt->status,
                    'attempt_ke'    => $attempt->attempt_ke,
                    'durasi_detik'  => $durasi,
                    'attempt'       => $attempt,
                ];
            });
    }

    /**
     * Statistik ringkasan nilai (rata-rata, tertinggi, terendah, median).
     */
    public function statistikNilai(int $sesiId): array
    {
        $rekap = $this->rekapNilai($sesiId);
        $nilai = $rekap->pluck('nilai_akhir')->filter()->map(fn($v) => (float) $v);

        return [
            'total_peserta'   => $rekap->count(),
            'rata_rata'       => round((float) ($nilai->avg() ?? 0), 2),
            'nilai_tertinggi' => (float) ($nilai->max() ?? 0),
            'nilai_terendah'  => (float) ($nilai->min() ?? 0),
            'median'          => $this->median($nilai),
        ];
    }

    // ── Rekap Kehadiran ───────────────────────────────────────────────────────

    /**
     * Rekap kehadiran berdasarkan ExamSessionParticipant.
     *
     * Mengembalikan array:
     *   total, selesai, sedang, belum, diskualifikasi, list (Collection)
     */
    public function rekapKehadiran(int $sesiId): array
    {
        $participants = ExamSessionParticipant::with('user.rombel')
            ->where('exam_session_id', $sesiId)
            ->get();

        return [
            'total'          => $participants->count(),
            'selesai'        => $participants->where('status', ExamSessionParticipant::STATUS_SELESAI)->count(),
            'sedang'         => $participants->where('status', ExamSessionParticipant::STATUS_SEDANG)->count(),
            'belum'          => $participants->where('status', ExamSessionParticipant::STATUS_BELUM)->count(),
            'diskualifikasi' => $participants->where('status', ExamSessionParticipant::STATUS_DISKUALIFIKASI)->count(),
            'list'           => $participants->sortBy('user.name')->values(),
        ];
    }

    // ── Statistik Soal ────────────────────────────────────────────────────────

    /**
     * Distribusi jawaban per soal dalam satu sesi.
     *
     * Mengembalikan Collection of stdClass per soal:
     *   no, question, tipe, teks, total_jawab,
     *   jumlah_benar, jumlah_salah, jumlah_kosong, persen_benar,
     *   distribusi_opsi (array keyed by kode_opsi)
     */
    public function statistikSoal(int $sesiId): Collection
    {
        $session = \App\Models\ExamSession::with('package.questions.options')->find($sesiId);

        if (! $session || ! $session->package) {
            return collect();
        }

        $questions = $session->package->questions()
            ->with(['options', 'keywords'])
            ->orderBy('exam_package_questions.urutan')
            ->get();

        $attemptIds = ExamAttempt::where('exam_session_id', $sesiId)->pluck('id');

        return $questions->map(function ($question, $index) use ($attemptIds) {
            $aq = AttemptQuestion::whereIn('attempt_id', $attemptIds)
                ->where('question_id', $question->id)
                ->get();

            $totalJawab  = $aq->count();
            $jumlahBenar = $aq->where('is_correct', true)->count();
            $jumlahKosong = $aq->filter(
                fn($a) => empty($a->jawaban_peserta) && empty($a->jawaban_file)
            )->count();

            $distribusiOpsi = [];

            if (in_array($question->tipe, ['PG', 'PG_BOBOT'])) {
                foreach ($question->options as $opt) {
                    $count = $aq->filter(
                        fn($a) => trim((string) $a->jawaban_peserta) === $opt->kode_opsi
                    )->count();
                    $distribusiOpsi[$opt->kode_opsi] = [
                        'label'   => $opt->kode_opsi,
                        'teks'    => strip_tags($opt->teks_opsi),
                        'count'   => $count,
                        'persen'  => $totalJawab > 0 ? round($count / $totalJawab * 100, 1) : 0,
                        'correct' => (bool) $opt->is_correct,
                    ];
                }
            } elseif ($question->tipe === 'PGJ') {
                foreach ($question->options as $opt) {
                    $count = $aq->filter(function ($a) use ($opt) {
                        $jawaban = json_decode($a->jawaban_peserta ?? '[]', true) ?? [];
                        return in_array($opt->kode_opsi, $jawaban);
                    })->count();
                    $distribusiOpsi[$opt->kode_opsi] = [
                        'label'   => $opt->kode_opsi,
                        'teks'    => strip_tags($opt->teks_opsi),
                        'count'   => $count,
                        'persen'  => $totalJawab > 0 ? round($count / $totalJawab * 100, 1) : 0,
                        'correct' => (bool) $opt->is_correct,
                    ];
                }
            }

            return (object) [
                'no'              => $index + 1,
                'question'        => $question,
                'tipe'            => $question->tipe,
                'teks'            => strip_tags($question->teks_soal),
                'total_jawab'     => $totalJawab,
                'jumlah_benar'    => $jumlahBenar,
                'jumlah_salah'    => $totalJawab - $jumlahBenar - $jumlahKosong,
                'jumlah_kosong'   => $jumlahKosong,
                'persen_benar'    => $totalJawab > 0 ? round($jumlahBenar / $totalJawab * 100, 1) : 0,
                'distribusi_opsi' => $distribusiOpsi,
            ];
        });
    }

    // ── Distribusi Nilai ──────────────────────────────────────────────────────

    /**
     * Distribusi nilai dalam bin 10 poin (0-10, 11-20, …, 91-100).
     *
     * Mengembalikan array 10 elemen, masing-masing:
     *   ['range' => '0–10', 'min' => 0, 'max' => 10, 'count' => int]
     */
    public function distribusiNilai(int $sesiId): array
    {
        $nilai = ExamAttempt::where('exam_session_id', $sesiId)
            ->whereIn('status', [
                ExamAttempt::STATUS_SELESAI,
                ExamAttempt::STATUS_TIMEOUT,
                ExamAttempt::STATUS_DISKUALIFIKASI,
            ])
            ->whereNotNull('nilai_akhir')
            ->get()
            ->groupBy('user_id')
            ->map(fn($g) => (float) $g->sortByDesc('attempt_ke')->first()->nilai_akhir)
            ->values();

        $bins = [];
        for ($i = 0; $i < 10; $i++) {
            $lo = $i * 10 + ($i > 0 ? 1 : 0);  // 0, 11, 21, …
            $hi = ($i + 1) * 10;                 // 10, 20, …, 100
            if ($i === 0) {
                $lo = 0;
            }
            $bins[] = [
                'range' => $lo . '–' . $hi,
                'min'   => $lo,
                'max'   => $hi,
                'count' => $nilai->filter(fn($v) => $v >= $lo && $v <= $hi)->count(),
            ];
        }

        return $bins;
    }

    // ── Komparasi Sesi ────────────────────────────────────────────────────────

    /**
     * Komparasi nilai peserta antara dua sesi.
     *
     * @param  'peserta'|'rombel'  $mode
     * @return \Illuminate\Support\Collection  tiap item: user_id, nama, rombel, nilai_a, nilai_b, selisih, tren
     */
    public function komparasiSesi(int $sesiIdA, int $sesiIdB, string $mode = 'peserta'): \Illuminate\Support\Collection
    {
        $pick = fn(int $sesiId) => ExamAttempt::with('user.rombel')
            ->where('exam_session_id', $sesiId)
            ->whereIn('status', [
                ExamAttempt::STATUS_SELESAI,
                ExamAttempt::STATUS_TIMEOUT,
                ExamAttempt::STATUS_DISKUALIFIKASI,
            ])
            ->get()
            ->groupBy('user_id')
            ->map(fn($g) => $g->sortByDesc('attempt_ke')->first());

        $attA = $pick($sesiIdA);
        $attB = $pick($sesiIdB);

        $allIds = $attA->keys()->merge($attB->keys())->unique();

        return $allIds->map(function ($userId) use ($attA, $attB) {
            $aRow = $attA->get($userId);
            $bRow = $attB->get($userId);

            $nilaiA = $aRow ? (float) $aRow->nilai_akhir : null;
            $nilaiB = $bRow ? (float) $bRow->nilai_akhir : null;

            $selisih = ($nilaiA !== null && $nilaiB !== null) ? round($nilaiB - $nilaiA, 2) : null;

            $tren = null;
            if ($selisih !== null) {
                $tren = $selisih > 0 ? 'naik' : ($selisih < 0 ? 'turun' : 'tetap');
            }

            $user     = $aRow?->user ?? $bRow?->user;

            return (object) [
                'user_id'     => $userId,
                'nama'        => $user?->name ?? '—',
                'rombel_nama' => $user?->rombel?->nama ?? '—',
                'nilai_a'     => $nilaiA,
                'nilai_b'     => $nilaiB,
                'selisih'     => $selisih,
                'tren'        => $tren,
            ];
        })->sortBy('nama')->values();
    }

    // ── Rekap Kecurangan ──────────────────────────────────────────────────────

    /**
     * Rekap event kecurangan per peserta dalam satu sesi.
     *
     * Mengembalikan Collection of stdClass:
     *   user_id, nama, nomor_peserta, rombel_nama,
     *   tab_switch, blur, kick, timeout_count,
     *   auto_submitted, status_akhir,
     *   logs (Collection of AttemptLog, chronological)
     */
    public function rekapKecurangan(int $sesiId): \Illuminate\Support\Collection
    {
        $attempts = ExamAttempt::with(['user.rombel', 'logs'])
            ->where('exam_session_id', $sesiId)
            ->get()
            ->groupBy('user_id')
            ->map(fn($g) => $g->sortByDesc('attempt_ke')->first());

        return $attempts->values()->sortBy('user.name')->values()->map(function ($attempt) {
            $logs       = $attempt->logs ?? collect();
            $tabSwitch  = $logs->where('event_type', \App\Models\AttemptLog::EVENT_TAB_SWITCH)->count();
            $blur       = $logs->where('event_type', \App\Models\AttemptLog::EVENT_BLUR)->count();
            $kick       = $logs->where('event_type', \App\Models\AttemptLog::EVENT_KICK)->count();
            $timeoutCnt = $logs->where('event_type', \App\Models\AttemptLog::EVENT_TIMEOUT)->count();

            return (object) [
                'user_id'        => $attempt->user_id,
                'nama'           => $attempt->user?->name ?? '—',
                'nomor_peserta'  => $attempt->user?->nomor_peserta ?? '—',
                'rombel_nama'    => $attempt->user?->rombel?->nama ?? '—',
                'tab_switch'     => $tabSwitch,
                'blur'           => $blur,
                'kick'           => $kick,
                'timeout_count'  => $timeoutCnt,
                'auto_submitted' => $attempt->status === ExamAttempt::STATUS_TIMEOUT,
                'status_akhir'   => $attempt->status,
                'logs'           => $logs->sortBy('created_at')->values(),
            ];
        });
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function median(Collection $values): float
    {
        if ($values->isEmpty()) {
            return 0.0;
        }

        $sorted = $values->sort()->values();
        $count  = $sorted->count();
        $mid    = intdiv($count, 2);

        return $count % 2 === 0
            ? (float) (($sorted[$mid - 1] + $sorted[$mid]) / 2)
            : (float) $sorted[$mid];
    }
}
