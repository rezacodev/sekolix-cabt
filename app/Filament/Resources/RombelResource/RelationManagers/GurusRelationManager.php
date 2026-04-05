<?php

namespace App\Filament\Resources\RombelResource\RelationManagers;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class GurusRelationManager extends RelationManager
{
    protected static string $relationship = 'gurus';

    protected static ?string $title = 'Guru Pengampu';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Guru')
                    ->searchable(),
                Tables\Columns\TextColumn::make('username')
                    ->label('Username')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email'),
                Tables\Columns\IconColumn::make('aktif')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Tambah Guru')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(
                        fn($query) => $query->where('level', User::LEVEL_GURU)->where('aktif', true)
                    ),
            ])
            ->actions([
                Tables\Actions\DetachAction::make()->label('Cabut'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()->label('Cabut Terpilih'),
                ]),
            ]);
    }
}
