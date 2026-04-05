<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Pengguna berhasil diperbarui';
    }

    protected function afterSave(): void
    {
        $record = $this->record;
        if ($record->level === User::LEVEL_PESERTA && $record->rombel_id) {
            $record->rombels()->syncWithoutDetaching([$record->rombel_id]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->successNotificationTitle('Pengguna berhasil dihapus'),
        ];
    }
}
