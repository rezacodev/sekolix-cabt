<?php

namespace App\Filament\Pages;

use App\Models\ExamAttempt;
use App\Models\ExamSession;
use App\Models\User;
use App\Filament\Concerns\HasHelpHeader;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class DashboardGuru extends Page
{
    use HasHelpHeader;

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
        // Default: tidak ada sesi dipilih — user harus pilih manual
        $this->selectedSesiId = null;
    }

    // Livewire reactive hook — triggers re-render automatically
    public function updatedSelectedSesiId(): void {}

    public function getHeaderActions(): array
    {
        return $this->appendHelpAction([]);
    }

    protected function getHelpModalView(): string
    {
        return 'filament.pages.actions.modal-help-dashboard-guru';
    }

    public function getTitle(): string
    {
        return 'Dashboard Guru';
    }

    // ── View Data ────────────────────────────────────────────────────────────
    public function getViewData(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Admin melihat semua rombel; guru hanya rombel yang diampu
        $rombelsAmpu = $user->level >= User::LEVEL_ADMIN
            ? \App\Models\Rombel::with(['peserta'])->orderBy('nama')->get()
            : $user->rombelsAmpu()->with(['peserta'])->get();

        // Admin melihat semua sesi; guru hanya sesi buatan sendiri
        $sesiQuery = ExamSession::whereNotIn('status', [ExamSession::STATUS_DIBATALKAN])
            ->orderByDesc('waktu_mulai');
        if ($user->level < User::LEVEL_ADMIN) {
            $sesiQuery->where('created_by', $user->id);
        }
        $sesiOptions = $sesiQuery->get()
            ->mapWithKeys(fn($s) => [
                $s->id => $s->nama_sesi . ' (' . (ExamSession::STATUS_LABELS[$s->status] ?? $s->status) . ')',
            ]);

        if (!$this->selectedSesiId) {
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

            $allAttempts = ExamAttempt::where('exam_session_id', $sesi->id)
                ->whereIn('user_id', $pesertaIds)
                ->get()
                ->groupBy('user_id');

            $attempts      = $allAttempts->map(fn($g) => $g->sortByDesc('nilai_akhir')->first());
            $attemptCounts = $allAttempts->map(fn($g) => $g->count());

            $pesertaList = $rombel->peserta
                ->sortBy('name')
                ->values()
                ->map(function ($p, $idx) use ($attempts, $attemptCounts) {
                    $attempt = $attempts->get($p->id);

                    $durasi = null;
                    if ($attempt && $attempt->waktu_selesai && $attempt->waktu_mulai) {
                        $totalDetik = (int) abs($attempt->waktu_mulai->diffInSeconds($attempt->waktu_selesai));
                        $menit      = intdiv($totalDetik, 60);
                        $detik      = $totalDetik % 60;
                        $durasi     = $menit . 'm ' . str_pad($detik, 2, '0', STR_PAD_LEFT) . 'd';
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
                        'attempt_ke'  => $attemptCounts->get($p->id, 0),
                        'status'      => $attempt?->status,
                    ];
                });

            // Sort by nilai desc, re-number
            $sorted = $pesertaList
                ->sortByDesc(fn($p) => $p->nilai ?? -1)
                ->values()
                ->map(function ($item, $idx) {
                    $item->no = $idx + 1;
                    return $item;
                });

            $submitted    = $sorted->filter(fn($p) => $p->nilai !== null);
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
