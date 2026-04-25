<?php

namespace App\Filament\Resources\TagResource\Pages;

use App\Filament\Concerns\HasHelpHeader;
use App\Filament\Resources\QuestionResource;
use App\Filament\Resources\TagResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTags extends ListRecords
{
    use HasHelpHeader;

    protected static string $resource = TagResource::class;

    protected function getHeaderActions(): array
    {
        return $this->appendHelpAction([
            Actions\Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(QuestionResource::getUrl()),

            Actions\CreateAction::make(),
        ]);
    }

    protected function getHelpModalView(): string
    {
        return 'filament.pages.actions.modal-help-tag';
    }
}
