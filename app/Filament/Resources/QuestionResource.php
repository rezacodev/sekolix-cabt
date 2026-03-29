<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuestionResource\Pages;
use App\Models\Category;
use App\Models\Question;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Bank Soal';

    protected static ?string $navigationGroup = 'Bank Soal';

    protected static ?int $navigationSort = 11;

    protected static ?string $modelLabel = 'Soal';

    protected static ?string $pluralModelLabel = 'Bank Soal';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Soal')
                    ->schema([
                        Forms\Components\Select::make('tipe')
                            ->label('Tipe Soal')
                            ->options(Question::TIPE_LABELS)
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                // Clear child data when tipe changes
                                $set('options', []);
                                $set('matches', []);
                                $set('keywords', []);
                            }),

                        Forms\Components\Select::make('kategori_id')
                            ->label('Kategori')
                            ->options(fn() => Category::pluck('nama', 'id'))
                            ->searchable()
                            ->nullable()
                            ->native(false),

                        Forms\Components\Select::make('tingkat_kesulitan')
                            ->label('Tingkat Kesulitan')
                            ->options(Question::KESULITAN_LABELS)
                            ->default('sedang')
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('bobot')
                            ->label('Bobot Soal')
                            ->numeric()
                            ->default(1)
                            ->minValue(0)
                            ->maxValue(999.99)
                            ->step(0.5),

                        Forms\Components\Toggle::make('lock_position')
                            ->label('Kunci Posisi')
                            ->helperText('Soal ini tidak ikut diacak saat pengacakan paket')
                            ->default(false),

                        Forms\Components\Toggle::make('aktif')
                            ->label('Aktif')
                            ->default(true),
                    ])->columns(3),

                Forms\Components\Section::make('Teks Soal')
                    ->schema([
                        Forms\Components\RichEditor::make('teks_soal')
                            ->label('Teks Soal')
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
                                'codeBlock',
                                'link',
                            ])
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('penjelasan')
                            ->label('Pembahasan / Penjelasan Jawaban')
                            ->helperText('Ditampilkan saat peserta review setelah ujian')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                                'link',
                            ])
                            ->nullable()
                            ->columnSpanFull(),
                    ]),

                // === Opsi Jawaban (PG, PG_BOBOT, PGJ) ===
                Forms\Components\Section::make('Opsi Jawaban')
                    ->schema([
                        Forms\Components\Repeater::make('options')
                            ->label('')
                            ->relationship('options')
                            ->schema([
                                Forms\Components\TextInput::make('kode_opsi')
                                    ->label('Kode')
                                    ->required()
                                    ->maxLength(5)
                                    ->default(function (Forms\Components\Repeater $repeater) {
                                        $codes = ['A', 'B', 'C', 'D', 'E', 'F'];
                                        return $codes[count($repeater->getState())] ?? '';
                                    }),
                                Forms\Components\RichEditor::make('teks_opsi')
                                    ->label('Teks Opsi')
                                    ->required()
                                    ->toolbarButtons(['bold', 'italic', 'underline'])
                                    ->columnSpan(3),
                                Forms\Components\Toggle::make('is_correct')
                                    ->label('Benar')
                                    ->default(false),
                                Forms\Components\TextInput::make('bobot_persen')
                                    ->label('Bobot (%)')
                                    ->numeric()
                                    ->default(100)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->hidden(fn(Get $get, $record) => $get('../../tipe') !== Question::TIPE_PG_BOBOT),
                                Forms\Components\TextInput::make('urutan')
                                    ->label('Urutan')
                                    ->numeric()
                                    ->default(0)
                                    ->hidden(),
                            ])
                            ->columns(5)
                            ->addActionLabel('Tambah Opsi')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->minItems(2),
                    ])
                    ->visible(fn(Get $get) => in_array($get('tipe'), [
                        Question::TIPE_PG,
                        Question::TIPE_PG_BOBOT,
                        Question::TIPE_PGJ,
                    ])),

                // === Pasangan Jodoh (JODOH) ===
                Forms\Components\Section::make('Pasangan Menjodohkan')
                    ->schema([
                        Forms\Components\Repeater::make('matches')
                            ->label('')
                            ->relationship('matches')
                            ->schema([
                                Forms\Components\Textarea::make('premis')
                                    ->label('Premis')
                                    ->required()
                                    ->rows(2),
                                Forms\Components\Textarea::make('respon')
                                    ->label('Respon / Jawaban')
                                    ->required()
                                    ->rows(2),
                                Forms\Components\TextInput::make('urutan')
                                    ->label('Urutan')
                                    ->numeric()
                                    ->default(0)
                                    ->hidden(),
                            ])
                            ->columns(2)
                            ->addActionLabel('Tambah Pasangan')
                            ->reorderableWithButtons()
                            ->minItems(2),
                    ])
                    ->visible(fn(Get $get) => $get('tipe') === Question::TIPE_JODOH),

                // === Kata Kunci (ISIAN) ===
                Forms\Components\Section::make('Kata Kunci Jawaban')
                    ->description('Semua kata kunci berikut diterima sebagai jawaban benar (case-insensitive)')
                    ->schema([
                        Forms\Components\Repeater::make('keywords')
                            ->label('')
                            ->relationship('keywords')
                            ->schema([
                                Forms\Components\TextInput::make('keyword')
                                    ->label('Kata Kunci')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->addActionLabel('Tambah Kata Kunci')
                            ->minItems(1),
                    ])
                    ->visible(fn(Get $get) => $get('tipe') === Question::TIPE_ISIAN),
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

                Tables\Columns\BadgeColumn::make('tipe')
                    ->label('Tipe')
                    ->colors([
                        'info'    => Question::TIPE_PG,
                        'primary' => Question::TIPE_PG_BOBOT,
                        'success' => Question::TIPE_PGJ,
                        'warning' => Question::TIPE_JODOH,
                        'gray'    => Question::TIPE_ISIAN,
                        'danger'  => Question::TIPE_URAIAN,
                    ])
                    ->formatStateUsing(fn($state) => Question::TIPE_LABELS[$state] ?? $state),

                Tables\Columns\TextColumn::make('teks_soal')
                    ->label('Teks Soal')
                    ->html()
                    ->limit(80)
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('category.nama')
                    ->label('Kategori')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('tingkat_kesulitan')
                    ->label('Kesulitan')
                    ->colors([
                        'success' => 'mudah',
                        'warning' => 'sedang',
                        'danger'  => 'sulit',
                    ])
                    ->formatStateUsing(fn($state) => Question::KESULITAN_LABELS[$state] ?? $state),

                Tables\Columns\TextColumn::make('bobot')
                    ->label('Bobot')
                    ->sortable(),

                Tables\Columns\IconColumn::make('lock_position')
                    ->label('Kunci')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open'),

                Tables\Columns\ToggleColumn::make('aktif')
                    ->label('Aktif'),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipe')
                    ->label('Tipe Soal')
                    ->options(Question::TIPE_LABELS)
                    ->multiple(),

                Tables\Filters\SelectFilter::make('kategori_id')
                    ->label('Kategori')
                    ->options(fn() => Category::pluck('nama', 'id'))
                    ->searchable(),

                Tables\Filters\SelectFilter::make('tingkat_kesulitan')
                    ->label('Kesulitan')
                    ->options(Question::KESULITAN_LABELS),

                Tables\Filters\TernaryFilter::make('aktif')
                    ->label('Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif')
                    ->placeholder('Semua'),

                Tables\Filters\TernaryFilter::make('lock_position')
                    ->label('Kunci Posisi')
                    ->trueLabel('Terkunci')
                    ->falseLabel('Bebas')
                    ->placeholder('Semua'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('bulk_toggle_aktif')
                        ->label('Toggle Aktif/Nonaktif')
                        ->icon('heroicon-o-check-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Toggle Status Aktif Soal')
                        ->modalDescription('Status aktif semua soal yang dipilih akan dibalik (aktif → nonaktif atau sebaliknya).')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            foreach ($records as $record) {
                                $record->update(['aktif' => ! $record->aktif]);
                            }
                            Notification::make()->success()->title('Status aktif ' . $records->count() . ' soal diperbarui.')->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user  = \Illuminate\Support\Facades\Auth::user();

        if ($user->level === \App\Models\User::LEVEL_GURU) {
            return $query->where('created_by', $user->id);
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListQuestions::route('/'),
            'create' => Pages\CreateQuestion::route('/create'),
            'edit'   => Pages\EditQuestion::route('/{record}/edit'),
        ];
    }
}
