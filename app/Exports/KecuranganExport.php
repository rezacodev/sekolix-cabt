<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use App\Models\ExamAttempt;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KecuranganExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles
{
  private Collection $data;

  public function __construct(Collection $data)
  {
    $this->data = $data;
  }

  public function collection(): Collection
  {
    return $this->data;
  }

  public function title(): string
  {
    return 'Rekap Kecurangan';
  }

  public function headings(): array
  {
    return [
      'No',
      'Nama Peserta',
      'No. Peserta',
      'Rombel',
      'Tab Switch',
      'Blur',
      'Kick',
      'Timeout',
      'Auto-Submit',
      'Status Akhir',
      'Total Pelanggaran',
    ];
  }

  public function map($row): array
  {
    static $no = 0;
    $no++;
    $total = $row->tab_switch + $row->blur + $row->kick;

    return [
      $no,
      $row->nama,
      $row->nomor_peserta,
      $row->rombel_nama,
      $row->tab_switch,
      $row->blur,
      $row->kick,
      $row->timeout_count,
      $row->auto_submitted ? 'Ya' : 'Tidak',
      ExamAttempt::STATUS_LABELS[$row->status_akhir] ?? $row->status_akhir,
      $total,
    ];
  }

  public function styles(Worksheet $sheet): array
  {
    return [
      1 => ['font' => ['bold' => true, 'size' => 11]],
    ];
  }
}
