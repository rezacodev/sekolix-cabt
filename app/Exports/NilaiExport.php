<?php

namespace App\Exports;

use App\Models\ExamAttempt;
use App\Models\ExamSession;
use App\Services\ReportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NilaiExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles
{
    private ExamSession $session;
    private Collection $data;

    public function __construct(ExamSession $session)
    {
        $this->session = $session;
        $this->data    = app(ReportService::class)->rekapNilai($session->id);
    }

    public function collection(): Collection
    {
        return $this->data;
    }

    public function title(): string
    {
        return 'Rekap Nilai';
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Peserta',
            'Nomor Peserta',
            'Rombel',
            'Nilai Akhir',
            'Benar',
            'Salah',
            'Kosong',
            'Status',
            'Attempt Ke',
            'Durasi',
        ];
    }

    public function map($row): array
    {
        $durasi = $row->durasi_detik !== null
            ? sprintf('%d menit %02d detik', intdiv($row->durasi_detik, 60), $row->durasi_detik % 60)
            : '—';

        return [
            $row->no,
            $row->nama,
            $row->nomor_peserta,
            $row->rombel_nama,
            $row->nilai_akhir !== null ? number_format((float) $row->nilai_akhir, 2) : '—',
            $row->jumlah_benar,
            $row->jumlah_salah,
            $row->jumlah_kosong,
            ExamAttempt::STATUS_LABELS[$row->status] ?? $row->status,
            $row->attempt_ke,
            $durasi,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 11]],
        ];
    }
}
