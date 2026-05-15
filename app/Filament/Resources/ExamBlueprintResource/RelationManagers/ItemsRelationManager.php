<?php

namespace App\Filament\Resources\ExamBlueprintResource\RelationManagers;

use App\Filament\Resources\ExamBlueprintResource;
use App\Models\CurriculumStandard;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Baris Kisi-kisi';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('urutan')
            ->defaultSort('urutan')
            ->columns([
                Tables\Columns\TextColumn::make('urutan')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('capaian_pembelajaran')
                    ->label('Capaian Pembelajaran')
                    ->limit(35)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('materi')
                    ->label('Materi')
                    ->limit(30)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('indikator')
                    ->label('Indikator')
                    ->limit(50)
                    ->placeholder('—')
                    ->tooltip(fn($record) => $record->indikator),

                Tables\Columns\TextColumn::make('tipe_soal')
                    ->label('Tipe')
                    ->placeholder('Semua')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('jumlah_soal')
                    ->label('Jml')
                    ->sortable(),

                Tables\Columns\TextColumn::make('nomor_soal')
                    ->label('No. Soal')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('category.nama')
                    ->label('Kategori')
                    ->placeholder('Semua')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('standard.kode')
                    ->label('KD/CP')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tingkat_kesulitan')
                    ->label('Kesulitan')
                    ->placeholder('Semua')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'mudah'  => 'success',
                        'sedang' => 'warning',
                        'sulit'  => 'danger',
                        default  => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('bloom_level')
                    ->label('Bloom')
                    ->placeholder('—')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'C1', 'C2' => 'gray',
                        'C3'       => 'info',
                        'C4'       => 'warning',
                        'C5', 'C6' => 'danger',
                        default    => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('bobot_per_soal')
                    ->label('Bobot')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tag.nama')
                    ->label('Tag')
                    ->placeholder('—')
                    ->badge()
                    ->color('primary')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->url(fn($record) => ExamBlueprintResource::getUrl(
                        'items.edit',
                        ['record' => $record->blueprint_id, 'item' => $record->id]
                    )),
                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create')
                    ->label('Tambah Baris')
                    ->icon('heroicon-o-plus')
                    ->url(fn() => ExamBlueprintResource::getUrl(
                        'items.create',
                        ['record' => $this->getOwnerRecord()->id]
                    )),
            ])
            ->bulkActions([]);
    }
}
