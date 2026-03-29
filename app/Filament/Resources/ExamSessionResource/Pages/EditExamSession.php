<?php

namespace App\Filament\Resources\ExamSessionResource\Pages;

use App\Filament\Resources\ExamSessionResource;
use App\Filament\Resources\ExamSessionResource\Pages\MonitorSesi;
use App\Models\ExamSession;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditExamSession extends EditRecord
{
    protected static string $resource = ExamSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('buka')
                ->label('Buka Sesi')
                ->icon('heroicon-o-play')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Buka Sesi Ujian?')
                ->modalDescription('Status sesi akan diubah menjadi Aktif. Peserta mulai dapat mengikuti ujian.')
                ->visible(fn () => $this->record->canBuka())
                ->action(function () {
                    $this->record->update(['status' => ExamSession::STATUS_AKTIF]);
                    Notification::make()->success()->title('Sesi dibuka')->send();
                    $this->refreshFormData(['status']);
                }),

            Actions\Action::make('tutup')
                ->label('Tutup Sesi')
                ->icon('heroicon-o-stop-circle')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Tutup Sesi Ujian?')
                ->modalDescription('Status sesi akan diubah menjadi Selesai. Peserta tidak dapat lagi mengerjakan ujian.')
                ->visible(fn () => $this->record->canTutup())
                ->action(function () {
                    $this->record->update(['status' => ExamSession::STATUS_SELESAI]);
                    Notification::make()->success()->title('Sesi ditutup')->send();
                    $this->refreshFormData(['status']);
                }),

            Actions\Action::make('monitor')
                ->label('Monitor')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->url(fn () => MonitorSesi::getUrl(['record' => $this->record->id]))
                ->visible(fn () => $this->record->status !== ExamSession::STATUS_DIBATALKAN),

            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->isDraft()),
        ];
    }
}
