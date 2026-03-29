<?php

namespace App\Filament\Resources\GradingResource\Pages;

use App\Filament\Resources\GradingResource;
use App\Models\ExamAttempt;
use App\Models\ExamSession;
use Filament\Resources\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

/**
 * GradingAttemptList
 *
 * Menampilkan semua attempt peserta dalam satu sesi ujian (grading_mode = manual).
 * Setiap baris menunjukkan status penilaian URAIAN peserta tersebut.
 */
class GradingAttemptList extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = GradingResource::class;

    protected static string $view = 'filament.resources.grading-resource.pages.grading-attempt-list';

    // ── State ─────────────────────────────────────────────────────────────────
    public ExamSession $record;

    public function mount(ExamSession $record): void
    {
        $this->record = $record->load('package.questions', 'creator', 'participants');
    }

    // ── Breadcrumb / Title ────────────────────────────────────────────────────
    public function getTitle(): string
    {
        return 'Peserta — ' . $this->record->nama_sesi;
    }

    public function getBreadcrumbs(): array
    {
        return [
            GradingResource::getUrl()                        => 'Grading URAIAN',
            GradingAttemptList::getUrl(['record' => $this->record->id]) => $this->record->nama_sesi,
        ];
    }

    // ── Back action ───────────────────────────────────────────────────────────
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('kembali')
                ->label('Kembali ke Daftar Sesi')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(GradingResource::getUrl()),
        ];
    }

    // ── Table ─────────────────────────────────────────────────────────────────
    public function table(Table $table): Table
    {
        return $table
            ->query(
                ExamAttempt::query()
                    ->where('exam_session_id', $this->record->id)
                    ->whereIn('status', [
                        ExamAttempt::STATUS_SELESAI,
                        ExamAttempt::STATUS_TIMEOUT,
                        ExamAttempt::STATUS_DISKUALIFIKASI,
                    ])
                    ->whereHas('questions', fn (Builder $q) =>
                        $q->whereHas('question', fn ($q2) => $q2->where('tipe', 'URAIAN'))
                    )
                    ->with('user')
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.nomor_peserta')
                    ->label('No. Peserta')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama Peserta')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('attempt_ke')
                    ->label('Attempt')
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status Ujian')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        ExamAttempt::STATUS_SELESAI        => 'Selesai',
                        ExamAttempt::STATUS_TIMEOUT        => 'Timeout',
                        ExamAttempt::STATUS_DISKUALIFIKASI => 'Diskualifikasi',
                        default                            => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        ExamAttempt::STATUS_SELESAI        => 'success',
                        ExamAttempt::STATUS_TIMEOUT        => 'warning',
                        ExamAttempt::STATUS_DISKUALIFIKASI => 'danger',
                        default                            => 'gray',
                    })
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('total_uraian')
                    ->label('Total URAIAN')
                    ->getStateUsing(fn (ExamAttempt $record): int =>
                        $record->questions()
                            ->whereHas('question', fn ($q) => $q->where('tipe', 'URAIAN'))
                            ->count()
                    )
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('pending_uraian')
                    ->label('Belum Dinilai')
                    ->getStateUsing(fn (ExamAttempt $record): int =>
                        $record->questions()
                            ->whereHas('question', fn ($q) => $q->where('tipe', 'URAIAN'))
                            ->whereNull('nilai_perolehan')
                            ->count()
                    )
                    ->alignCenter()
                    ->badge()
                    ->color(fn (ExamAttempt $record): string =>
                        $record->questions()
                            ->whereHas('question', fn ($q) => $q->where('tipe', 'URAIAN'))
                            ->whereNull('nilai_perolehan')
                            ->exists()
                            ? 'warning' : 'success'
                    ),

                Tables\Columns\TextColumn::make('nilai_akhir')
                    ->label('Nilai Akhir')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 1) . ' / 100' : '—')
                    ->alignCenter()
                    ->color(fn ($state) => match (true) {
                        $state === null        => 'gray',
                        $state >= 75           => 'success',
                        $state >= 60           => 'warning',
                        default                => 'danger',
                    }),

                Tables\Columns\TextColumn::make('waktu_selesai')
                    ->label('Diselesaikan')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->defaultSort('user.name', 'asc')
            ->actions([
                Tables\Actions\Action::make('nilai')
                    ->label('Nilai')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->visible(fn (ExamAttempt $record): bool =>
                        $record->questions()
                            ->whereHas('question', fn ($q) => $q->where('tipe', 'URAIAN'))
                            ->whereNull('nilai_perolehan')
                            ->exists()
                    )
                    ->url(fn (ExamAttempt $record): string =>
                        GradingDetail::getUrl(['record' => $record->id])
                    ),

                Tables\Actions\Action::make('lihat')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(fn (ExamAttempt $record): bool =>
                        ! $record->questions()
                            ->whereHas('question', fn ($q) => $q->where('tipe', 'URAIAN'))
                            ->whereNull('nilai_perolehan')
                            ->exists()
                    )
                    ->url(fn (ExamAttempt $record): string =>
                        GradingDetail::getUrl(['record' => $record->id])
                    ),
            ])
            ->emptyStateHeading('Belum ada peserta yang menyelesaikan ujian')
            ->emptyStateIcon('heroicon-o-users');
    }
}
