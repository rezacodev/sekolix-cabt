<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamPackageResource\Pages;
use App\Filament\Resources\ExamPackageResource\RelationManagers;
use App\Models\ExamPackage;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ExamPackageResource extends Resource
{
    protected static ?string $model = ExamPackage::class;

    protected static ?string $navigationIcon       = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup      = 'Paket Ujian';
    protected static ?int    $navigationSort       = 20;
    protected static ?string $modelLabel           = 'Paket Ujian';
    protected static ?string $pluralModelLabel     = 'Paket Ujian';
    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ── Informasi Paket ──────────────────────────────────
                Forms\Components\Section::make('Informasi Paket')
                    ->columns(1)
                    ->schema([
                        Forms\Components\TextInput::make('nama')
                            ->label('Nama Paket')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('deskripsi')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->nullable()
                            ->columnSpanFull(),
                    ]),

                // ── Pengaturan Waktu ─────────────────────────────────
                Forms\Components\Section::make('Pengaturan Waktu')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('durasi_menit')
                            ->label('Durasi (menit)')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(60),

                        Forms\Components\TextInput::make('waktu_minimal_menit')
                            ->label('Waktu Minimal Submit (menit)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('0 = peserta bisa submit kapan saja'),
                    ]),

                // ── Pengaturan Ujian ─────────────────────────────────
                Forms\Components\Section::make('Pengaturan Ujian')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('grading_mode')
                            ->label('Mode Penilaian')
                            ->options(ExamPackage::GRADING_LABELS)
                            ->required()
                            ->default(fn() => \App\Models\AppSetting::getBool('realtime_grading', true) ? 'realtime' : 'manual')
                            ->helperText('Realtime: nilai dihitung otomatis saat submit. Manual: guru input nilai.'),

                        Forms\Components\TextInput::make('max_pengulangan')
                            ->label('Maks. Percobaan')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->default(1)
                            ->helperText('0 = tidak dibatasi. 1 = hanya 1 kali (tanpa remidi). 2 = boleh remidi 1 kali. dst.'),

                        Forms\Components\Toggle::make('acak_soal')
                            ->label('Acak Urutan Soal')
                            ->default(false),

                        Forms\Components\Toggle::make('acak_opsi')
                            ->label('Acak Urutan Opsi')
                            ->default(false),

                        Forms\Components\Toggle::make('tampilkan_hasil')
                            ->label('Tampilkan Nilai ke Peserta')
                            ->default(true),

                        Forms\Components\Toggle::make('tampilkan_review')
                            ->label('Peserta Bisa Review Jawaban')
                            ->default(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Paket')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('durasi_menit')
                    ->label('Durasi')
                    ->suffix(' mnt')
                    ->sortable(),

                Tables\Columns\TextColumn::make('questions_count')
                    ->label('Jumlah Soal')
                    ->counts('questions')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('max_pengulangan')
                    ->label('Maks. Percobaan')
                    ->formatStateUsing(fn($state) => $state == 0 ? 'Tak terbatas' : $state)
                    ->badge()
                    ->color(fn($state) => $state == 0 ? 'warning' : 'gray')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('grading_mode')
                    ->label('Penilaian')
                    ->formatStateUsing(fn($state) => ExamPackage::GRADING_LABELS[$state] ?? $state)
                    ->colors([
                        'success' => 'realtime',
                        'warning' => 'manual',
                    ]),

                Tables\Columns\IconColumn::make('acak_soal')
                    ->label('Acak Soal')
                    ->boolean(),

                Tables\Columns\IconColumn::make('tampilkan_hasil')
                    ->label('Tampil Nilai')
                    ->boolean(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('grading_mode')
                    ->label('Mode Penilaian')
                    ->options(ExamPackage::GRADING_LABELS),
                Tables\Filters\SelectFilter::make('created_by')
                    ->label('Dibuat Oleh')
                    ->options(fn() => User::where('level', '>=', User::LEVEL_GURU)->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->native(false)
                    ->hidden(fn(): bool => Auth::user()->level < User::LEVEL_ADMIN),
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
        return [
            RelationManagers\QuestionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListExamPackages::route('/'),
            'create' => Pages\CreateExamPackage::route('/create'),
            'edit'   => Pages\EditExamPackage::route('/{record}/edit'),
        ];
    }
}
