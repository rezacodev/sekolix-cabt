<?php

namespace App\Filament\Resources\LaporanResource\Pages;

use App\Filament\Resources\LaporanResource;
use App\Jobs\RecalculateItemStatistics;
use App\Models\ExamSession;
use App\Models\MataPelajaran;
use App\Models\QuestionStatistic;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AnalisisButirSoal extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = LaporanResource::class;
    protected static string $view     = 'filament.resources.laporan-resource.pages.analisis-butir-soal';

    public ExamSession $record;

    public function mount(ExamSession $record): void
    {
        $this->record = $record->load(['package']);
    }

    public function getTitle(): string
    {
        return 'Analisis Butir Soal — ' . str($this->record->nama_sesi)->limit(40)->toString();
    }

    public function getBreadcrumbs(): array
    {
        $sesi = str($this->record->nama_sesi)->limit(35)->toString();
        return [
            LaporanResource::getUrl()                                    => 'Laporan Ujian',
            AnalisisButirSoal::getUrl(['record' => $this->record->id])   => $sesi,
            '#'                                                          => 'Analisis Butir',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('hitung_ulang')
                ->label('Hitung Ulang Statistik')
                ->icon('heroicon-o-calculator')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Hitung Ulang Statistik Butir Soal')
                ->modalDescription(
                    'Proses akan menghitung ulang P-value, Discrimination Index, dan Distractor Distribution ' .
                        'untuk semua soal di sesi ini (lintas semua attempt historis). Proses berjalan di background.'
                )
                ->action(function () {
                    RecalculateItemStatistics::dispatch($this->record->id);
                    Notification::make()
                        ->title('Job dikirim')
                        ->body('Statistik akan dihitung ulang di background. Refresh halaman beberapa saat kemudian.')
                        ->success()
                        ->send();
                }),

            Action::make('statistik_soal')
                ->label('Statistik Soal')
                ->icon('heroicon-o-chart-pie')
                ->color('gray')
                ->url(StatistikSoal::getUrl(['record' => $this->record->id])),

            Action::make('kembali')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(LaporanResource::getUrl()),
        ];
    }

    public function table(Table $table): Table
    {
        $questionIds = DB::table('attempt_questions as aq')
            ->join('exam_attempts as ea', 'aq.attempt_id', '=', 'ea.id')
            ->where('ea.exam_session_id', $this->record->id)
            ->distinct()
            ->pluck('aq.question_id');

        return $table
            ->query(
                QuestionStatistic::query()
                    ->whereIn('question_id', $questionIds)
                    ->with(['question.category.mataPelajaran'])
            )
            ->defaultSort('p_value', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('question.teks_soal')
                    ->label('Soal')
                    ->limit(70)
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('question.tipe')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'PG'       => 'primary',
                        'PG_BOBOT' => 'info',
                        'URAIAN'   => 'danger',
                        default    => 'gray',
                    }),

                Tables\Columns\TextColumn::make('question.category.mataPelajaran.nama')
                    ->label('Mata Pelajaran')
                    ->badge()
                    ->color('info')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('question.category.nama')
                    ->label('Kategori')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('question.tingkat_kesulitan')
                    ->label('Kesulitan')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'mudah'  => 'success',
                        'sedang' => 'warning',
                        'sulit'  => 'danger',
                        default  => 'gray',
                    }),

                Tables\Columns\TextColumn::make('question.bloom_level')
                    ->label('Bloom')
                    ->badge()
                    ->color(fn($state) => match (true) {
                        in_array($state, ['C1', 'C2']) => 'gray',
                        in_array($state, ['C3', 'C4']) => 'info',
                        default                        => 'warning',
                    }),

                Tables\Columns\TextColumn::make('total_attempts')
                    ->label('N')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('p_value')
                    ->label('P-value')
                    ->sortable()
                    ->alignCenter()
                    ->formatStateUsing(fn($state) => $state !== null ? number_format((float) $state, 3) : '—')
                    ->badge()
                    ->color(fn($record) => $record?->pValueColor() ?? 'gray')
                    ->description(fn($record) => $record?->pValueLabel()),

                Tables\Columns\TextColumn::make('discrimination_index')
                    ->label('Daya Beda (D)')
                    ->sortable()
                    ->alignCenter()
                    ->formatStateUsing(fn($state) => $state !== null ? number_format((float) $state, 3) : '—')
                    ->badge()
                    ->color(fn($record) => $record?->discriminationColor() ?? 'gray')
                    ->description(fn($record) => $record?->discriminationLabel()),

                Tables\Columns\TextColumn::make('avg_response_seconds')
                    ->label('Avg. Waktu')
                    ->alignCenter()
                    ->sortable()
                    ->formatStateUsing(fn($state) => $state !== null ? number_format((float) $state, 0) . ' det' : '—'),

                Tables\Columns\TextColumn::make('last_calculated_at')
                    ->label('Dihitung')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('mata_pelajaran')
                    ->label('Mata Pelajaran')
                    ->options(MataPelajaran::where('aktif', true)->orderBy('nama')->pluck('nama', 'id'))
                    ->query(fn($query, $data) => $data['value']
                        ? $query->whereHas('question.category', fn($q) => $q->where('mata_pelajaran_id', $data['value']))
                        : $query
                    )
                    ->native(false),

                Tables\Filters\SelectFilter::make('question.tipe')
                    ->label('Tipe Soal')
                    ->relationship('question', 'tipe')
                    ->options(\App\Models\Question::TIPE_LABELS)
                    ->native(false),

                Tables\Filters\SelectFilter::make('question.tingkat_kesulitan')
                    ->label('Kesulitan')
                    ->relationship('question', 'tingkat_kesulitan')
                    ->options(\App\Models\Question::KESULITAN_LABELS)
                    ->native(false),
            ])
            ->emptyStateIcon('heroicon-o-calculator')
            ->emptyStateHeading('Belum ada statistik')
            ->emptyStateDescription('Klik "Hitung Ulang Statistik" di atas untuk menghitung P-value dan Discrimination Index soal-soal di sesi ini.')
            ->striped();
    }
}
