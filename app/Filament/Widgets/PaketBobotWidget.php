<?php

namespace App\Filament\Widgets;

use App\Models\ExamPackage;
use App\Models\Question;
use Filament\Widgets\Widget;

class PaketBobotWidget extends Widget
{
  public ExamPackage $record;

  protected static string $view = 'filament.widgets.paket-bobot-widget';

  protected int | string | array $columnSpan = 'full';

  protected function getViewData(): array
  {
    $questions  = $this->record->questions()->get(['questions.id', 'questions.bobot', 'questions.tipe']);
    $totalBobot = (float) $questions->sum('bobot');
    $jumlahSoal = $questions->count();
    $tipeLabels = Question::TIPE_LABELS;

    $breakdown = $questions
      ->groupBy('tipe')
      ->map(fn($g, $tipe) => [
        'label' => $tipeLabels[$tipe] ?? $tipe,
        'count' => $g->count(),
        'bobot' => (float) $g->sum('bobot'),
      ])
      ->sortByDesc('count')
      ->values();

    if ($jumlahSoal === 0) {
      $status = 'empty';
    } elseif (abs($totalBobot - 100) < 0.01) {
      $status = 'ok';
    } elseif ($totalBobot < 100) {
      $status = 'under';
    } else {
      $status = 'over';
    }

    return compact('totalBobot', 'jumlahSoal', 'breakdown', 'status');
  }
}
