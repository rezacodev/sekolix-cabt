<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\ExamAttempt;
use App\Models\ExamSessionParticipant;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): \Illuminate\View\View
    {
        $user   = $request->user();
        $userId = $user->id;

        $participations = ExamSessionParticipant::with([
            'session.package',
            'session.attempts' => function ($q) use ($userId) {
                $q->where('user_id', $userId)->latest('waktu_mulai');
            },
        ])
            ->where('user_id', $userId)
            ->get();

        // Map ke data yang berguna untuk view
        $sessions = $participations->map(function ($p) use ($userId) {
            $session      = $p->session;
            $package      = $session->package;
            $lastAttempt  = $session->attempts->first();
            $activeAttempt = $session->attempts->firstWhere('status', ExamAttempt::STATUS_BERLANGSUNG);
            $attemptCount  = $session->attempts->count();
            $sisaAttempt   = $package->max_pengulangan == 0
                ? null  // null = tidak dibatasi
                : max(0, $package->max_pengulangan - $attemptCount);

            return [
                'participation'  => $p,
                'session'        => $session,
                'package'        => $package,
                'last_attempt'   => $lastAttempt,
                'active_attempt' => $activeAttempt,
                'attempt_count'  => $attemptCount,
                'sisa_attempt'   => $sisaAttempt,
            ];
        });

        // Pengumuman aktif yang relevan untuk peserta ini
        $userRombelIds = $user->rombels()->pluck('rombels.id');
        $announcements = Announcement::aktif()
            ->where(function ($q) use ($user, $userRombelIds) {
                $q->where('target', Announcement::TARGET_SEMUA)
                    ->orWhere(function ($q2) use ($userRombelIds) {
                        $q2->where('target', Announcement::TARGET_PER_ROMBEL)
                            ->whereIn('rombel_id', $userRombelIds);
                    });
            })
            ->orderByDesc('created_at')
            ->get();

        return view('peserta.dashboard', compact('sessions', 'announcements'));
    }
}
