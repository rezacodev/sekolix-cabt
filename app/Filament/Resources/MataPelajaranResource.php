<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MataPelajaranResource\Pages;
use App\Filament\Resources\MataPelajaranResource\RelationManagers;
use App\Models\MataPelajaran;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MataPelajaranResource extends Resource
{
  protected static ?string $model = MataPelajaran::class;

  protected static ?string $navigationIcon  = 'heroicon-o-book-open';
  protected static ?string $navigationLabel = 'Mata Pelajaran';
  protected static ?string $navigationGroup = 'Kurikulum';
  protected static ?int    $navigationSort  = 0;
  protected static ?string $modelLabel       = 'Mata Pelajaran';
  protected static ?string $pluralModelLabel = 'Mata Pelajaran';

  public static function canViewAny(): bool
  {
    return Auth::user()?->level >= User::LEVEL_ADMIN;
  }

  public static function shouldRegisterNavigation(): bool
  {
    return Auth::user()?->level >= User::LEVEL_ADMIN;
  }

  public static function canCreate(): bool
  {
    return Auth::user()?->level >= User::LEVEL_ADMIN;
  }

  public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
  {
    return Auth::user()?->level >= User::LEVEL_ADMIN;
  }

  public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
  {
    return Auth::user()?->level >= User::LEVEL_ADMIN;
  }

  public static function canDeleteAny(): bool
  {
    return Auth::user()?->level >= User::LEVEL_ADMIN;
  }

  public static function form(Form $form): Form
  {
    return $form->schema([
      Forms\Components\Section::make('Identitas Mata Pelajaran')
        ->schema([
          Forms\Components\TextInput::make('nama')
            ->label('Nama Mata Pelajaran')
            ->required()
            ->maxLength(100)
            ->unique(ignoreRecord: true)
            ->placeholder('mis. Matematika, Bahasa Indonesia'),

          Forms\Components\TextInput::make('kode')
            ->label('Kode')
            ->maxLength(20)
            ->nullable()
            ->placeholder('mis. MTK, BIN, IPA'),

          Forms\Components\Select::make('jenjang')
            ->label('Jenjang')
            ->options(MataPelajaran::JENJANG_LABELS)
            ->default('Umum')
            ->required()
            ->native(false),

          Forms\Components\Toggle::make('aktif')
            ->label('Aktif')
            ->default(true)
            ->helperText('Non-aktifkan untuk menyembunyikan dari pilihan tanpa menghapus data.'),

          Forms\Components\Textarea::make('keterangan')
            ->label('Keterangan')
            ->rows(2)
            ->nullable()
            ->columnSpanFull()
            ->placeholder('Deskripsi singkat mata pelajaran (opsional)'),
        ])->columns(3),
    ]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->defaultSort('nama')
      ->columns([
        Tables\Columns\TextColumn::make('nama')
          ->label('Nama Mata Pelajaran')
          ->searchable()
          ->sortable()
          ->weight('semibold'),

        Tables\Columns\TextColumn::make('kode')
          ->label('Kode')
          ->searchable()
          ->sortable()
          ->badge()
          ->color('gray')
          ->placeholder('—'),

        Tables\Columns\TextColumn::make('jenjang')
          ->label('Jenjang')
          ->badge()
          ->color(fn($state) => match ($state) {
            'SD'   => 'primary',
            'SMP'  => 'info',
            'SMA'  => 'success',
            'SMK'  => 'warning',
            default => 'gray',
          })
          ->sortable(),

        Tables\Columns\TextColumn::make('curriculum_standards_count')
          ->label('Jml KD/CP')
          ->counts('curriculumStandards')
          ->sortable(),

        Tables\Columns\IconColumn::make('aktif')
          ->label('Aktif')
          ->boolean()
          ->sortable(),

        Tables\Columns\TextColumn::make('keterangan')
          ->label('Keterangan')
          ->limit(50)
          ->placeholder('—')
          ->toggleable(isToggledHiddenByDefault: true),

        Tables\Columns\TextColumn::make('creator.name')
          ->label('Dibuat Oleh')
          ->placeholder('—')
          ->toggleable(isToggledHiddenByDefault: true),

        Tables\Columns\TextColumn::make('created_at')
          ->label('Dibuat')
          ->date('d M Y')
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('jenjang')
          ->label('Jenjang')
          ->options(MataPelajaran::JENJANG_LABELS),

        Tables\Filters\TernaryFilter::make('aktif')
          ->label('Status')
          ->placeholder('Semua')
          ->trueLabel('Aktif')
          ->falseLabel('Non-aktif'),
      ])
      ->actions([
        Tables\Actions\ViewAction::make(),
        Tables\Actions\EditAction::make(),
        Tables\Actions\DeleteAction::make()
          ->successNotificationTitle('Mata pelajaran berhasil dihapus'),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DeleteBulkAction::make(),
        ]),
      ]);
  }

  public static function getRelations(): array
  {
    return [
      RelationManagers\CurriculumStandardsRelationManager::class,
    ];
  }

  public static function getPages(): array
  {
    return [
      'index'  => Pages\ListMataPelajaran::route('/'),
      'create' => Pages\CreateMataPelajaran::route('/create'),
      'view'   => Pages\ViewMataPelajaran::route('/{record}'),
      'edit'   => Pages\EditMataPelajaran::route('/{record}/edit'),
    ];
  }
}
