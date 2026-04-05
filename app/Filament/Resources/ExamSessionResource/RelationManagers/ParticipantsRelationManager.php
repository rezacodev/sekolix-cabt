<?php

namespace App\Filament\Resources\ExamSessionResource\RelationManagers;

use App\Mail\SesiDijadwalkanMail;
use App\Models\AppSetting;
use App\Models\ExamSession;
use App\Models\ExamSessionParticipant;
use App\Models\Rombel;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ParticipantsRelationManager extends RelationManager
{
    protected static string $relationship = 'participants';

    protected static ?string $title = 'Daftar Peserta';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->label('Peserta')
                ->options(fn() => User::where('level', User::LEVEL_PESERTA)
                    ->where('aktif', true)
                    ->orderBy('name')
                    ->pluck('name', 'id'))
                ->searchable()
                ->required()
                ->native(false),

            Forms\Components\Select::make('status')
                ->label('Status')
                ->options(ExamSessionParticipant::STATUS_LABELS)
                ->default(ExamSessionParticipant::STATUS_BELUM)
                ->required()
                ->native(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('user.name')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama Peserta')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.nomor_peserta')
                    ->label('Nomor Peserta')
                    ->placeholder('—')
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.rombel.nama')
                    ->label('Rombel')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        ExamSessionParticipant::STATUS_BELUM           => 'gray',
                        ExamSessionParticipant::STATUS_SEDANG          => 'warning',
                        ExamSessionParticipant::STATUS_SELESAI         => 'success',
                        ExamSessionParticipant::STATUS_DISKUALIFIKASI  => 'danger',
                        default                                        => 'gray',
                    })
                    ->formatStateUsing(fn($state) => ExamSessionParticipant::STATUS_LABELS[$state] ?? $state),
            ])
            ->headerActions([
                // ── Tambah peserta individual ────────────────────────────
                Tables\Actions\Action::make('tambah_peserta')
                    ->label('Tambah Peserta')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->form([
                        Forms\Components\Select::make('user_ids')
                            ->label('Pilih Peserta')
                            ->options(function () {
                                $existing = $this->getOwnerRecord()->participants()->pluck('user_id');
                                return User::where('level', User::LEVEL_PESERTA)
                                    ->where('aktif', true)
                                    ->whereNotIn('id', $existing)
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->multiple()
                            ->searchable()
                            ->native(false)
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        /** @var ExamSession $session */
                        $session = $this->getOwnerRecord();
                        $added   = 0;
                        $notifEnabled = AppSetting::getBool('email_notifikasi_sesi', false);
                        foreach ($data['user_ids'] as $userId) {
                            $participant = ExamSessionParticipant::firstOrCreate(
                                ['exam_session_id' => $session->id, 'user_id' => $userId],
                                ['status' => ExamSessionParticipant::STATUS_BELUM]
                            );
                            if ($participant->wasRecentlyCreated) {
                                $added++;
                                if ($notifEnabled) {
                                    $user = User::find($userId);
                                    if ($user && $user->email) {
                                        \Illuminate\Support\Facades\Mail::to($user->email)
                                            ->queue(new SesiDijadwalkanMail($user, $session));
                                    }
                                }
                            }
                        }
                        Notification::make()->success()
                            ->title("{$added} peserta berhasil ditambahkan")
                            ->send();
                    }),

                // ── Assign seluruh peserta per Rombel ───────────────────
                Tables\Actions\Action::make('assign_rombel')
                    ->label('Assign per Rombel')
                    ->icon('heroicon-o-user-group')
                    ->color('info')
                    ->form([
                        Forms\Components\Select::make('rombel_ids')
                            ->label('Pilih Rombel')
                            ->options(
                                fn() => Rombel::where('aktif', true)
                                    ->orderBy('nama')
                                    ->get()
                                    ->mapWithKeys(fn($r) => [
                                        $r->id => $r->nama . ' — ' . ($r->tahun_ajaran ?? '—'),
                                    ])
                            )
                            ->multiple()
                            ->searchable()
                            ->native(false)
                            ->required()
                            ->helperText('Semua peserta aktif di rombel yang dipilih akan di-assign ke sesi ini.'),
                    ])
                    ->action(function (array $data) {
                        /** @var ExamSession $session */
                        $session = $this->getOwnerRecord();
                        $peserta = User::where('level', User::LEVEL_PESERTA)
                            ->where('aktif', true)
                            ->whereHas('rombels', fn($q) => $q->whereIn('rombels.id', $data['rombel_ids']))
                            ->pluck('id');

                        $added = 0;
                        $notifEnabled = AppSetting::getBool('email_notifikasi_sesi', false);
                        foreach ($peserta as $userId) {
                            $created = ExamSessionParticipant::firstOrCreate(
                                ['exam_session_id' => $session->id, 'user_id' => $userId],
                                ['status' => ExamSessionParticipant::STATUS_BELUM]
                            );
                            if ($created->wasRecentlyCreated) {
                                $added++;
                                if ($notifEnabled) {
                                    $user = User::find($userId);
                                    if ($user && $user->email) {
                                        \Illuminate\Support\Facades\Mail::to($user->email)
                                            ->queue(new SesiDijadwalkanMail($user, $session));
                                    }
                                }
                            }
                        }
                        Notification::make()->success()
                            ->title("{$added} peserta baru berhasil di-assign dari rombel")
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Ubah Status'),

                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }
}
