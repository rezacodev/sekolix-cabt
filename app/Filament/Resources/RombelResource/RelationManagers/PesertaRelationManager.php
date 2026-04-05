<?php

namespace App\Filament\Resources\RombelResource\RelationManagers;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PesertaRelationManager extends RelationManager
{
    protected static string $relationship = 'peserta';

    protected static ?string $title = 'Daftar Peserta';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('nomor_peserta')
                    ->label('No. Peserta')
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('username')
                    ->label('Username')
                    ->placeholder('-'),
                Tables\Columns\IconColumn::make('aktif')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->defaultSort('nomor_peserta')
            ->filters([
                Tables\Filters\TernaryFilter::make('aktif')
                    ->label('Status Aktif')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Tambah Peserta')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(
                        fn($query) => $query->where('level', User::LEVEL_PESERTA)->where('aktif', true)
                    ),
            ])
            ->actions([
                Tables\Actions\DetachAction::make()->label('Keluarkan'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()->label('Keluarkan Terpilih'),
                ]),
            ]);
    }
}
