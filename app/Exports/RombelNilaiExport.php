<?php

namespace App\Exports;

use App\Models\ExamAttempt;
use App\Models\ExamSession;
use App\Models\Rombel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class RombelNilaiExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize
{
    private int $rowNo = 0;

    public function __construct(
        private readonly ExamSession $session,
        private readonly Rombel $rombel,
    ) {}

    public function title(): string
    {
        return str($this->rombel->nama)->limit(28)->toString();
    }

    public function headings(): array
    {
        return ['No', 'Nama Peserta', 'No. Peserta', 'Nilai', 'Benar', 'Salah', 'Kosong', 'Attempt Ke', 'Status', 'Durasi'];
    }

    public function collection()
    {
        $peserta    = $this->rombel->peserta()->orderBy('name')->get();
        $pesertaIds = $peserta->pluck('id');

        // Single query: load all attempts, reuse for both best-attempt and count
        $allAttempts = ExamAttempt::where('exam_session_id', $this->session->id)
            ->whereIn('user_id', $pesertaIds)
            ->get()
            ->groupBy('user_id');

        $bestAttempts  = $allAttempts->map(fn($g) => $g->sortByDesc('nilai_akhir')->first());
        $attemptCounts = $allAttempts->map(fn($g) => $g->count());

        $this->rowNo = 0;

        return $peserta->map(function ($p) use ($bestAttempts, $attemptCounts) {
            $attempt = $bestAttempts->get($p->id);

            $durasi = '—';
            if ($attempt && $attempt->waktu_selesai && $attempt->waktu_mulai) {
                $totalDetik = (int) abs($attempt->waktu_mulai->diffInSeconds($attempt->waktu_selesai));
                $menit      = intdiv($totalDetik, 60);
                $detik      = $totalDetik % 60;
                $durasi     = $menit . 'm ' . str_pad($detik, 2, '0', STR_PAD_LEFT) . 'd';
            }

            return (object) [
                'nama'          => $p->name,
                'nomor_peserta' => $p->nomor_peserta ?? '—',
                'nilai'         => $attempt?->nilai_akhir,
                'benar'         => $attempt?->jumlah_benar,
                'salah'         => $attempt?->jumlah_salah,
                'kosong'        => $attempt?->jumlah_kosong,
                'attempt_ke'    => $attemptCounts->get($p->id, 0),
                'status'        => $attempt?->status,
                'durasi'        => $durasi,
            ];
        });
    }

    public function map($row): array
    {
        $this->rowNo++;

        return [
            $this->rowNo,
            $row->nama,
            $row->nomor_peserta,
            $row->nilai !== null ? number_format((float) $row->nilai, 1) : '—',
            $row->benar ?? '—',
            $row->salah ?? '—',
            $row->kosong ?? '—',
            $row->attempt_ke > 0 ? $row->attempt_ke . '×' : '—',
            $row->status ? (ExamAttempt::STATUS_LABELS[$row->status] ?? $row->status) : 'Belum Mengerjakan',
            $row->durasi,
        ];
    }
}
