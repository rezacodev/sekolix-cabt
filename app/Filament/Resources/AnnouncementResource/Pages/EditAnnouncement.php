<?php

namespace App\Filament\Resources\AnnouncementResource\Pages;

use App\Filament\Resources\AnnouncementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAnnouncement extends EditRecord
{
  protected static string $resource = AnnouncementResource::class;

  protected function getSavedNotificationTitle(): ?string
  {
    return 'Pengumuman berhasil diperbarui';
  }

  protected function getHeaderActions(): array
  {
    return [
      Actions\DeleteAction::make()
          ->successNotificationTitle('Pengumuman berhasil dihapus'),
    ];
  }
}
