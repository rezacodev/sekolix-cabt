<?php

namespace App\Filament\Resources\QuestionGroupResource\RelationManagers;

use App\Models\Question;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuestionGroupQuestionsRelationManager extends RelationManager
{
  protected static string $relationship = 'questions';
  protected static ?string $title = 'Soal dalam Grup';
  protected static ?string $recordTitleAttribute = 'teks_soal';

  public function form(Form $form): Form
  {
    return $form->schema([
      Forms\Components\TextInput::make('group_urutan')
        ->label('Urutan dalam Grup')
        ->numeric()
        ->default(0),
    ]);
  }

  public function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('teks_soal')
      ->reorderable('group_urutan')
      ->defaultSort('group_urutan')
      ->columns([
        Tables\Columns\TextColumn::make('group_urutan')
          ->label('#')
          ->width(40),

        Tables\Columns\BadgeColumn::make('tipe')
          ->label('Tipe')
          ->formatStateUsing(fn($state) => Question::TIPE_LABELS[$state] ?? $state)
          ->colors([
            'primary'   => 'PG',
            'info'      => 'PG_BOBOT',
            'warning'   => 'PGJ',
            'success'   => 'JODOH',
            'secondary' => 'ISIAN',
            'danger'    => 'URAIAN',
          ]),

        Tables\Columns\TextColumn::make('teks_soal')
          ->label('Teks Soal')
          ->html()
          ->limit(80),

        Tables\Columns\TextColumn::make('category.nama')
          ->label('Kategori')
          ->placeholder('—'),

        Tables\Columns\BadgeColumn::make('tingkat_kesulitan')
          ->label('Kesulitan')
          ->formatStateUsing(fn($state) => Question::KESULITAN_LABELS[$state] ?? $state)
          ->colors([
            'success' => 'mudah',
            'warning' => 'sedang',
            'danger'  => 'sulit',
          ]),

        Tables\Columns\TextColumn::make('bobot')
          ->label('Bobot'),
      ])
      ->headerActions([
        // Attach existing question to this group
        Tables\Actions\AttachAction::make()
          ->label('Tambah Soal ke Grup')
          ->icon('heroicon-o-plus')
          ->color('primary')
          ->preloadRecordSelect()
          ->recordSelectOptionsQuery(fn(Builder $query) => $query->where('aktif', true)->whereNull('question_group_id'))
          ->recordSelectSearchColumns(['teks_soal'])
          ->after(function ($livewire) {
            // Set group_urutan = current max + 1
            $group    = $livewire->getOwnerRecord();
            $maxUrutan = Question::where('question_group_id', $group->id)->max('group_urutan') ?? 0;
            $last      = Question::where('question_group_id', $group->id)
              ->orderByDesc('id')
              ->first();
            if ($last && (int) $last->group_urutan === 0) {
              $last->update(['group_urutan' => $maxUrutan]);
            }
          }),
      ])
      ->actions([
        Tables\Actions\EditAction::make()
          ->label('Urutan'),
        Tables\Actions\DetachAction::make()
          ->label('Lepas dari Grup')
          ->after(function (Question $record) {
            $record->update(['group_urutan' => null]);
          }),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DetachBulkAction::make()
            ->label('Lepas Terpilih dari Grup')
            ->after(function (\Illuminate\Database\Eloquent\Collection $records) {
              foreach ($records as $record) {
                $record->update(['group_urutan' => null]);
              }
            }),
        ]),
      ]);
  }
}
