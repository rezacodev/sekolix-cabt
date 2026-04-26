<?php

namespace App\Filament\Resources;

use App\Exports\NilaiExport;
use App\Filament\Resources\LaporanResource\Pages;
use App\Models\ExamAttempt;
use App\Models\ExamSession;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class LaporanResource extends Resource
{
    protected static ?string $model = ExamSession::class;

    protected static ?string $navigationIcon   = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup  = 'Laporan';
    protected static ?string $navigationLabel  = 'Rekap & Statistik';
    protected static ?int    $navigationSort   = 60;
    protected static ?string $modelLabel       = 'Sesi Ujian';
    protected static ?string $pluralModelLabel = 'Laporan per Sesi';
    protected static ?string $slug            = 'laporan';

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
    public static function canCreate(): bool
    {
        return false;
    }
    public static function canEdit(Model $record): bool
    {
        return false;
    }
    public static function canDelete(Model $record): bool
    {
        return false;
    }
    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = ExamSession::query()
            ->whereIn('status', [ExamSession::STATUS_AKTIF, ExamSession::STATUS_SELESAI])
            ->with(['package', 'creator']);

        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if ($user && $user->level === User::LEVEL_GURU) {
            $query->where('created_by', $user->id);
        }

        return $query;
    }

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
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn($state) => ExamSession::STATUS_LABELS[$state] ?? $state)
                    ->color(fn($state) => ExamSession::STATUS_COLORS[$state] ?? 'gray')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('waktu_mulai')
                    ->label('Waktu Ujian')
                    ->dateTime('d M Y, H:i')
                    ->description(fn(ExamSession $r) => 'sd ' . ($r->waktu_selesai?->format('d M Y, H:i') ?? '—'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('peserta_selesai')
                    ->label('Peserta Selesai')
                    ->getStateUsing(
                        fn(ExamSession $r): int =>
                        ExamAttempt::where('exam_session_id', $r->id)
                            ->whereIn('status', [ExamAttempt::STATUS_SELESAI, ExamAttempt::STATUS_TIMEOUT])
                            ->distinct('user_id')
                            ->count('user_id')
                    )
                    ->alignCenter()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('rata_nilai')
                    ->label('Rata-rata')
                    ->getStateUsing(
                        fn(ExamSession $r): string =>
                        number_format(
                            (float) (ExamAttempt::where('exam_session_id', $r->id)->whereNotNull('nilai_akhir')->avg('nilai_akhir') ?? 0),
                            1
                        )
                    )
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->placeholder('—'),
            ])
            ->defaultSort('waktu_mulai', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(ExamSession::STATUS_LABELS)
                    ->native(false),

                Tables\Filters\SelectFilter::make('created_by')
                    ->label('Dibuat Oleh')
                    ->options(fn() => User::where('level', '>=', User::LEVEL_GURU)->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->native(false)
                    ->hidden(fn(): bool => Auth::user()->level < User::LEVEL_ADMIN),

                Tables\Filters\Filter::make('tanggal')
                    ->label('Rentang Tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('dari')
                            ->label('Dari Tanggal')
                            ->native(false),
                        Forms\Components\DatePicker::make('sampai')
                            ->label('Sampai Tanggal')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['dari'] ?? null, fn($q, $v) => $q->whereDate('waktu_mulai', '>=', $v))
                            ->when($data['sampai'] ?? null, fn($q, $v) => $q->whereDate('waktu_mulai', '<=', $v));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['dari'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Dari: ' . date('d M Y', strtotime($data['dari'])))
                                ->removeField('dari');
                        }
                        if ($data['sampai'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Sampai: ' . date('d M Y', strtotime($data['sampai'])))
                                ->removeField('sampai');
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('laporan_nilai')
                    ->label('Rekap Nilai')
                    ->icon('heroicon-o-table-cells')
                    ->color('primary')
                    ->url(fn(ExamSession $r) => Pages\LaporanNilai::getUrl(['record' => $r->id])),

                Tables\Actions\Action::make('laporan_kehadiran')
                    ->label('Kehadiran')
                    ->icon('heroicon-o-users')
                    ->color('info')
                    ->url(fn(ExamSession $r) => Pages\LaporanKehadiran::getUrl(['record' => $r->id])),

                Tables\Actions\Action::make('statistik_soal')
                    ->label('Statistik Soal')
                    ->icon('heroicon-o-chart-pie')
                    ->color('gray')
                    ->url(fn(ExamSession $r) => Pages\StatistikSoal::getUrl(['record' => $r->id])),

                Tables\Actions\Action::make('rekap_kecurangan')
                    ->label('Kecurangan')
                    ->icon('heroicon-o-shield-exclamation')
                    ->color('danger')
                    ->url(fn(ExamSession $r) => Pages\LaporanKecurangan::getUrl(['record' => $r->id])),

                Tables\Actions\Action::make('analisis_ulangan')
                    ->label('Analisis Ulangan')
                    ->icon('heroicon-o-document-chart-bar')
                    ->color('warning')
                    ->url(fn(ExamSession $r) => route('analisis.index', $r->id))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'          => Pages\ListLaporan::route('/'),
            'nilai'          => Pages\LaporanNilai::route('/{record}/nilai'),
            'kehadiran'      => Pages\LaporanKehadiran::route('/{record}/kehadiran'),
            'statistik-soal' => Pages\StatistikSoal::route('/{record}/statistik-soal'),
            'komparasi'      => Pages\KomparasiSesi::route('/komparasi'),
            'kecurangan'     => Pages\LaporanKecurangan::route('/{record}/kecurangan'),
        ];
    }
}
