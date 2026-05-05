<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class QuestionsInstructionSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
  public function title(): string
  {
    return 'Petunjuk Pengisian';
  }

  public function array(): array
  {
    return [
      // Section: Judul
      ['PETUNJUK PENGISIAN TEMPLATE IMPORT SOAL', '', ''],
      ['', '', ''],

      // Section: Kolom wajib
      ['KOLOM-KOLOM YANG TERSEDIA', '', ''],
      ['Kolom', 'Keterangan', 'Contoh'],
      ['tipe_soal', 'Tipe soal (WAJIB). Lihat tabel Tipe Soal di bawah.', 'PG'],
      ['teks_soal', 'Teks pertanyaan (WAJIB). Bisa berisi HTML sederhana.', 'Ibu kota Indonesia adalah ...'],
      ['opsi_a', 'Teks pilihan A (khusus PG, PG_BOBOT, PGJ)', 'Bandung'],
      ['opsi_b', 'Teks pilihan B', 'Jakarta'],
      ['opsi_c', 'Teks pilihan C', 'Surabaya'],
      ['opsi_d', 'Teks pilihan D', 'Medan'],
      ['opsi_e', 'Teks pilihan E (opsional)', ''],
      ['kunci', 'Jawaban kunci (lihat aturan per tipe soal)', 'B'],
      ['kategori', 'Nama kategori soal. Jika belum ada, otomatis dibuat.', 'Biologi'],
      ['kesulitan', 'Tingkat kesulitan: mudah / sedang / sulit', 'sedang'],
      ['bobot', 'Nilai bobot soal (angka). Default: 1', '5'],
      ['', '', ''],

      // Section: Tipe soal
      ['TIPE SOAL YANG DIDUKUNG', '', ''],
      ['Kode', 'Nama Lengkap', 'Aturan Kunci Jawaban (kolom kunci)'],
      ['PG', 'Pilihan Ganda Tunggal', 'Huruf opsi jawaban benar. Contoh: B'],
      ['PG_BOBOT', 'Pilihan Ganda dengan Bobot', 'Huruf opsi jawaban benar. Contoh: C'],
      ['PGJ', 'Pilihan Ganda Jamak (multi-jawaban)', 'Huruf dipisah koma. Contoh: A,C'],
      ['BS', 'Benar/Salah', 'Isi B (Benar) atau S (Salah)'],
      ['ISIAN', 'Isian Singkat', 'Kata kunci jawaban, pisah koma jika lebih dari satu. Contoh: Jakarta,Ibukota'],
      ['URAIAN', 'Uraian / Essay', 'Kosongkan. Dinilai manual oleh guru.'],
      ['JODOH', 'Menjodohkan', 'Kosongkan. Pasangan dikelola via halaman edit soal.'],
      ['CLOZE', 'Isian Melengkapi (Close Test)', 'Kosongkan. Pasangan dikelola via halaman edit soal.'],
      ['', '', ''],

      // Section: Catatan penting
      ['CATATAN PENTING', '', ''],
      ['1.', 'Baris pertama adalah header — jangan diubah atau dihapus.', ''],
      ['2.', 'Kolom tipe_soal, teks_soal, dan kesulitan adalah WAJIB diisi.', ''],
      ['3.', 'Tipe PG / PG_BOBOT / PGJ membutuhkan minimal opsi_a dan opsi_b.', ''],
      ['4.', 'Kolom kunci untuk tipe PG / PG_BOBOT / PGJ harus huruf kapital (A–E).', ''],
      ['5.', 'Untuk PGJ, pisahkan kunci dengan koma. Contoh: A,C', ''],
      ['6.', 'Kategori yang belum ada di sistem akan dibuat otomatis saat import.', ''],
      ['7.', 'Baris dengan data tidak valid akan dilewati (tidak diimport).', ''],
      ['8.', 'Halaman review akan menampilkan status valid/tidak per baris sebelum import dijalankan.', ''],
    ];
  }

  public function columnWidths(): array
  {
    return [
      'A' => 20,
      'B' => 70,
      'C' => 50,
    ];
  }

  public function styles(Worksheet $sheet): array
  {
    // Judul utama
    $sheet->getStyle('A1')->applyFromArray([
      'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1e3a8a']],
      'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
    ]);

    // Merge judul
    $sheet->mergeCells('A1:C1');

    // Section headers (baris 3, 17, 28)
    foreach ([3, 17, 28] as $row) {
      $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e40af']],
      ]);
      $sheet->mergeCells("A{$row}:C{$row}");
    }

    // Sub-header rows (baris 4 dan 18 — kolom header tabel)
    foreach ([4, 18] as $row) {
      $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => '1e3a8a']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'dbeafe']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
      ]);
    }

    // Borders untuk tabel kolom (baris 4–14)
    $sheet->getStyle('A4:C15')->applyFromArray([
      'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'bfdbfe']]],
    ]);

    // Borders untuk tabel tipe soal (baris 18–27)
    $sheet->getStyle('A18:C27')->applyFromArray([
      'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'bfdbfe']]],
    ]);

    // Wrap text semua kolom B
    $sheet->getStyle('B1:B100')->getAlignment()->setWrapText(true);
    $sheet->getStyle('C1:C100')->getAlignment()->setWrapText(true);

    // Alternating rows tabel kolom
    for ($r = 5; $r <= 15; $r++) {
      $color = ($r % 2 === 0) ? 'EFF6FF' : 'FFFFFF';
      $sheet->getStyle("A{$r}:C{$r}")->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setRGB($color);
    }

    // Alternating rows tabel tipe soal
    for ($r = 19; $r <= 27; $r++) {
      $color = ($r % 2 === 0) ? 'EFF6FF' : 'FFFFFF';
      $sheet->getStyle("A{$r}:C{$r}")->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setRGB($color);
    }

    // Section catatan — label nomor bold
    $sheet->getStyle('A29:A38')->getFont()->setBold(true);

    return [];
  }
}
