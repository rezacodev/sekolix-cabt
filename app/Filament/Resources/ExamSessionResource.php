<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamSessionResource\Pages;
use App\Filament\Resources\ExamSessionResource\RelationManagers;
use App\Models\ExamPackage;
use App\Models\ExamSession;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ExamSessionResource extends Resource
{
    protected static ?string $model = ExamSession::class;

    protected static ?string $navigationIcon       = 'heroicon-o-play-circle';
    protected static ?string $navigationGroup      = 'Sesi Ujian';
    protected static ?int    $navigationSort       = 30;
    protected static ?string $modelLabel           = 'Sesi Ujian';
    protected static ?string $pluralModelLabel     = 'Sesi Ujian';
    protected static ?string $recordTitleAttribute = 'nama_sesi';

    // ── Guru hanya melihat sesi milik sendiri ────────────────────────────────
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        /** @var \App\Models\User $user */
        $user  = Auth::user();
        if ($user->level === User::LEVEL_GURU) {
            return $query->where('created_by', $user->id);
        }
        return $query;
    }

    // ── Form ─────────────────────────────────────────────────────────────────
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Sesi')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('nama_sesi')
                        ->label('Nama Sesi')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\Select::make('exam_package_id')
                        ->label('Paket Ujian')
                        ->options(fn() => ExamPackage::orderBy('nama')->pluck('nama', 'id'))
                        ->searchable()
                        ->required()
                        ->native(false)
                        ->columnSpanFull(),

                    Forms\Components\DateTimePicker::make('waktu_mulai')
                        ->label('Waktu Mulai')
                        ->required()
                        ->seconds(false)
                        ->native(false),

                    Forms\Components\DateTimePicker::make('waktu_selesai')
                        ->label('Waktu Selesai')
                        ->required()
                        ->seconds(false)
                        ->native(false)
                        ->after('waktu_mulai'),
                ]),

            Forms\Components\Section::make('Pengaturan Akses')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options(ExamSession::STATUS_LABELS)
                        ->required()
                        ->default(ExamSession::STATUS_DRAFT)
                        ->native(false),

                    Forms\Components\TextInput::make('token_akses')
                        ->label('Token Akses')
                        ->nullable()
                        ->maxLength(20)
                        ->helperText('Kosongkan jika tidak memerlukan token. Peserta wajib input token sebelum mulai ujian.')
                        ->suffixAction(
                            Forms\Components\Actions\Action::make('generate_token')
                                ->label('Generate')
                                ->icon('heroicon-o-arrow-path')
                                ->action(fn(Forms\Set $set) => $set('token_akses', strtoupper(Str::random(8))))
                        ),
                ]),
        ]);
    }

    // ── Table ────────────────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_sesi')
                    ->label('Nama Sesi')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('package.nama')
                    ->label('Paket Ujian')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('waktu_mulai')
                    ->label('Mulai')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('waktu_selesai')
                    ->label('Selesai')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn($state) => ExamSession::STATUS_LABELS[$state] ?? $state)
                    ->color(fn($state) => match ($state) {
                        ExamSession::STATUS_DRAFT      => 'gray',
                        ExamSession::STATUS_AKTIF      => 'success',
                        ExamSession::STATUS_SELESAI    => 'info',
                        ExamSession::STATUS_DIBATALKAN => 'danger',
                        default                        => 'gray',
                    }),

                Tables\Columns\TextColumn::make('token_akses')
                    ->label('Token')
                    ->placeholder('—')
                    ->fontFamily('mono')
                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                    ->copyable()
                    ->copyMessage('Token disalin!')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('participants_count')
                    ->label('Peserta')
                    ->counts('participants')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(ExamSession::STATUS_LABELS)
                    ->multiple(),

                Tables\Filters\Filter::make('waktu_mulai')
                    ->label('Hari Ini')
                    ->query(fn(Builder $query) => $query->whereDate('waktu_mulai', today()))
                    ->toggle(),

                Tables\Filters\SelectFilter::make('created_by')
                    ->label('Dibuat Oleh (Guru)')
                    ->options(fn() => User::where('level', '>=', User::LEVEL_GURU)->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->native(false)
                    ->hidden(fn(): bool => Auth::user()->level < User::LEVEL_ADMIN),
            ])
            ->actions([
                // Buka sesi (draft → aktif)
                Tables\Actions\Action::make('buka')
                    ->label('Buka')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Buka Sesi Ujian?')
                    ->modalDescription('Status sesi akan diubah menjadi Aktif. Peserta mulai dapat mengikuti ujian.')
                    ->visible(fn(ExamSession $record) => $record->canBuka())
                    ->action(function (ExamSession $record) {
                        $record->update(['status' => ExamSession::STATUS_AKTIF]);
                        Notification::make()->success()->title('Sesi dibuka')->send();
                    }),

                // Tutup sesi (aktif → selesai)
                Tables\Actions\Action::make('tutup')
                    ->label('Tutup')
                    ->icon('heroicon-o-stop-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Tutup Sesi Ujian?')
                    ->modalDescription('Status sesi akan diubah menjadi Selesai. Peserta tidak dapat lagi mengerjakan ujian.')
                    ->visible(fn(ExamSession $record) => $record->canTutup())
                    ->action(function (ExamSession $record) {
                        $record->update(['status' => ExamSession::STATUS_SELESAI]);
                        Notification::make()->success()->title('Sesi ditutup')->send();
                    }),

                Tables\Actions\Action::make('monitor')
                    ->label('Monitor')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn(ExamSession $record) => Pages\MonitorSesi::getUrl(['record' => $record->id]))
                    ->visible(fn(ExamSession $record) => $record->status !== ExamSession::STATUS_DIBATALKAN),

                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->visible(fn(ExamSession $record) => $record->isDraft()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn(): bool => Auth::user()->level >= User::LEVEL_ADMIN),
                ]),
            ])
            ->defaultSort('waktu_mulai', 'desc');
    }

    // ── Relations ────────────────────────────────────────────────────────────
    public static function getRelations(): array
    {
        return [
            RelationManagers\ParticipantsRelationManager::class,
        ];
    }

    // ── Pages ────────────────────────────────────────────────────────────────
    public static function getPages(): array
    {
        return [
            'index'   => Pages\ListExamSessions::route('/'),
            'create'  => Pages\CreateExamSession::route('/create'),
            'edit'    => Pages\EditExamSession::route('/{record}/edit'),
            'monitor' => Pages\MonitorSesi::route('/{record}/monitor'),
        ];
    }
}
