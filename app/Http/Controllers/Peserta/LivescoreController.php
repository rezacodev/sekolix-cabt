<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\ExamAttempt;
use App\Models\ExamSession;
use Illuminate\Support\Facades\Auth;

class LivescoreController extends Controller
{
    // ── GET /sesi/{session}/livescore ────────────────────────────────────────
    public function show(ExamSession $session)
    {
        if (!in_array($session->status, [ExamSession::STATUS_AKTIF, ExamSession::STATUS_SELESAI])) {
            abort(404);
        }

        // If livescore_public is disabled, require authentication
        if (! AppSetting::getBool('livescore_public', true) && ! Auth::check()) {
            return redirect()->route('login');
        }

        return view('peserta.livescore', compact('session'));
    }

    // ── GET /sesi/{session}/livescore/data ───────────────────────────────────
    public function data(ExamSession $session)
    {
        if (!in_array($session->status, [ExamSession::STATUS_AKTIF, ExamSession::STATUS_SELESAI])) {
            return response()->json(['error' => 'Sesi tidak tersedia'], 404);
        }

        $rankings = $this->buildRankings($session);

        $rataRata = count($rankings) > 0
            ? round(array_sum(array_column($rankings, 'nilai')) / count($rankings), 1)
            : null;

        return response()->json([
            'status'     => $session->status,
            'updated_at' => now()->format('H:i:s'),
            'total'      => count($rankings),
            'rata_rata'  => $rataRata,
            'rankings'   => $rankings,
        ]);
    }

    private function buildRankings(ExamSession $session): array
    {
        $best = ExamAttempt::with('user.rombel')
            ->where('exam_session_id', $session->id)
            ->whereIn('status', [ExamAttempt::STATUS_SELESAI, ExamAttempt::STATUS_TIMEOUT])
            ->whereNotNull('nilai_akhir')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($g) => $g->sortByDesc('nilai_akhir')->first())
            ->sortByDesc('nilai_akhir')
            ->values();

        $rank = 1;
        return $best->map(fn ($a) => [
            'rank'          => $rank++,
            'nama'          => $a->user->name,
            'nomor_peserta' => $a->user->nomor_peserta ?? '—',
            'rombel'        => $a->user->rombel?->nama ?? '—',
            'nilai'         => (float) $a->nilai_akhir,
            'benar'         => $a->jumlah_benar,
            'status'        => $a->status,
        ])->toArray();
    }
}
