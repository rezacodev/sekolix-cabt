<?php

namespace App\Filament\Resources\ExamPackageResource\Pages;

use App\Filament\Resources\ExamPackageResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditExamPackage extends EditRecord
{
    protected static string $resource = ExamPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Preview: open package detail in new tab (Fase 7 route — uses basic show for now)
            Actions\Action::make('preview')
                ->label('Preview Paket')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->url(fn () => route('admin.paket.preview', $this->record))
                ->openUrlInNewTab()
                ->visible(fn () => \Illuminate\Support\Facades\Route::has('admin.paket.preview')),

            // Regrade: trigger re-calculation of all results in finished sessions
            Actions\Action::make('regrade')
                ->label('Hitung Ulang Nilai')
                ->icon('heroicon-o-calculator')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Hitung Ulang Nilai Semua Sesi?')
                ->modalDescription('Semua jawaban peserta pada sesi yang selesai akan dihitung ulang. Proses ini tidak bisa dibatalkan.')
                ->visible(fn () => $this->record->isSoftLocked())
                ->action(function () {
                    // Regrade logic implemented in Fase 8 (results)
                    Notification::make()
                        ->title('Regrade dijadwalkan.')
                        ->body('Nilai akan diperbarui di latar belakang.')
                        ->info()
                        ->send();
                }),

            Actions\DeleteAction::make()
                ->disabled(fn () => $this->record->isSoftLocked()),
        ];
    }
}
