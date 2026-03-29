<?php

namespace App\Filament\Resources\ExamSessionResource\Pages;

use App\Filament\Resources\ExamSessionResource;
use App\Models\ExamAttempt;
use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;

class MonitorSesi extends Page
{
    protected static string $resource = ExamSessionResource::class;

    protected static string $view = 'filament.resources.exam-session-resource.pages.monitor-sesi';

    public ExamSession $record;

    // ── Kick Confirmation State ───────────────────────────────────────────────
    public bool   $showKickModal = false;
    public ?int   $kickUserId   = null;
    public string $kickNama     = '';

    public function mount(ExamSession $record): void
    {
        $this->record = $record->load(['package', 'creator']);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user->level === User::LEVEL_GURU && $record->created_by !== $user->id) {
            abort(403);
        }
    }

    public function getTitle(): string
    {
        return 'Monitor — ' . str($this->record->nama_sesi)->limit(40)->toString();
    }

    public function getBreadcrumbs(): array
    {
        $sesi = str($this->record->nama_sesi)->limit(35)->toString();
        return [
            ExamSessionResource::getUrl()                            => 'Sesi Ujian',
            MonitorSesi::getUrl(['record' => $this->record->id])    => $sesi,
            '#'                                                      => 'Monitor',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('livescore')
                ->label('Livescore')
                ->icon('heroicon-o-chart-bar')
                ->color('info')
                ->url(fn () => route('livescore.show', $this->record->id))
                ->openUrlInNewTab()
                ->visible(fn () => in_array($this->record->status, [
                    ExamSession::STATUS_AKTIF,
                    ExamSession::STATUS_SELESAI,
                ])),

            Action::make('kembali')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(ExamSessionResource::getUrl()),
        ];
    }

    // ── Livewire Action: Paksa Keluar ────────────────────────────────────────
    public function confirmKick(int $userId, string $nama): void
    {
        $this->kickUserId   = $userId;
        $this->kickNama     = $nama;
        $this->showKickModal = true;
    }

    public function resetKick(): void
    {
        $this->kickUserId    = null;
        $this->kickNama      = '';
        $this->showKickModal = false;
    }

    public function doKick(): void
    {
        if (!$this->kickUserId) return;

        $participant = ExamSessionParticipant::where('exam_session_id', $this->record->id)
            ->where('user_id', $this->kickUserId)
            ->first();

        if (!$participant) {
            Notification::make()->danger()->title('Peserta tidak ditemukan')->send();
            $this->resetKick();
            return;
        }

        $participant->update(['status' => ExamSessionParticipant::STATUS_DISKUALIFIKASI]);

        ExamAttempt::where('exam_session_id', $this->record->id)
            ->where('user_id', $this->kickUserId)
            ->where('status', ExamAttempt::STATUS_BERLANGSUNG)
            ->update([
                'status'        => ExamAttempt::STATUS_DISKUALIFIKASI,
                'waktu_selesai' => now(),
            ]);

        Notification::make()->success()->title('Peserta berhasil dikeluarkan')->send();
        $this->resetKick();
    }

    // ── View Data ────────────────────────────────────────────────────────────
    public function getViewData(): array
    {
        $session   = $this->record->fresh(['package']);
        $totalSoal = $session->package?->questions()->count() ?? 0;

        $participants = ExamSessionParticipant::with(['user.rombel'])
            ->where('exam_session_id', $session->id)
            ->get();

        // Load all latest attempts in one query — include session.package for sisaWaktuDetik()
        $attempts = ExamAttempt::with(['questions', 'session.package'])
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

            return (object) [
                'user_id'            => $p->user_id,
                'nama'               => $p->user->name,
                'nomor_peserta'      => $p->user->nomor_peserta ?? '—',
                'rombel'             => $p->user->rombel?->nama ?? '—',
                'participant_status' => $p->status,
                'attempt_status'     => $attempt?->status,
                'attempt_id'         => $attempt?->id,
                'dijawab'            => $dijawab,
                'total_soal'         => $totalSoal,
                'sisa_waktu'         => $sisaWaktu,
                'tab_switch'         => $tabSwitch,
                'nilai_sementara'    => $attempt?->nilai_akhir,
                'rank'               => null,
            ];
        });

        // Assign rank based on nilai_sementara desc (objects are shared by reference)
        $rankCounter = 1;
        foreach ($list->sortByDesc(fn ($p) => $p->nilai_sementara ?? -1)->values() as $item) {
            if ($item->nilai_sementara !== null) {
                $item->rank = $rankCounter++;
            }
        }

        $list = $list->sortBy(function ($p) {
            return match ($p->participant_status) {
                ExamSessionParticipant::STATUS_SEDANG         => 0,
                ExamSessionParticipant::STATUS_BELUM          => 1,
                ExamSessionParticipant::STATUS_SELESAI        => 2,
                ExamSessionParticipant::STATUS_DISKUALIFIKASI => 3,
                default                                        => 4,
            };
        })->values();

        $stats = [
            'total'          => $participants->count(),
            'sedang'         => $participants->where('status', ExamSessionParticipant::STATUS_SEDANG)->count(),
            'selesai'        => $participants->where('status', ExamSessionParticipant::STATUS_SELESAI)->count(),
            'belum'          => $participants->where('status', ExamSessionParticipant::STATUS_BELUM)->count(),
            'diskualifikasi' => $participants->where('status', ExamSessionParticipant::STATUS_DISKUALIFIKASI)->count(),
        ];

        $livescoreUrl = in_array($session->status, [ExamSession::STATUS_AKTIF, ExamSession::STATUS_SELESAI])
            && \App\Models\AppSetting::getBool('show_livescore', true)
            ? route('livescore.show', $session->id)
            : null;

        return [
            'session'      => $session,
            'list'         => $list,
            'stats'        => $stats,
            'totalSoal'    => $totalSoal,
            'livescoreUrl' => $livescoreUrl,
        ];
    }
}
