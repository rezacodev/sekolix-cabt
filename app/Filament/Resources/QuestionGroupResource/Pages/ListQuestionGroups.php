<?php

namespace App\Filament\Resources\QuestionGroupResource\Pages;

use App\Filament\Concerns\HasHelpHeader;
use App\Filament\Resources\QuestionGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuestionGroups extends ListRecords
{
  use HasHelpHeader;

  protected static string $resource = QuestionGroupResource::class;

  protected function getHeaderActions(): array
  {
    return $this->appendHelpAction([
      Actions\CreateAction::make(),
    ]);
  }

  protected function getHelpModalView(): string
  {
    return 'filament.pages.actions.modal-help-question-group';
  }
}
