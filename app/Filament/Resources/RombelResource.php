<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RombelResource\Pages;
use App\Filament\Resources\RombelResource\RelationManagers;
use App\Models\Rombel;
use App\Services\AuditLogService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class RombelResource extends Resource
{
    protected static ?string $model = Rombel::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Rombongan Belajar';

    protected static ?string $modelLabel = 'Rombel';

    protected static ?string $pluralModelLabel = 'Daftar Rombel';

    protected static ?int $navigationSort = 2;

    /** Guru tidak dapat mengakses halaman Rombel sama sekali. */
    public static function canViewAny(): bool
    {
        return Auth::user()->level >= \App\Models\User::LEVEL_ADMIN;
    }

    public static function canCreate(): bool
    {
        return Auth::user()->level >= \App\Models\User::LEVEL_ADMIN;
    }

    public static function canEdit($record): bool
    {
        return Auth::user()->level >= \App\Models\User::LEVEL_ADMIN;
    }

    public static function canDelete($record): bool
    {
        return Auth::user()->level >= \App\Models\User::LEVEL_ADMIN;
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()->level >= \App\Models\User::LEVEL_ADMIN;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Rombel')
                    ->schema([
                        Forms\Components\TextInput::make('nama')
                            ->label('Nama Rombel')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('kode')
                            ->label('Kode Rombel')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(30)
                            ->helperText('Contoh: X-IPA-1, XI-IPS-2'),
                        Forms\Components\TextInput::make('angkatan')
                            ->label('Angkatan (Tahun Masuk)')
                            ->numeric()
                            ->minValue(2000)
                            ->maxValue(2100)
                            ->nullable(),
                        Forms\Components\TextInput::make('tahun_ajaran')
                            ->label('Tahun Ajaran')
                            ->nullable()
                            ->maxLength(20)
                            ->helperText('Contoh: 2024/2025'),
                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->nullable()
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('aktif')
                            ->label('Aktif')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Rombel')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('angkatan')
                    ->label('Angkatan')
                    ->sortable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('tahun_ajaran')
                    ->label('Tahun Ajaran')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('peserta_count')
                    ->label('Peserta')
                    ->counts('peserta')
                    ->sortable(),
                Tables\Columns\TextColumn::make('gurus_count')
                    ->label('Guru')
                    ->counts('gurus')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('aktif')
                    ->label('Aktif')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('kode')
            ->filters([
                Tables\Filters\TernaryFilter::make('aktif')
                    ->label('Status Aktif')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
                Tables\Filters\SelectFilter::make('angkatan')
                    ->label('Angkatan')
                    ->options(
                        Rombel::query()
                            ->whereNotNull('angkatan')
                            ->distinct()
                            ->orderByDesc('angkatan')
                            ->pluck('angkatan', 'angkatan')
                    )
                    ->native(false),
                Tables\Filters\SelectFilter::make('tahun_ajaran')
                    ->label('Tahun Ajaran')
                    ->options(
                        Rombel::query()
                            ->whereNotNull('tahun_ajaran')
                            ->distinct()
                            ->orderByDesc('tahun_ajaran')
                            ->pluck('tahun_ajaran', 'tahun_ajaran')
                    )
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->after(function (Rombel $record) {
                        AuditLogService::log('hapus_rombel', null, "Rombel dihapus: {$record->kode} — {$record->nama}");
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('bulk_toggle_aktif')
                        ->label('Toggle Aktif/Nonaktif')
                        ->icon('heroicon-o-check-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Toggle Status Aktif Rombel')
                        ->modalDescription('Status aktif semua rombel yang dipilih akan dibalik (aktif → nonaktif atau sebaliknya).')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            foreach ($records as $record) {
                                $record->update(['aktif' => ! $record->aktif]);
                            }
                            Notification::make()->success()->title('Status aktif ' . $records->count() . ' rombel diperbarui.')->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\GurusRelationManager::class,
            RelationManagers\PesertaRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRombels::route('/'),
            'create' => Pages\CreateRombel::route('/create'),
            'edit'   => Pages\EditRombel::route('/{record}/edit'),
        ];
    }
}
