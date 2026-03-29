<?php

namespace App\Filament\Resources\GradingResource\Pages;

use App\Filament\Resources\GradingResource;
use App\Models\AttemptQuestion;
use App\Models\ExamAttempt;
use App\Services\ScoringService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class GradingDetail extends Page
{
    protected static string $resource = GradingResource::class;

    protected static string $view = 'filament.resources.grading-resource.pages.grading-detail';

    // ── Livewire state (int aman di-serialize Livewire antar request) ─────────

    public int $attemptId  = 0;
    public int $sessionId  = 0;

    /** @var array<int, float|null>  keyed by AttemptQuestion.id */
    public array $nilai = [];

    // ── Mount ─────────────────────────────────────────────────────────────────

    public function mount(ExamAttempt $record): void
    {
        $this->attemptId = $record->id;
        $this->sessionId = $record->exam_session_id;

        // Pre-fill nilai dari DB (semua URAIAN, termasuk yang sudah dinilai)
        foreach ($this->fetchUraianQuestions() as $aq) {
            $this->nilai[$aq->id] = $aq->nilai_perolehan !== null
                ? (float) $aq->nilai_perolehan
                : null;
        }
    }

    // ── Data untuk blade — dipanggil sekali per render via getViewData() ──────

    protected function getViewData(): array
    {
        $attempt   = ExamAttempt::with('user', 'session.package')->findOrFail($this->attemptId);
        // Semua soal beserta pilihan & pasangan — untuk tampilan lengkap di blade
        $questions = AttemptQuestion::with([
                'question.options',
                'question.matches',
                'question.keywords',
            ])
            ->where('attempt_id', $this->attemptId)
            ->orderBy('urutan')
            ->get();

        return compact('attempt', 'questions');
    }

    // ── Query helper — hanya URAIAN, untuk validasi & penyimpanan nilai ───────

    private function fetchUraianQuestions(): \Illuminate\Database\Eloquent\Collection
    {
        return AttemptQuestion::with(['question.keywords'])
            ->where('attempt_id', $this->attemptId)
            ->whereHas('question', fn ($q) => $q->where('tipe', 'URAIAN'))
            ->orderBy('urutan')
            ->get();
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('simpan_regrade')
                ->label('Simpan & Hitung Ulang Nilai')
                ->icon('heroicon-o-calculator')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Simpan Nilai')
                ->modalDescription('Nilai semua URAIAN akan disimpan dan nilai akhir peserta akan dihitung ulang.')
                ->action('simpanDanRegrade'),

            Action::make('kembali')
                ->label('Kembali ke Daftar Peserta')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => GradingAttemptList::getUrl(['record' => $this->sessionId])),
        ];
    }

    // ── Submit Logic ──────────────────────────────────────────────────────────

    public function simpanDanRegrade(): void
    {
        $uraianQuestions = $this->fetchUraianQuestions();

        foreach ($uraianQuestions as $aq) {
            $inputRaw = $this->nilai[$aq->id] ?? null;

            if ($inputRaw === null || $inputRaw === '') {
                Notification::make()
                    ->title('Masih ada soal belum dinilai')
                    ->body("Soal nomor {$aq->urutan} belum diisi nilai.")
                    ->warning()
                    ->send();
                return;
            }

            $bobot = (float) $aq->question->bobot;
            $input = (float) $inputRaw;
            if ($input < 0 || $input > $bobot) {
                Notification::make()
                    ->title('Nilai tidak valid')
                    ->body("Nilai soal nomor {$aq->urutan} harus antara 0 dan {$bobot}.")
                    ->warning()
                    ->send();
                return;
            }
        }

        foreach ($uraianQuestions as $aq) {
            $aq->update([
                'nilai_perolehan' => (float) $this->nilai[$aq->id],
                'is_correct'      => null,
            ]);
        }

        $attempt = ExamAttempt::findOrFail($this->attemptId);
        app(ScoringService::class)->regrade($attempt);

        Notification::make()
            ->title('Nilai berhasil disimpan')
            ->body('Nilai akhir peserta telah dihitung ulang.')
            ->success()
            ->send();

        $this->redirect(GradingAttemptList::getUrl(['record' => $this->sessionId]));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function jawabanFileUrl(AttemptQuestion $aq): ?string
    {
        if (! $aq->jawaban_file) {
            return null;
        }

        if (str_starts_with($aq->jawaban_file, 'http')) {
            return $aq->jawaban_file;
        }

        return asset('storage/' . ltrim($aq->jawaban_file, '/'));
    }

    public function getTitle(): string
    {
        $attempt = ExamAttempt::with('user')->findOrFail($this->attemptId);
        return "Penilaian URAIAN — {$attempt->user->name}";
    }
}

