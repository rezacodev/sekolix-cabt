<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KomparasiExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles
{
  private Collection $data;
  private string $namaA;
  private string $namaB;

  public function __construct(Collection $data, string $namaA = 'Sesi A', string $namaB = 'Sesi B')
  {
    $this->data  = $data;
    $this->namaA = $namaA;
    $this->namaB = $namaB;
  }

  public function collection(): Collection
  {
    return $this->data;
  }

  public function title(): string
  {
    return 'Komparasi Sesi';
  }

  public function headings(): array
  {
    return [
      'No',
      'Nama Peserta',
      'Rombel',
      'Nilai ' . $this->namaA,
      'Nilai ' . $this->namaB,
      'Selisih',
      'Tren',
    ];
  }

  public function map($row): array
  {
    static $no = 0;
    $no++;

    $trenLabel = match ($row->tren) {
      'naik'  => 'Naik ↑',
      'turun' => 'Turun ↓',
      'tetap' => 'Tetap →',
      default => '—',
    };

    return [
      $no,
      $row->nama,
      $row->rombel_nama,
      $row->nilai_a !== null ? number_format($row->nilai_a, 2) : '—',
      $row->nilai_b !== null ? number_format($row->nilai_b, 2) : '—',
      $row->selisih !== null ? ($row->selisih > 0 ? '+' : '') . number_format($row->selisih, 2) : '—',
      $trenLabel,
    ];
  }

  public function styles(Worksheet $sheet): array
  {
    return [
      1 => ['font' => ['bold' => true, 'size' => 11]],
    ];
  }
}
