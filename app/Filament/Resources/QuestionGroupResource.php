<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuestionGroupResource\Pages;
use App\Filament\Resources\QuestionGroupResource\RelationManagers;
use App\Models\QuestionGroup;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class QuestionGroupResource extends Resource
{
  protected static ?string $model = QuestionGroup::class;

  protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

  protected static ?string $navigationLabel = 'Grup Soal (Stimulus)';

  protected static ?string $navigationGroup = 'Bank Soal';

  protected static bool $shouldRegisterNavigation = false;

  protected static ?int $navigationSort = 10;

  protected static ?string $modelLabel = 'Grup Soal';

  protected static ?string $pluralModelLabel = 'Grup Soal (Stimulus)';

  public static function canEdit(Model $record): bool
  {
    $user = Auth::user();
    if ($user->level === User::LEVEL_GURU) {
      return $record->created_by === $user->id;
    }
    return $user->level >= User::LEVEL_ADMIN;
  }

  public static function canDelete(Model $record): bool
  {
    $user = Auth::user();
    if ($user->level === User::LEVEL_GURU) {
      return $record->created_by === $user->id;
    }
    return $user->level >= User::LEVEL_ADMIN;
  }

  public static function canDeleteAny(): bool
  {
    return Auth::user()->level >= User::LEVEL_ADMIN;
  }

  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Section::make('Informasi Grup')
          ->schema([
            Forms\Components\TextInput::make('judul')
              ->label('Judul Stimulus')
              ->required()
              ->maxLength(255)
              ->columnSpanFull(),

            Forms\Components\Select::make('tipe_stimulus')
              ->label('Tipe Stimulus')
              ->options(QuestionGroup::TIPE_STIMULUS_LABELS)
              ->required()
              ->native(false)
              ->default('teks')
              ->live(),

            Forms\Components\Textarea::make('deskripsi')
              ->label('Deskripsi / Petunjuk')
              ->helperText('Instruksi untuk peserta (opsional)')
              ->rows(2)
              ->nullable()
              ->columnSpanFull(),
          ])->columns(2),

        Forms\Components\Section::make('Konten Stimulus')
          ->schema([
            // Teks & Tabel: RichEditor
            Forms\Components\RichEditor::make('konten')
              ->label('Isi Konten')
              ->required()
              ->toolbarButtons([
                'bold',
                'italic',
                'underline',
                'strike',
                'h2',
                'h3',
                'bulletList',
                'orderedList',
                'blockquote',
                'table',
                'link',
              ])
              ->columnSpanFull()
              ->visible(fn(Forms\Get $get) => in_array($get('tipe_stimulus'), ['teks', 'tabel'])),

            // Gambar, Audio, Video: URL input
            Forms\Components\TextInput::make('konten')
              ->label('URL Media')
              ->helperText('URL langsung ke file gambar/audio/video, atau embed URL (YouTube, dst.)')
              ->required()
              ->url()
              ->columnSpanFull()
              ->visible(fn(Forms\Get $get) => in_array($get('tipe_stimulus'), ['gambar', 'audio', 'video'])),
          ]),
      ]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('id')
          ->label('ID')
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),

        Tables\Columns\TextColumn::make('judul')
          ->label('Judul Stimulus')
          ->searchable()
          ->limit(60),

        Tables\Columns\BadgeColumn::make('tipe_stimulus')
          ->label('Tipe')
          ->formatStateUsing(fn($state) => QuestionGroup::TIPE_STIMULUS_LABELS[$state] ?? $state)
          ->colors([
            'primary' => 'teks',
            'info'    => 'gambar',
            'warning' => 'audio',
            'danger'  => 'video',
            'success' => 'tabel',
          ]),

        Tables\Columns\TextColumn::make('questions_count')
          ->label('Jumlah Soal')
          ->counts('questions')
          ->sortable(),

        Tables\Columns\TextColumn::make('creator.name')
          ->label('Dibuat Oleh')
          ->placeholder('—')
          ->toggleable(),

        Tables\Columns\TextColumn::make('created_at')
          ->label('Dibuat')
          ->dateTime('d M Y')
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        Tables\Filters\SelectFilter::make('tipe_stimulus')
          ->label('Tipe Stimulus')
          ->options(QuestionGroup::TIPE_STIMULUS_LABELS),
      ])
      ->actions([
        Tables\Actions\EditAction::make(),
        Tables\Actions\DeleteAction::make()
          ->requiresConfirmation()
          ->successNotificationTitle('Grup soal berhasil dihapus'),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DeleteBulkAction::make(),
        ]),
      ])
      ->defaultSort('id', 'desc');
  }

  public static function getRelations(): array
  {
    return [
      RelationManagers\QuestionGroupQuestionsRelationManager::class,
    ];
  }

  public static function getPages(): array
  {
    return [
      'index'  => Pages\ListQuestionGroups::route('/'),
      'create' => Pages\CreateQuestionGroup::route('/create'),
      'edit'   => Pages\EditQuestionGroup::route('/{record}/edit'),
    ];
  }
}
