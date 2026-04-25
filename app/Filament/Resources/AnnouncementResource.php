<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnnouncementResource\Pages;
use App\Models\Announcement;
use App\Models\Rombel;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class AnnouncementResource extends Resource
{
  protected static ?string $model = Announcement::class;

  protected static ?string $navigationIcon  = 'heroicon-o-megaphone';
  protected static ?string $navigationLabel = 'Pengumuman';
  protected static ?string $navigationGroup = 'Pengaturan';
  protected static bool   $shouldRegisterNavigation = false;
  protected static ?int    $navigationSort  = 90;
  protected static ?string $modelLabel      = 'Pengumuman';
  protected static ?string $pluralModelLabel = 'Daftar Pengumuman';

  public static function canViewAny(): bool
  {
    return Auth::user()?->level >= User::LEVEL_GURU;
  }

  public static function form(Form $form): Form
  {
    return $form->schema([
      Forms\Components\TextInput::make('judul')
        ->label('Judul')
        ->required()
        ->maxLength(200)
        ->columnSpanFull(),

      Forms\Components\RichEditor::make('isi')
        ->label('Isi Pengumuman')
        ->required()
        ->toolbarButtons(['bold', 'italic', 'underline', 'bulletList', 'orderedList', 'link', 'undo', 'redo'])
        ->columnSpanFull(),

      Forms\Components\Select::make('tipe')
        ->label('Tipe')
        ->options(Announcement::TIPE_LABELS)
        ->default(Announcement::TIPE_INFO)
        ->required()
        ->native(false),

      Forms\Components\Select::make('target')
        ->label('Target Penerima')
        ->options([
          Announcement::TARGET_SEMUA      => 'Semua Peserta',
          Announcement::TARGET_PER_ROMBEL => 'Per Rombel',
        ])
        ->default(Announcement::TARGET_SEMUA)
        ->required()
        ->native(false)
        ->live(),

      Forms\Components\Select::make('rombel_id')
        ->label('Rombel')
        ->options(fn() => Rombel::where('aktif', true)->orderBy('nama')->pluck('nama', 'id'))
        ->searchable()
        ->native(false)
        ->visible(fn(Get $get) => $get('target') === Announcement::TARGET_PER_ROMBEL)
        ->required(fn(Get $get) => $get('target') === Announcement::TARGET_PER_ROMBEL),

      Forms\Components\DateTimePicker::make('tanggal_mulai')
        ->label('Tampilkan Mulai')
        ->nullable()
        ->native(false)
        ->seconds(false),

      Forms\Components\DateTimePicker::make('tanggal_selesai')
        ->label('Tampilkan Hingga')
        ->nullable()
        ->native(false)
        ->seconds(false),

      Forms\Components\Toggle::make('aktif')
        ->label('Aktif')
        ->default(true)
        ->columnSpanFull(),
    ])->columns(2);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('judul')
          ->label('Judul')
          ->searchable()
          ->limit(60),

        Tables\Columns\TextColumn::make('tipe')
          ->label('Tipe')
          ->badge()
          ->color(fn($state) => match ($state) {
            Announcement::TIPE_WARNING => 'warning',
            Announcement::TIPE_PENTING => 'danger',
            default                    => 'info',
          })
          ->formatStateUsing(fn($state) => Announcement::TIPE_LABELS[$state] ?? $state),

        Tables\Columns\TextColumn::make('target')
          ->label('Target')
          ->formatStateUsing(fn($state) => $state === 'per_rombel' ? 'Per Rombel' : 'Semua'),

        Tables\Columns\TextColumn::make('tanggal_mulai')
          ->label('Mulai')
          ->dateTime('d M Y, H:i')
          ->placeholder('—'),

        Tables\Columns\TextColumn::make('tanggal_selesai')
          ->label('Selesai')
          ->dateTime('d M Y, H:i')
          ->placeholder('—'),

        Tables\Columns\ToggleColumn::make('aktif')
          ->label('Aktif'),
      ])
      ->defaultSort('created_at', 'desc')
      ->filters([
        Tables\Filters\TernaryFilter::make('aktif')->label('Status Aktif'),
        Tables\Filters\SelectFilter::make('tipe')
          ->label('Tipe')
          ->options(Announcement::TIPE_LABELS),
      ])
      ->actions([
        Tables\Actions\EditAction::make(),
        Tables\Actions\DeleteAction::make()
          ->successNotificationTitle('Pengumuman berhasil dihapus'),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DeleteBulkAction::make(),
        ]),
      ]);
  }

  public static function getPages(): array
  {
    return [
      'index'  => Pages\ListAnnouncements::route('/'),
      'create' => Pages\CreateAnnouncement::route('/create'),
      'edit'   => Pages\EditAnnouncement::route('/{record}/edit'),
    ];
  }
}
