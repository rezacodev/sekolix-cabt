<?php

namespace App\Filament\Resources\AnnouncementResource\Pages;

use App\Filament\Concerns\HasHelpHeader;
use App\Filament\Resources\AnnouncementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAnnouncements extends ListRecords
{
  use HasHelpHeader;

  protected static string $resource = AnnouncementResource::class;

  protected function getHeaderActions(): array
  {
    return $this->appendHelpAction([
      Actions\CreateAction::make(),
    ]);
  }

  protected function getHelpModalView(): string
  {
    return 'filament.pages.actions.modal-help-announcement';
  }
}
