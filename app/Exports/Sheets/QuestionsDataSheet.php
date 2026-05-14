<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class QuestionsDataSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
  public function title(): string
  {
    return 'Data Soal';
  }

  public function headings(): array
  {
    return [
      'tipe_soal',
      'teks_soal',
      'opsi_a',
      'opsi_b',
      'opsi_c',
      'opsi_d',
      'opsi_e',
      'kunci',
      'mata_pelajaran',
      'kategori',
      'kesulitan',
      'bobot',
    ];
  }

  public function array(): array
  {
    return [
      [
        'PG',
        'Ibu kota Indonesia adalah ...',
        'Bandung',
        'Jakarta',
        'Surabaya',
        'Medan',
        '',
        'B',
        'Pengetahuan Umum',
        'Pengetahuan Umum',
        'mudah',
        1,
      ],
      [
        'PGJ',
        'Manakah pernyataan berikut yang benar tentang fotosintesis?',
        'Terjadi di mitokondria',
        'Menghasilkan oksigen',
        'Membutuhkan cahaya matahari',
        'Hanya terjadi pada malam hari',
        '',
        'B,C',
        'IPA',
        'Biologi',
        'sedang',
        2,
      ],
      [
        'BS',
        'Air mendidih pada suhu 100 derajat Celcius pada tekanan normal.',
        '',
        '',
        '',
        '',
        '',
        'B',
        'IPA',
        'IPA',
        'mudah',
        1,
      ],
      [
        'ISIAN',
        'Ibukota provinsi Jawa Barat adalah ...',
        '',
        '',
        '',
        '',
        '',
        'Bandung',
        'Pengetahuan Umum',
        'Pengetahuan Umum',
        'mudah',
        1,
      ],
      [
        'URAIAN',
        'Jelaskan pengertian dari fotosintesis secara lengkap!',
        '',
        '',
        '',
        '',
        '',
        '',
        'IPA',
        'Biologi',
        'sedang',
        5,
      ],
    ];
  }

  public function columnWidths(): array
  {
    return [
      'A' => 12,  // tipe_soal
      'B' => 50,  // teks_soal
      'C' => 25,  // opsi_a
      'D' => 25,  // opsi_b
      'E' => 25,  // opsi_c
      'F' => 25,  // opsi_d
      'G' => 25,  // opsi_e
      'H' => 15,  // kunci
      'I' => 20,  // mata_pelajaran
      'J' => 20,  // kategori
      'K' => 12,  // kesulitan
      'L' => 8,   // bobot
    ];
  }

  public function styles(Worksheet $sheet): array
  {
    // Header row style
    $sheet->getStyle('A1:L1')->applyFromArray([
      'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
      'fill' => [
        'fillType'   => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '1e40af'],
      ],
      'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    ]);

    // Freeze header row
    $sheet->freezePane('A2');

    // Row height for header
    $sheet->getRowDimension(1)->setRowHeight(22);

    // Wrap text for teks_soal column
    $sheet->getStyle('B2:B1000')->getAlignment()->setWrapText(true);

    // Alternating row background for data rows (2–6)
    for ($row = 2; $row <= 6; $row++) {
      $color = ($row % 2 === 0) ? 'EFF6FF' : 'FFFFFF';
      $sheet->getStyle("A{$row}:L{$row}")->applyFromArray([
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
      ]);
    }

    // Borders for header + data
    $sheet->getStyle('A1:L6')->applyFromArray([
      'borders' => [
        'allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'BFDBFE']],
      ],
    ]);

    return [];
  }
}
