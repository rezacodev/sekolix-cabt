<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Rombel;
use App\Models\User;
use App\Services\AuditLogService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Manajemen User';

    protected static ?string $modelLabel = 'User';

    protected static ?string $pluralModelLabel = 'Daftar User';

    protected static ?int $navigationSort = 1;

    /** Guru tidak dapat mengakses halaman Manajemen User sama sekali. */
    public static function canViewAny(): bool
    {
        return Auth::user()->level >= User::LEVEL_ADMIN;
    }

    public static function canCreate(): bool
    {
        return Auth::user()->level >= User::LEVEL_ADMIN;
    }

    public static function canEdit($record): bool
    {
        return Auth::user()->level >= User::LEVEL_ADMIN;
    }

    public static function canDelete($record): bool
    {
        return Auth::user()->level >= User::LEVEL_ADMIN;
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()->level >= User::LEVEL_ADMIN;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Akun')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('username')
                            ->label('Username')
                            ->unique(ignoreRecord: true)
                            ->nullable()
                            ->maxLength(50)
                            ->regex('/^[a-zA-Z0-9._-]+$/'),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn($state) => filled($state))
                            ->required(fn(string $operation) => $operation === 'create')
                            ->maxLength(255)
                            ->helperText('Kosongkan jika tidak ingin mengubah password.'),
                    ])->columns(2),

                Forms\Components\Section::make('Informasi Peserta')
                    ->schema([
                        Forms\Components\Select::make('level')
                            ->label('Level')
                            ->options(User::levelLabels())
                            ->required()
                            ->default(User::LEVEL_PESERTA)
                            ->native(false),
                        Forms\Components\TextInput::make('nomor_peserta')
                            ->label('Nomor Peserta')
                            ->unique(ignoreRecord: true)
                            ->nullable()
                            ->maxLength(50),
                        Forms\Components\Select::make('rombels')
                            ->label('Rombongan Belajar')
                            ->helperText('Peserta dapat terdaftar di lebih dari satu rombel')
                            ->multiple()
                            ->relationship('rombels', 'nama', fn($query) => $query->where('aktif', true)->orderBy('nama'))
                            ->getOptionLabelFromRecordUsing(fn(Rombel $record) => "{$record->kode} — {$record->nama}")
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->visible(fn(\Filament\Forms\Get $get) => (int) $get('level') === User::LEVEL_PESERTA),
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
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('level')
                    ->label('Level')
                    ->badge()
                    ->formatStateUsing(fn($state) => User::levelLabels()[$state] ?? $state)
                    ->color(fn($state) => match ((int) $state) {
                        User::LEVEL_PESERTA     => 'gray',
                        User::LEVEL_GURU        => 'info',
                        User::LEVEL_ADMIN       => 'warning',
                        User::LEVEL_SUPER_ADMIN => 'success',
                        default                 => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('nomor_peserta')
                    ->label('No. Peserta')
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\ToggleColumn::make('aktif')
                    ->label('Aktif')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('level')
                    ->label('Level')
                    ->options(User::levelLabels())
                    ->native(false),
                Tables\Filters\SelectFilter::make('rombel_id')
                    ->label('Rombongan Belajar')
                    ->options(fn() => Rombel::orderBy('nama')->pluck('nama', 'id'))
                    ->searchable()
                    ->native(false),
                Tables\Filters\TernaryFilter::make('aktif')
                    ->label('Status Aktif')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('lihat_portofolio')
                    ->label('Portofolio')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->visible(fn(User $record) => $record->level === User::LEVEL_PESERTA)
                    ->url(fn(User $record) => Pages\PesertaPortfolio::getUrl(['record' => $record->id])),
                Tables\Actions\Action::make('reset_password')
                    ->label('Reset Password')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('new_password')
                            ->label('Password Baru')
                            ->password()
                            ->required()
                            ->minLength(8)
                            ->maxLength(255),
                    ])
                    ->action(function (User $record, array $data) {
                        $record->update(['password' => Hash::make($data['new_password'])]);
                        AuditLogService::log('reset_password', $record, "Reset password user: {$record->name}");
                        Notification::make()
                            ->title('Password berhasil direset.')
                            ->success()
                            ->send();
                    })
                    ->modalHeading('Reset Password')
                    ->requiresConfirmation(false),
                Tables\Actions\Action::make('reset_session')
                    ->label('Paksa Logout')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Paksa Logout User')
                    ->modalDescription('Seluruh sesi aktif user ini akan dihapus. User harus login ulang.')
                    ->action(function (User $record) {
                        // Hapus semua session DB milik user ini
                        \Illuminate\Support\Facades\DB::table('sessions')
                            ->where('user_id', $record->id)
                            ->delete();
                        AuditLogService::log('paksa_logout', $record, "Paksa logout user: {$record->name}");
                        Notification::make()
                            ->title("Sesi {$record->name} berhasil dihapus.")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->successNotificationTitle('Pengguna berhasil dihapus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('bulk_toggle_aktif')
                        ->label('Toggle Aktif/Nonaktif')
                        ->icon('heroicon-o-check-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Toggle Status Aktif')
                        ->modalDescription('Status aktif semua user yang dipilih akan dibalik (aktif → nonaktif atau sebaliknya).')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            foreach ($records as $record) {
                                $record->update(['aktif' => ! $record->aktif]);
                            }
                            Notification::make()->success()->title('Status aktif ' . $records->count() . ' user diperbarui.')->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('bulk_reset_password')
                        ->label('Reset Password Massal')
                        ->icon('heroicon-o-key')
                        ->color('danger')
                        ->form([
                            Forms\Components\TextInput::make('new_password')
                                ->label('Password Baru')
                                ->password()
                                ->required()
                                ->minLength(8)
                                ->maxLength(255)
                                ->helperText('Password ini akan diterapkan ke semua user yang dipilih.'),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $hashed = Hash::make($data['new_password']);
                            foreach ($records as $record) {
                                $record->update(['password' => $hashed]);
                            }
                            AuditLogService::log('bulk_reset_password', null, 'Bulk reset password untuk ' . $records->count() . ' user');
                            Notification::make()->success()->title('Password berhasil direset untuk ' . $records->count() . ' user.')->send();
                        })
                        ->modalHeading('Reset Password Massal')
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'      => Pages\ListUsers::route('/'),
            'create'     => Pages\CreateUser::route('/create'),
            'edit'       => Pages\EditUser::route('/{record}/edit'),
            'portofolio' => Pages\PesertaPortfolio::route('/{record}/portofolio'),
            'import'     => Pages\ImportUsers::route('/import'),
        ];
    }
}
