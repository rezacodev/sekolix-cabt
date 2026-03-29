<?php

namespace App\Filament\Resources\RombelResource\RelationManagers;

use App\Models\Rombel;
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
        // Form tidak dipakai: peserta dikelola via UserResource
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
        ]);
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
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('pindah_rombel')
                    ->label('Pindah Rombel')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('rombel_id')
                            ->label('Rombel Tujuan')
                            ->options(
                                Rombel::where('aktif', true)
                                    ->orderBy('nama')
                                    ->get()
                                    ->mapWithKeys(fn ($r) => [$r->id => "{$r->kode} — {$r->nama}"])
                            )
                            ->required()
                            ->native(false),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update(['rombel_id' => $data['rombel_id']]);
                    }),
            ])
            ->bulkActions([]);
    }
}
