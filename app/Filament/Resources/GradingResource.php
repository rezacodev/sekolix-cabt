<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GradingResource\Pages;
use App\Models\ExamAttempt;
use App\Models\ExamPackage;
use App\Models\ExamSession;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * GradingResource
 *
 * Index: daftar Sesi Ujian yang memiliki paket grading_mode = manual,
 *        beserta statistik progres penilaian URAIAN per sesi.
 *
 * Drill-down: klik "Lihat Peserta" → GradingAttemptList (daftar attempt per sesi)
 *             klik "Nilai" pada peserta → GradingDetail (input nilai URAIAN)
 */
class GradingResource extends Resource
{
    protected static ?string $model = ExamSession::class;

    protected static ?string $navigationIcon   = 'heroicon-o-pencil-square';
    protected static ?string $navigationGroup  = 'Penilaian';
    protected static ?string $navigationLabel  = 'Grading URAIAN';
    protected static ?int    $navigationSort   = 50;
    protected static ?string $modelLabel       = 'Sesi Ujian';
    protected static ?string $pluralModelLabel = 'Penilaian per Sesi';
    protected static ?string $slug             = 'grading';

    /** Hanya Admin dan Guru yang bisa mengakses. */
    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->level >= User::LEVEL_GURU;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    // ── Query: sesi yang paketnya grading_mode = manual ──────────────────────
    public static function getEloquentQuery(): Builder
    {
        $query = ExamSession::query()
            ->whereHas('package', fn($q) => $q->where('grading_mode', ExamPackage::GRADING_MANUAL))
            ->with(['package', 'creator']);

        // Guru hanya lihat sesi yang dia buat
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if ($user && $user->level === User::LEVEL_GURU) {
            $query->where('created_by', $user->id);
        }

        return $query;
    }

    // ── Table ─────────────────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_sesi')
                    ->label('Sesi Ujian')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn(ExamSession $r) => $r->package?->nama ?? '—'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status Sesi')
                    ->badge()
                    ->formatStateUsing(fn($state) => ExamSession::STATUS_LABELS[$state] ?? $state)
                    ->color(fn($state) => ExamSession::STATUS_COLORS[$state] ?? 'gray')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('waktu_mulai')
                    ->label('Waktu Ujian')
                    ->dateTime('d M Y, H:i')
                    ->description(fn(ExamSession $r) => 'sd ' . ($r->waktu_selesai?->format('d M Y, H:i') ?? '—'))
                    ->sortable(),

                // ── Kolom statistik penilaian ─────────────────────────────────
                Tables\Columns\TextColumn::make('total_peserta')
                    ->label('Total Peserta')
                    ->getStateUsing(
                        fn(ExamSession $record): int =>
                        ExamAttempt::where('exam_session_id', $record->id)
                            ->whereIn('status', [ExamAttempt::STATUS_SELESAI, ExamAttempt::STATUS_TIMEOUT])
                            ->whereHas(
                                'questions',
                                fn($q) =>
                                $q->whereHas('question', fn($q2) => $q2->where('tipe', 'URAIAN'))
                            )
                            ->count()
                    )
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('sudah_dinilai')
                    ->label('Selesai')
                    ->getStateUsing(
                        fn(ExamSession $record): int =>
                        ExamAttempt::where('exam_session_id', $record->id)
                            ->whereIn('status', [ExamAttempt::STATUS_SELESAI, ExamAttempt::STATUS_TIMEOUT])
                            ->whereHas(
                                'questions',
                                fn($q) =>
                                $q->whereHas('question', fn($q2) => $q2->where('tipe', 'URAIAN'))
                            )
                            ->whereDoesntHave(
                                'questions',
                                fn($q) =>
                                $q->whereHas('question', fn($q2) => $q2->where('tipe', 'URAIAN'))
                                    ->whereNull('nilai_perolehan')
                            )
                            ->count()
                    )
                    ->alignCenter()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('belum_dinilai')
                    ->label('Pending')
                    ->getStateUsing(
                        fn(ExamSession $record): int =>
                        ExamAttempt::where('exam_session_id', $record->id)
                            ->whereIn('status', [ExamAttempt::STATUS_SELESAI, ExamAttempt::STATUS_TIMEOUT])
                            ->whereHas(
                                'questions',
                                fn($q) =>
                                $q->whereHas('question', fn($q2) => $q2->where('tipe', 'URAIAN'))
                                    ->whereNull('nilai_perolehan')
                            )
                            ->count()
                    )
                    ->alignCenter()
                    ->badge()
                    ->color(
                        fn(ExamSession $record): string =>
                        ExamAttempt::where('exam_session_id', $record->id)
                            ->whereHas(
                                'questions',
                                fn($q) =>
                                $q->whereHas('question', fn($q2) => $q2->where('tipe', 'URAIAN'))
                                    ->whereNull('nilai_perolehan')
                            )
                            ->exists()
                            ? 'warning' : 'success'
                    ),

                Tables\Columns\TextColumn::make('rata_nilai')
                    ->label('Rata-rata Nilai')
                    ->getStateUsing(
                        fn(ExamSession $record): string =>
                        number_format(
                            (float) ExamAttempt::where('exam_session_id', $record->id)
                                ->whereNotNull('nilai_akhir')
                                ->avg('nilai_akhir') ?? 0,
                            1
                        )
                    )
                    ->alignCenter()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->placeholder('—'),
            ])
            ->defaultSort('waktu_mulai', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Sesi')
                    ->options(ExamSession::STATUS_LABELS)
                    ->native(false),
                Tables\Filters\SelectFilter::make('created_by')
                    ->label('Dibuat Oleh')
                    ->options(fn() => User::where('level', '>=', User::LEVEL_GURU)->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->native(false)
                    ->hidden(fn(): bool => Auth::user()->level < User::LEVEL_ADMIN),
            ])
            ->actions([
                Tables\Actions\Action::make('lihat_peserta')
                    ->label('Lihat Peserta')
                    ->icon('heroicon-o-users')
                    ->color('primary')
                    ->url(
                        fn(ExamSession $record): string =>
                        Pages\GradingAttemptList::getUrl(['record' => $record->id])
                    ),
            ])
            ->emptyStateHeading('Tidak ada sesi dengan penilaian manual')
            ->emptyStateDescription('Sesi dengan paket "Grading Manual" akan tampil di sini.')
            ->emptyStateIcon('heroicon-o-pencil-square');
    }

    // ── Pages ─────────────────────────────────────────────────────────────────
    public static function getPages(): array
    {
        return [
            'index'   => Pages\ListGradings::route('/'),
            'peserta' => Pages\GradingAttemptList::route('/{record}/peserta'),
            'detail'  => Pages\GradingDetail::route('/attempt/{record}'),
        ];
    }
}
