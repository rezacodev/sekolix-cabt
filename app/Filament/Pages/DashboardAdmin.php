<?php

namespace App\Filament\Pages;

use App\Models\Announcement;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\Rombel;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardAdmin extends \Filament\Pages\Dashboard
{
    protected static string $view = 'filament.pages.dashboard-admin';

    protected static ?string $navigationIcon  = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?int    $navigationSort  = -1;

    public static function canAccess(): bool
    {
        return Auth::user()?->level >= User::LEVEL_GURU;
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Dashboard';
    }

    /** Disable default Filament widgets — we render everything in the custom view. */
    public function getWidgets(): array
    {
        return [];
    }

    protected function getViewData(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return $user->level >= User::LEVEL_ADMIN
            ? $this->buildAdminData($user)
            : $this->buildGuruData($user);
    }

    // ── Admin / Super-Admin data ──────────────────────────────────────────────

    private function buildAdminData(User $user): array
    {
        $totalPeserta     = User::where('level', User::LEVEL_PESERTA)->count();
        $totalGuru        = User::where('level', User::LEVEL_GURU)->count();
        $totalRombelAktif = Rombel::where('aktif', true)->count();
        $totalSoal        = Question::count();
        $totalPaket       = ExamPackage::count();
        $totalSesi        = ExamSession::whereNotIn('status', [ExamSession::STATUS_DIBATALKAN])->count();

        // Sesi yang sedang aktif saat ini
        $sesiAktif = ExamSession::where('status', ExamSession::STATUS_AKTIF)
            ->with(['package', 'creator'])
            ->orderByDesc('waktu_mulai')
            ->get()
            ->map(function ($s) {
                $sedang  = ExamAttempt::where('exam_session_id', $s->id)
                    ->where('status', ExamAttempt::STATUS_BERLANGSUNG)
                    ->count();
                $selesai = ExamAttempt::where('exam_session_id', $s->id)
                    ->whereIn('status', [
                        ExamAttempt::STATUS_SELESAI,
                        ExamAttempt::STATUS_TIMEOUT,
                        ExamAttempt::STATUS_DISKUALIFIKASI,
                    ])->count();

                return (object) [
                    'id'                 => $s->id,
                    'nama'               => $s->nama_sesi,
                    'paket'              => $s->package?->nama ?? '—',
                    'guru'               => $s->creator?->name ?? '—',
                    'token'              => $s->token_akses,
                    'waktu_mulai'        => $s->waktu_mulai,
                    'sedang_mengerjakan' => $sedang,
                    'sudah_selesai'      => $selesai,
                ];
            });

        // 6 sesi terakhir (semua status kecuali dibatalkan)
        $sesiTerbaru = ExamSession::with(['package', 'creator'])
            ->whereNotIn('status', [ExamSession::STATUS_DIBATALKAN])
            ->orderByDesc('waktu_mulai')
            ->limit(6)
            ->get();

        // Pengumuman aktif
        $pengumuman = Announcement::aktif()
            ->latest('tanggal_mulai')
            ->limit(5)
            ->get();

        return compact(
            'user', 'totalPeserta', 'totalGuru', 'totalRombelAktif',
            'totalSoal', 'totalPaket', 'totalSesi',
            'sesiAktif', 'sesiTerbaru', 'pengumuman',
        );
    }

    // ── Guru data ─────────────────────────────────────────────────────────────

    private function buildGuruData(User $user): array
    {
        $rombelCount = $user->rombelsAmpu()->count();
        $totalSesi   = ExamSession::where('created_by', $user->id)->count();

        $sesiAktif = ExamSession::where('created_by', $user->id)
            ->where('status', ExamSession::STATUS_AKTIF)
            ->with('package')
            ->orderByDesc('waktu_mulai')
            ->get()
            ->map(function ($s) {
                $sedang  = ExamAttempt::where('exam_session_id', $s->id)
                    ->where('status', ExamAttempt::STATUS_BERLANGSUNG)
                    ->count();
                $selesai = ExamAttempt::where('exam_session_id', $s->id)
                    ->whereIn('status', [ExamAttempt::STATUS_SELESAI, ExamAttempt::STATUS_TIMEOUT])
                    ->count();

                return (object) [
                    'id'                 => $s->id,
                    'nama'               => $s->nama_sesi,
                    'paket'              => $s->package?->nama ?? '—',
                    'token'              => $s->token_akses,
                    'waktu_mulai'        => $s->waktu_mulai,
                    'sedang_mengerjakan' => $sedang,
                    'sudah_selesai'      => $selesai,
                ];
            });

        $sesiTerakhir = ExamSession::where('created_by', $user->id)
            ->with('package')
            ->whereNotIn('status', [ExamSession::STATUS_DIBATALKAN])
            ->orderByDesc('waktu_mulai')
            ->limit(5)
            ->get();

        return compact('user', 'rombelCount', 'totalSesi', 'sesiAktif', 'sesiTerakhir');
    }
}
