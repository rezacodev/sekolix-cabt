<?php

namespace App\Filament\Resources\MataPelajaranResource\RelationManagers;

use App\Models\CurriculumStandard;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CurriculumStandardsRelationManager extends RelationManager
{
    protected static string $relationship = 'curriculumStandards';

    protected static ?string $title = 'KD / CP';

    public function form(Form $form): Form
    {
        $mapel = $this->getOwnerRecord();

        return $form->schema([
            // ── Identitas ─────────────────────────────────────────────────────
            Forms\Components\Section::make('Identitas KD/CP')
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
                        ->native(false)
                        ->default(fn() => ($mapel->jenjang !== 'Umum') ? $mapel->jenjang : null),

                    Forms\Components\TextInput::make('kelas')
                        ->label('Kelas / Semester')
                        ->maxLength(20)
                        ->nullable()
                        ->placeholder('mis. X, XI, 10'),
                ])->columns(2),

            // ── Deskripsi ─────────────────────────────────────────────────────
            Forms\Components\Section::make('Deskripsi KD/CP')
                ->schema([
                    Forms\Components\Textarea::make('nama')
                        ->label('Rumusan KD/CP / Indikator')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            // ── Konteks Kisi-kisi ─────────────────────────────────────────────
            Forms\Components\Section::make('Konteks Kisi-kisi Formal')
                ->description('Digunakan untuk auto-fill baris kisi-kisi saat KD/CP ini dipilih')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('capaian_pembelajaran')
                        ->label('Capaian Pembelajaran')
                        ->maxLength(200)
                        ->nullable()
                        ->placeholder('mis. Algoritma dan Pemrograman'),

                    Forms\Components\TextInput::make('materi')
                        ->label('Materi')
                        ->maxLength(200)
                        ->nullable()
                        ->placeholder('mis. Scratch dan Blockly'),

                    Forms\Components\Select::make('tingkat_kognitif')
                        ->label('Tingkat Kognitif (Bloom)')
                        ->options(CurriculumStandard::BLOOM_LABELS)
                        ->nullable()
                        ->native(false),
                ])->columns(3),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('kode')
            ->columns([
                Tables\Columns\TextColumn::make('kode')
                    ->label('Kode')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('nama')
                    ->label('Deskripsi / Rumusan')
                    ->limit(70)
                    ->searchable()
                    ->tooltip(fn($record) => $record->nama),

                Tables\Columns\TextColumn::make('kurikulum')
                    ->label('Kurikulum')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'K13'           => 'gray',
                        'Merdeka'       => 'success',
                        'Internasional' => 'info',
                        default         => 'gray',
                    }),

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

                Tables\Columns\TextColumn::make('kelas')
                    ->label('Kelas')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tingkat_kognitif')
                    ->label('Bloom')
                    ->badge()
                    ->placeholder('—')
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
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kurikulum')
                    ->options(CurriculumStandard::KURIKULUM_LABELS),

                Tables\Filters\SelectFilter::make('jenjang')
                    ->options(CurriculumStandard::JENJANG_LABELS),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah KD/CP')
                    ->mutateFormDataUsing(function (array $data): array {
                        $mapel = $this->getOwnerRecord();
                        $data['mata_pelajaran_id'] = $mapel->id;
                        $data['mata_pelajaran']    = $mapel->nama;
                        $data['created_by']        = auth()->id();
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Tables\Actions\DeleteAction $action, $record) {
                        if ($record->questions()->count() > 0) {
                            Notification::make()
                                ->title('Tidak bisa dihapus')
                                ->body('KD/CP ini masih dipakai oleh ' . $record->questions()->count() . ' soal.')
                                ->danger()
                                ->send();
                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([]);
    }
}
