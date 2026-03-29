<?php

namespace App\Filament\Pages;

use App\Models\ExamAttempt;
use App\Models\ExamSession;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class DashboardGuru extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $navigationLabel = 'Dashboard Guru';
    protected static ?int    $navigationSort  = 55;
    protected static ?string $slug            = 'dashboard-guru';
    protected static string  $view            = 'filament.pages.dashboard-guru';

    public ?int $selectedSesiId = null;

    public static function canAccess(): bool
    {
        return Auth::user()?->level >= User::LEVEL_GURU;
    }

    public function mount(): void
    {
        $latest = ExamSession::where('created_by', Auth::id())
            ->whereNotIn('status', [ExamSession::STATUS_DIBATALKAN])
            ->latest('waktu_mulai')
            ->first();

        $this->selectedSesiId = $latest?->id;
    }

    // Livewire reactive hook — triggers re-render automatically
    public function updatedSelectedSesiId(): void {}

    public function getTitle(): string
    {
        return 'Dashboard Guru';
    }

    // ── View Data ────────────────────────────────────────────────────────────
    public function getViewData(): array
    {
        /** @var \App\Models\User $user */
        $user        = Auth::user();
        $rombelsAmpu = $user->rombelsAmpu()->with(['peserta'])->get();

        $sesiOptions = ExamSession::where('created_by', $user->id)
            ->whereNotIn('status', [ExamSession::STATUS_DIBATALKAN])
            ->orderByDesc('waktu_mulai')
            ->get()
            ->mapWithKeys(fn ($s) => [
                $s->id => $s->nama_sesi . ' (' . (ExamSession::STATUS_LABELS[$s->status] ?? $s->status) . ')',
            ]);

        if (!$this->selectedSesiId || $rombelsAmpu->isEmpty()) {
            return [
                'rombelsAmpu' => $rombelsAmpu,
                'sesiOptions' => $sesiOptions,
                'sesi'        => null,
                'rombelData'  => collect(),
            ];
        }

        $sesi = ExamSession::with('package')->find($this->selectedSesiId);

        if (!$sesi) {
            return [
                'rombelsAmpu' => $rombelsAmpu,
                'sesiOptions' => $sesiOptions,
                'sesi'        => null,
                'rombelData'  => collect(),
            ];
        }

        // For each rombel, get peserta + their best attempt nilai
        $rombelData = $rombelsAmpu->map(function ($rombel) use ($sesi) {
            $pesertaIds = $rombel->peserta->pluck('id');

            $attempts = ExamAttempt::where('exam_session_id', $sesi->id)
                ->whereIn('user_id', $pesertaIds)
                ->get()
                ->groupBy('user_id')
                ->map(fn ($g) => $g->sortByDesc('nilai_akhir')->first());

            $pesertaList = $rombel->peserta
                ->sortBy('name')
                ->values()
                ->map(function ($p, $idx) use ($attempts, $sesi) {
                    $attempt    = $attempts->get($p->id);
                    $totalAttempt = ExamAttempt::where('exam_session_id', $sesi->id)
                        ->where('user_id', $p->id)
                        ->count();

                    $durasi = null;
                    if ($attempt && $attempt->waktu_selesai && $attempt->waktu_mulai) {
                        $menit  = $attempt->waktu_selesai->diffInMinutes($attempt->waktu_mulai);
                        $detik  = $attempt->waktu_selesai->diffInSeconds($attempt->waktu_mulai) % 60;
                        $durasi = $menit . 'm ' . $detik . 'd';
                    }

                    return (object) [
                        'no'          => $idx + 1,
                        'nama'        => $p->name,
                        'nomor'       => $p->nomor_peserta ?? '—',
                        'nilai'       => $attempt?->nilai_akhir,
                        'benar'       => $attempt?->jumlah_benar,
                        'salah'       => $attempt?->jumlah_salah,
                        'kosong'      => $attempt?->jumlah_kosong,
                        'durasi'      => $durasi,
                        'attempt_ke'  => $totalAttempt,
                        'status'      => $attempt?->status,
                    ];
                });

            // Sort by nilai desc, re-number
            $sorted = $pesertaList
                ->sortByDesc(fn ($p) => $p->nilai ?? -1)
                ->values()
                ->map(function ($item, $idx) {
                    $item->no = $idx + 1;
                    return $item;
                });

            $submitted    = $sorted->filter(fn ($p) => $p->nilai !== null);
            $rataRata     = $submitted->isNotEmpty() ? $submitted->avg('nilai') : null;

            return (object) [
                'rombel'    => $rombel,
                'peserta'   => $sorted,
                'total'     => $sorted->count(),
                'selesai'   => $submitted->count(),
                'rata_rata' => $rataRata,
                'tertinggi' => $submitted->max('nilai'),
                'terendah'  => $submitted->min('nilai'),
            ];
        });

        return [
            'rombelsAmpu' => $rombelsAmpu,
            'sesiOptions' => $sesiOptions,
            'sesi'        => $sesi,
            'rombelData'  => $rombelData,
        ];
    }
}
