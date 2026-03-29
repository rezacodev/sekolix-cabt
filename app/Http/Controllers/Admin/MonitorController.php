<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class MonitorController extends Controller
{
    // ── GET /admin/sesi/{session}/monitor/data ───────────────────────────────
    public function data(ExamSession $session): JsonResponse
    {
        $this->authorizeSession($session);

        $session->load('package');
        $totalSoal = $session->package?->questions()->count() ?? 0;

        $participants = ExamSessionParticipant::with(['user.rombel'])
            ->where('exam_session_id', $session->id)
            ->get();

        $attempts = ExamAttempt::with('questions')
            ->where('exam_session_id', $session->id)
            ->whereIn('user_id', $participants->pluck('user_id'))
            ->get()
            ->groupBy('user_id')
            ->map(fn ($g) => $g->sortByDesc('id')->first());

        $list = $participants->map(function ($p) use ($attempts, $totalSoal) {
            $attempt   = $attempts->get($p->user_id);
            $dijawab   = 0;
            $sisaWaktu = null;
            $tabSwitch = 0;

            if ($attempt) {
                $dijawab   = $attempt->questions->filter(fn ($q) => $q->isDijawab())->count();
                $sisaWaktu = $attempt->sisaWaktuDetik();
                $tabSwitch = $attempt->tabSwitchCount();
            }

            return [
                'user_id'            => $p->user_id,
                'nama'               => $p->user->name,
                'nomor_peserta'      => $p->user->nomor_peserta ?? '—',
                'rombel'             => $p->user->rombel?->nama ?? '—',
                'participant_status' => $p->status,
                'attempt_status'     => $attempt?->status,
                'dijawab'            => $dijawab,
                'total_soal'         => $totalSoal,
                'sisa_waktu'         => $sisaWaktu,
                'tab_switch'         => $tabSwitch,
            ];
        })->values();

        return response()->json([
            'session_status' => $session->status,
            'total'          => $participants->count(),
            'sedang'         => $participants->where('status', ExamSessionParticipant::STATUS_SEDANG)->count(),
            'selesai'        => $participants->where('status', ExamSessionParticipant::STATUS_SELESAI)->count(),
            'belum'          => $participants->where('status', ExamSessionParticipant::STATUS_BELUM)->count(),
            'diskualifikasi' => $participants->where('status', ExamSessionParticipant::STATUS_DISKUALIFIKASI)->count(),
            'list'           => $list,
            'updated_at'     => now()->format('H:i:s'),
        ]);
    }

    // ── POST /admin/sesi/{session}/paksa-keluar/{userId} ─────────────────────
    public function paksaKeluar(ExamSession $session, int $userId): JsonResponse
    {
        $this->authorizeSession($session);

        $participant = ExamSessionParticipant::where('exam_session_id', $session->id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $participant->update(['status' => ExamSessionParticipant::STATUS_DISKUALIFIKASI]);

        ExamAttempt::where('exam_session_id', $session->id)
            ->where('user_id', $userId)
            ->where('status', ExamAttempt::STATUS_BERLANGSUNG)
            ->update([
                'status'        => ExamAttempt::STATUS_DISKUALIFIKASI,
                'waktu_selesai' => now(),
            ]);

        return response()->json(['success' => true, 'message' => 'Peserta berhasil dikeluarkan.']);
    }

    private function authorizeSession(ExamSession $session): void
    {
        $user = Auth::user();
        if ($user->level === User::LEVEL_GURU && $session->created_by !== $user->id) {
            abort(403);
        }
    }
}
