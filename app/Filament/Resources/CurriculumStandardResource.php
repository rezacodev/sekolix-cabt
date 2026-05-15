<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CurriculumStandardResource\Pages;
use App\Models\CurriculumStandard;
use App\Models\MataPelajaran;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CurriculumStandardResource extends Resource
{
    protected static ?string $model = CurriculumStandard::class;

    protected static ?string $navigationIcon  = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'KD / CP';
    protected static ?string $navigationGroup = 'Kurikulum';
    protected static ?int    $navigationSort  = 1;
    protected static ?string $modelLabel       = 'Standar Kurikulum';
    protected static ?string $pluralModelLabel = 'KD / CP';

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return $user && $user->level === User::LEVEL_GURU;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->level === User::LEVEL_GURU;
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->level === User::LEVEL_GURU;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = Auth::user();
        return $user && $user->level === User::LEVEL_GURU && $record->created_by === $user->id;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = Auth::user();
        return $user && $user->level === User::LEVEL_GURU && $record->created_by === $user->id && $record->questions()->count() === 0;
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()?->level === User::LEVEL_GURU;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user && $user->level === User::LEVEL_GURU) {
            return $query->where('created_by', $user->id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            // ── Identitas ─────────────────────────────────────────────────────
            Forms\Components\Section::make('Identitas KD/CP')
                ->schema([
                    Forms\Components\Select::make('mata_pelajaran_id')
                        ->label('Mata Pelajaran')
                        ->helperText('Belum ada? Tambahkan di menu Kurikulum → Mata Pelajaran')
                        ->options(fn() => MataPelajaran::where('aktif', true)->orderBy('nama')->pluck('nama', 'id'))
                        ->searchable()
                        ->required()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set) {
                            if ($state) {
                                $mapel = MataPelajaran::find($state);
                                if ($mapel?->jenjang && $mapel->jenjang !== 'Umum') {
                                    $set('jenjang', $mapel->jenjang);
                                }
                            }
                        }),

                    Forms\Components\Select::make('kurikulum')
                        ->label('Kurikulum')
                        ->options(CurriculumStandard::KURIKULUM_LABELS)
                        ->required()
                        ->native(false),

                    Forms\Components\TextInput::make('kode')
                        ->label('Kode KD/CP')
                        ->required()
                        ->maxLength(50)
                        ->placeholder('mis. 3.1, CP.BIN.10.1'),

                    Forms\Components\Select::make('jenjang')
                        ->label('Jenjang')
                        ->options(CurriculumStandard::JENJANG_LABELS)
                        ->required()
                        ->native(false)
                        ->helperText('Otomatis terisi saat mapel dipilih'),
                ])->columns(2),

            // ── Deskripsi ─────────────────────────────────────────────────────
            Forms\Components\Section::make('Deskripsi KD/CP')
                ->schema([
                    Forms\Components\Textarea::make('nama')
                        ->label('Rumusan KD/CP / Indikator')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            // ── Konteks Kisi-kisi ─────────────────────────────────────────────
            Forms\Components\Section::make('Konteks Kisi-kisi Formal')
                ->description('Digunakan untuk auto-fill baris kisi-kisi saat KD/CP ini dipilih')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('capaian_pembelajaran')
                        ->label('Capaian Pembelajaran')
                        ->maxLength(200)
                        ->nullable()
                        ->placeholder('mis. Algoritma dan Pemrograman'),

                    Forms\Components\TextInput::make('materi')
                        ->label('Materi')
                        ->maxLength(200)
                        ->nullable()
                        ->placeholder('mis. Scratch dan Blockly'),
                ])->columns(2),

            // ── Detail Tambahan ───────────────────────────────────────────────
            Forms\Components\Section::make('Detail Tambahan')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('kelas')
                        ->label('Kelas / Semester')
                        ->maxLength(20)
                        ->placeholder('mis. X, XI, 10')
                        ->nullable(),

                    Forms\Components\Select::make('tingkat_kognitif')
                        ->label('Tingkat Kognitif (Bloom)')
                        ->options(CurriculumStandard::BLOOM_LABELS)
                        ->nullable()
                        ->native(false)
                        ->helperText('Tingkat kognitif default KD ini'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('mata_pelajaran')
            ->groups([
                Tables\Grouping\Group::make('mata_pelajaran')
                    ->label('Mata Pelajaran')
                    ->collapsible(),
            ])
            ->defaultGroup('mata_pelajaran')
            ->columns([
                Tables\Columns\TextColumn::make('kode')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('mata_pelajaran')
                    ->label('Mapel')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('nama')
                    ->label('Deskripsi')
                    ->limit(60)
                    ->searchable()
                    ->tooltip(fn($record) => $record->nama),

                Tables\Columns\TextColumn::make('jenjang')
                    ->label('Jenjang')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'SD'  => 'primary',
                        'SMP' => 'info',
                        'SMA' => 'success',
                        'SMK' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('kurikulum')
                    ->label('Kurikulum')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'K13'           => 'gray',
                        'Merdeka'       => 'success',
                        'Internasional' => 'info',
                        default         => 'gray',
                    }),

                Tables\Columns\TextColumn::make('kelas')
                    ->label('Kelas')
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('tingkat_kognitif')
                    ->label('Bloom')
                    ->badge()
                    ->placeholder('—')
                    ->color(fn($state) => match ($state) {
                        'C1', 'C2' => 'gray',
                        'C3'       => 'info',
                        'C4'       => 'warning',
                        'C5', 'C6' => 'danger',
                        default    => 'gray',
                    }),

                Tables\Columns\TextColumn::make('questions_count')
                    ->label('Jml Soal')
                    ->counts('questions')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kurikulum')
                    ->options(CurriculumStandard::KURIKULUM_LABELS)
                    ->label('Kurikulum'),

                Tables\Filters\SelectFilter::make('jenjang')
                    ->options(CurriculumStandard::JENJANG_LABELS)
                    ->label('Jenjang'),

                Tables\Filters\SelectFilter::make('mata_pelajaran_id')
                    ->label('Mata Pelajaran')
                    ->options(fn() => MataPelajaran::where('aktif', true)->orderBy('nama')->pluck('nama', 'id'))
                    ->searchable(),

                Tables\Filters\SelectFilter::make('tingkat_kognitif')
                    ->label('Bloom Level')
                    ->options(CurriculumStandard::BLOOM_LABELS),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Tables\Actions\DeleteAction $action, $record) {
                        if ($record->questions()->count() > 0) {
                            Notification::make()
                                ->title('Tidak bisa dihapus')
                                ->body('Standar ini masih dipakai oleh ' . $record->questions()->count() . ' soal.')
                                ->danger()
                                ->send();
                            $action->cancel();
                        }
                    })
                    ->successNotificationTitle('Standar kurikulum berhasil dihapus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCurriculumStandards::route('/'),
            'create' => Pages\CreateCurriculumStandard::route('/create'),
            'edit'   => Pages\EditCurriculumStandard::route('/{record}/edit'),
        ];
    }
}
