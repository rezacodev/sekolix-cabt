<?php

namespace App\Filament\Resources\ExamPackageResource\Pages;

use App\Filament\Resources\ExamPackageResource;
use App\Filament\Widgets\PaketBobotWidget;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditExamPackage extends EditRecord
{
    protected static string $resource = ExamPackageResource::class;

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Paket ujian berhasil diperbarui';
    }

    protected function getHeaderActions(): array
    {
        return [
            // Kunci Jawaban — buka tab baru
            Actions\Action::make('kunci_jawaban')
                ->label('Kunci Jawaban')
                ->icon('heroicon-o-key')
                ->color('success')
                ->url(fn() => route('paket.kunci-jawaban', $this->record))
                ->openUrlInNewTab(),

            // Cetak Soal — buka tab baru (print/PDF naskah soal)
            Actions\Action::make('cetak_soal')
                ->label('Cetak Soal')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(fn() => route('paket.cetak-soal', $this->record))
                ->openUrlInNewTab(),

            // Download Excel soal + kunci
            Actions\Action::make('export_excel')
                ->label('Download Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn() => route('paket.export-excel', $this->record))
                ->openUrlInNewTab(),

            // Preview: open package detail in new tab
            Actions\Action::make('preview')
                ->label('Preview Paket')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(fn() => route('admin.paket.preview', $this->record))
                ->openUrlInNewTab()
                ->visible(fn() => \Illuminate\Support\Facades\Route::has('admin.paket.preview')),

            // Regrade: trigger re-calculation of all results in finished sessions
            Actions\Action::make('regrade')
                ->label('Hitung Ulang Nilai')
                ->icon('heroicon-o-calculator')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Hitung Ulang Nilai Semua Sesi?')
                ->modalDescription('Semua jawaban peserta pada sesi yang selesai akan dihitung ulang. Proses ini tidak bisa dibatalkan.')
                ->visible(fn() => $this->record->isSoftLocked())
                ->action(function () {
                    Notification::make()
                        ->title('Regrade dijadwalkan.')
                        ->body('Nilai akan diperbarui di latar belakang.')
                        ->info()
                        ->send();
                }),

            Actions\DeleteAction::make()
                ->disabled(fn() => $this->record->isSoftLocked())
                ->successNotificationTitle('Paket ujian berhasil dihapus'),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            PaketBobotWidget::class,
        ];
    }
}
