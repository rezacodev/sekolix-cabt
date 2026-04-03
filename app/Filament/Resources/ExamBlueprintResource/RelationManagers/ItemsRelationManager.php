<?php

namespace App\Filament\Resources\ExamBlueprintResource\RelationManagers;

use App\Models\Category;
use App\Models\CurriculumStandard;
use App\Models\Question;
use App\Models\Tag;
use Filament\Forms;
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
        return $form->schema([
            Forms\Components\Select::make('category_id')
                ->label('Kategori Soal')
                ->options(fn() => Category::pluck('nama', 'id'))
                ->searchable()
                ->nullable()
                ->native(false),

            Forms\Components\Select::make('standard_id')
                ->label('KD / CP')
                ->options(fn() => CurriculumStandard::query()
                    ->orderBy('mata_pelajaran')->orderBy('kode')
                    ->get()
                    ->mapWithKeys(fn($s) => [$s->id => "[{$s->kode}] {$s->mata_pelajaran} — {$s->nama}"]))
                ->searchable()
                ->nullable()
                ->native(false),

            Forms\Components\Select::make('tipe_soal')
                ->label('Tipe Soal')
                ->options(Question::TIPE_LABELS)
                ->nullable()
                ->native(false),

            Forms\Components\Select::make('tingkat_kesulitan')
                ->label('Kesulitan')
                ->options(Question::KESULITAN_LABELS)
                ->nullable()
                ->native(false),

            Forms\Components\Select::make('bloom_level')
                ->label('Level Bloom')
                ->options(CurriculumStandard::BLOOM_LABELS)
                ->nullable()
                ->native(false),

            Forms\Components\Select::make('tag_id')
                ->label('Tag')
                ->options(fn() => Tag::pluck('nama', 'id'))
                ->searchable()
                ->nullable()
                ->native(false),

            Forms\Components\TextInput::make('jumlah_soal')
                ->label('Jumlah Soal')
                ->numeric()
                ->required()
                ->minValue(1)
                ->default(5),

            Forms\Components\TextInput::make('bobot_per_soal')
                ->label('Bobot per Soal')
                ->numeric()
                ->default(1)
                ->minValue(0)
                ->step(0.5),

            Forms\Components\TextInput::make('urutan')
                ->label('Urutan')
                ->numeric()
                ->default(fn() => ($this->getOwnerRecord()->items()->max('urutan') ?? 0) + 1)
                ->required(),
        ])->columns(3);
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

                Tables\Columns\TextColumn::make('category.nama')
                    ->label('Kategori')
                    ->placeholder('Semua')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('standard.kode')
                    ->label('KD/CP')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('tipe_soal')
                    ->label('Tipe')
                    ->placeholder('Semua')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('tingkat_kesulitan')
                    ->label('Kesulitan')
                    ->placeholder('Semua')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'mudah' => 'success',
                        'sedang' => 'warning',
                        'sulit' => 'danger',
                        default => 'gray',
                    }),

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
                    }),

                Tables\Columns\TextColumn::make('tag.nama')
                    ->label('Tag')
                    ->placeholder('—')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('jumlah_soal')
                    ->label('Jml')
                    ->sortable(),

                Tables\Columns\TextColumn::make('bobot_per_soal')
                    ->label('Bobot')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Tambah Baris'),
            ])
            ->bulkActions([]);
    }
}
