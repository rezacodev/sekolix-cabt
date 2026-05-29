<?php

namespace App\Exports;

use App\Models\ExamPackage;
use App\Models\Question;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PaketSoalExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
  private int $rowNo = 0;

  /** Max opsi that will be rendered as separate columns (A–E) */
  private const MAX_OPSI = 5;

  public function __construct(private readonly ExamPackage $package) {}

  public function title(): string
  {
    return str($this->package->nama)->limit(28)->toString();
  }

  public function headings(): array
  {
    $opsiHeadings = [];
    for ($i = 0; $i < self::MAX_OPSI; $i++) {
      $opsiHeadings[] = 'Opsi ' . chr(65 + $i); // A, B, C, D, E
    }

    return array_merge(
      ['No', 'Tipe', 'Pertanyaan'],
      $opsiHeadings,
      ['Kunci Jawaban', 'Pembahasan', 'Bobot', 'Kelas', 'Kategori']
    );
  }

  public function collection()
  {
    return $this->package->questions()->with([
      'options'    => fn($q) => $q->orderBy('urutan'),
      'matches'    => fn($q) => $q->orderBy('urutan'),
      'keywords',
      'clozeBlank' => fn($q) => $q->orderBy('urutan'),
      'category',
    ])->get();
  }

  public function map($question): array
  {
    $this->rowNo++;

    $opsiCols = array_fill(0, self::MAX_OPSI, '');

    // Fill opsi columns for option-based types
    if (in_array($question->tipe, ['PG', 'PG_BOBOT', 'PGJ', 'BS'])) {
      foreach ($question->options as $idx => $opt) {
        if ($idx < self::MAX_OPSI) {
          $text = $opt->kode_opsi . '. ' . strip_tags($opt->teks_opsi);
          if ($question->tipe === 'PG_BOBOT' && $opt->bobot_persen !== null) {
            $text .= ' (' . $opt->bobot_persen . '%)';
          }
          $opsiCols[$idx] = $text;
        }
      }
    }

    $kunci = $this->buildKunci($question);
    $pembahasan = $question->penjelasan ? strip_tags($question->penjelasan) : '';

    return array_merge(
      [
        $this->rowNo,
        Question::TIPE_LABELS[$question->tipe] ?? $question->tipe,
        strip_tags($question->teks_soal),
      ],
      $opsiCols,
      [
        $kunci,
        $pembahasan,
        $question->bobot,
        $question->kelas ? 'Kelas ' . $question->kelas : '',
        $question->category?->nama ?? '',
      ]
    );
  }

  public function styles(Worksheet $sheet): array
  {
    return [
      1 => ['font' => ['bold' => true]],
    ];
  }

  // ── Private helpers ──────────────────────────────────────────────────────

  private function buildKunci(Question $question): string
  {
    return match ($question->tipe) {
      'PG'       => $this->kunciPg($question, single: true),
      'BS'       => $this->kunciPg($question, single: true),
      'PGJ'      => $this->kunciPg($question, single: false),
      'PG_BOBOT' => $this->kunciPgBobot($question),
      'JODOH'    => $this->kunciJodoh($question),
      'ISIAN'    => $this->kunciIsian($question),
      'CLOZE'    => $this->kunciCloze($question),
      'URAIAN'   => 'Dinilai Manual',
      default    => '',
    };
  }

  private function kunciPg(Question $question, bool $single): string
  {
    $correct = $question->options->where('is_correct', true);
    if ($correct->isEmpty()) {
      return '';
    }
    if ($single) {
      return $correct->first()->kode_opsi ?? '';
    }

    return $correct->pluck('kode_opsi')->join(', ');
  }

  private function kunciPgBobot(Question $question): string
  {
    return $question->options
      ->where('is_correct', true)
      ->map(fn($opt) => $opt->kode_opsi . ($opt->bobot_persen !== null ? ' (' . $opt->bobot_persen . '%)' : ''))
      ->join(', ');
  }

  private function kunciJodoh(Question $question): string
  {
    return $question->matches
      ->map(fn($m) => strip_tags($m->premis) . ' → ' . strip_tags($m->respon))
      ->join(' | ');
  }

  private function kunciIsian(Question $question): string
  {
    return $question->keywords->pluck('keyword')->join(' / ');
  }

  private function kunciCloze(Question $question): string
  {
    return $question->clozeBlank
      ->map(fn($b) => '[' . $b->urutan . '] ' . $b->jawaban_benar)
      ->join(' | ');
  }
}
