<?php

namespace App\Filament\Concerns;

use Filament\Actions;
use Illuminate\Contracts\View\View;

trait HasHelpHeader
{
  protected function getHelpAction(): Actions\Action
  {
    return Actions\Action::make('help')
      ->label('Bantuan')
      ->icon('heroicon-o-question-mark-circle')
      ->color('warning')
      ->modalSubmitAction(false)
      ->modalCancelActionLabel('Tutup')
      ->modalHeading($this->getHelpModalHeading())
      ->modalContent($this->getHelpModalContent());
  }

  protected function getHelpModalHeading(): string
  {
    return 'Panduan Halaman ' . (string) $this->getHeading();
  }

  protected function getHelpModalView(): string
  {
    return 'filament.pages.actions.modal-help';
  }

  protected function getHelpModalData(): array
  {
    return [
      'pageTitle' => (string) $this->getHeading(),
    ];
  }

  protected function getHelpModalContent(): View
  {
    return view($this->getHelpModalView(), $this->getHelpModalData());
  }

  protected function appendHelpAction(array $actions): array
  {
    $actions[] = $this->getHelpAction();

    return $actions;
  }

  public function getHeader(): ?View
  {
    $helpAction = null;
    $otherActions = [];

    foreach ($this->getCachedHeaderActions() as $action) {
      if ($action->getName() === 'help') {
        $helpAction = $action;
      } else {
        $otherActions[] = $action;
      }
    }

    return view('filament.components.headers.help-header', [
      'helpAction' => $helpAction,
      'otherActions' => $otherActions,
      'heading' => $this->getHeading(),
      'subheading' => $this->getSubheading(),
      'breadcrumbs' => filament()->hasBreadcrumbs() ? $this->getBreadcrumbs() : [],
    ]);
  }
}
