<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CurriculumStandardResource\Pages;
use App\Models\CurriculumStandard;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
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

    public static function canCreate(): bool
    {
        return Auth::user()->level >= User::LEVEL_ADMIN;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return Auth::user()->level >= User::LEVEL_ADMIN;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return Auth::user()->level >= User::LEVEL_ADMIN && $record->questions()->count() === 0;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identitas Standar')
                ->schema([
                    Forms\Components\TextInput::make('kode')
                        ->label('Kode KD/CP')
                        ->required()
                        ->maxLength(50)
                        ->placeholder('mis. 3.1, CP.BIN.10.1'),

                    Forms\Components\Select::make('kurikulum')
                        ->label('Kurikulum')
                        ->options(CurriculumStandard::KURIKULUM_LABELS)
                        ->required()
                        ->native(false),

                    Forms\Components\Select::make('jenjang')
                        ->label('Jenjang')
                        ->options(CurriculumStandard::JENJANG_LABELS)
                        ->required()
                        ->native(false),

                    Forms\Components\TextInput::make('mata_pelajaran')
                        ->label('Mata Pelajaran')
                        ->required()
                        ->maxLength(100)
                        ->placeholder('mis. Bahasa Indonesia'),

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
                        ->helperText('Tingkat kognitif default KD ini (dapat di-override di soal)'),
                ])->columns(3),

            Forms\Components\Section::make('Deskripsi')
                ->schema([
                    Forms\Components\Textarea::make('nama')
                        ->label('Deskripsi / Rumusan KD/CP')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('mata_pelajaran')
            ->columns([
                Tables\Columns\TextColumn::make('kode')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('mata_pelajaran')
                    ->label('Mapel')
                    ->searchable()
                    ->sortable(),

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
                    ->sortable(),

                Tables\Columns\TextColumn::make('tingkat_kognitif')
                    ->label('Bloom')
                    ->badge()
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

                Tables\Filters\SelectFilter::make('mata_pelajaran')
                    ->label('Mata Pelajaran')
                    ->options(fn() => CurriculumStandard::distinct()->pluck('mata_pelajaran', 'mata_pelajaran'))
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
                    }),
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
