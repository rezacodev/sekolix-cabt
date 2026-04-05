<?php

namespace App\Filament\Resources\CurriculumStandardResource\Pages;

use App\Filament\Resources\CurriculumStandardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCurriculumStandard extends EditRecord
{
    protected static string $resource = CurriculumStandardResource::class;

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Standar kurikulum berhasil diperbarui';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->successNotificationTitle('Standar kurikulum berhasil dihapus'),
        ];
    }
}
