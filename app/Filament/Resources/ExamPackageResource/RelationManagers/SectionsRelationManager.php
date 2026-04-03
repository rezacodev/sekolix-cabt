<?php

namespace App\Filament\Resources\ExamPackageResource\RelationManagers;

use App\Models\ExamSection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SectionsRelationManager extends RelationManager
{
  protected static string $relationship = 'sections';
  protected static ?string $title = 'Bagian (Seksi) Ujian';
  protected static ?string $recordTitleAttribute = 'nama';

  public function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\TextInput::make('nama')
          ->label('Nama Bagian')
          ->required()
          ->maxLength(100)
          ->columnSpanFull(),

        Forms\Components\TextInput::make('urutan')
          ->label('Urutan')
          ->numeric()
          ->required()
          ->minValue(1)
          ->default(fn() => $this->getOwnerRecord()->sections()->max('urutan') + 1),

        Forms\Components\TextInput::make('durasi_menit')
          ->label('Durasi (menit)')
          ->numeric()
          ->required()
          ->minValue(1)
          ->default(30),

        Forms\Components\TextInput::make('waktu_minimal_menit')
          ->label('Waktu Minimal Lanjut (menit)')
          ->numeric()
          ->required()
          ->minValue(0)
          ->default(0)
          ->helperText('0 = bisa lanjut kapan saja'),

        Forms\Components\Toggle::make('acak_soal')
          ->label('Acak Urutan Soal')
          ->default(false),

        Forms\Components\Toggle::make('acak_opsi')
          ->label('Acak Urutan Opsi')
          ->default(false),
      ]);
  }

  public function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('nama')
      ->reorderable('urutan')
      ->defaultSort('urutan')
      ->columns([
        Tables\Columns\TextColumn::make('urutan')
          ->label('#')
          ->width(40),

        Tables\Columns\TextColumn::make('nama')
          ->label('Nama Bagian')
          ->searchable()
          ->weight('semibold'),

        Tables\Columns\TextColumn::make('durasi_menit')
          ->label('Durasi')
          ->suffix(' mnt')
          ->badge()
          ->color('info'),

        Tables\Columns\TextColumn::make('waktu_minimal_menit')
          ->label('Min. Lanjut')
          ->suffix(' mnt')
          ->placeholder('—'),

        Tables\Columns\IconColumn::make('acak_soal')
          ->label('Acak Soal')
          ->boolean(),

        Tables\Columns\IconColumn::make('acak_opsi')
          ->label('Acak Opsi')
          ->boolean(),
      ])
      ->headerActions([
        Tables\Actions\CreateAction::make()
          ->mutateFormDataUsing(function (array $data): array {
            if (! isset($data['urutan']) || ! $data['urutan']) {
              $data['urutan'] = $this->getOwnerRecord()->sections()->max('urutan') + 1;
            }
            return $data;
          }),
      ])
      ->actions([
        Tables\Actions\EditAction::make(),
        Tables\Actions\DeleteAction::make(),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DeleteBulkAction::make(),
        ]),
      ]);
  }
}
