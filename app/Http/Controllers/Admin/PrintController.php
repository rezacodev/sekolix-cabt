<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamBlueprint;
use App\Models\ExamSession;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrintController extends Controller
{
    public function __construct(private ReportService $reportService) {}

    /** Rekap nilai — printable */
    public function nilai(ExamSession $session)
    {
        $this->authorize($session);

        $rekap        = $this->reportService->rekapNilai($session->id);
        $statistik    = $this->reportService->statistikNilai($session->id);
        $schoolName   = \App\Models\AppSetting::getString('school_name', '');
        $schoolLogoUrl = \App\Models\AppSetting::getString('school_logo_url', '');

        return view('print.rekap-nilai', compact('session', 'rekap', 'statistik', 'schoolName', 'schoolLogoUrl'));
    }

    /** Daftar hadir — printable */
    public function daftarHadir(ExamSession $session)
    {
        $this->authorize($session);

        $kehadiran    = $this->reportService->rekapKehadiran($session->id);
        $schoolName   = \App\Models\AppSetting::getString('school_name', '');
        $schoolLogoUrl = \App\Models\AppSetting::getString('school_logo_url', '');

        return view('print.daftar-hadir', compact('session', 'kehadiran', 'schoolName', 'schoolLogoUrl'));
    }

    /** Berita acara ujian — printable */
    public function beritaAcara(ExamSession $session)
    {
        $this->authorize($session);

        $kehadiran    = $this->reportService->rekapKehadiran($session->id);
        $rekap        = $this->reportService->rekapNilai($session->id);
        $schoolName   = \App\Models\AppSetting::getString('school_name', '');
        $schoolLogoUrl = \App\Models\AppSetting::getString('school_logo_url', '');

        return view('print.berita-acara', compact('session', 'kehadiran', 'rekap', 'schoolName', 'schoolLogoUrl'));
    }

    /** Cetak kisi-kisi blueprint — printable */
    public function blueprint(ExamBlueprint $blueprint)
    {
        $schoolName    = \App\Models\AppSetting::getString('school_name', '');
        $schoolLogoUrl = \App\Models\AppSetting::getString('school_logo_url', '');
        $blueprint->load(['items.category', 'items.standard', 'items.tag', 'creator']);

        return view('print.kisi-kisi', compact('blueprint', 'schoolName', 'schoolLogoUrl'));
    }

    /** Cetak kisi-kisi blueprint format formal (sesuai format dokumen resmi) — printable */
    public function blueprintFormal(ExamBlueprint $blueprint)
    {
        $schoolName    = \App\Models\AppSetting::getString('school_name', '');
        $schoolLogoUrl = \App\Models\AppSetting::getString('school_logo_url', '');

        $blueprint->load([
            'items' => fn($q) => $q->orderBy('capaian_pembelajaran')
                                    ->orderBy('materi')
                                    ->orderBy('urutan'),
            'items.category',
            'items.standard',
            'items.tag',
            'creator',
        ]);

        $bentukMap = [
            'PG'       => 'PG',
            'PG_BOBOT' => 'PG',
            'PGJ'      => 'PGJ',
            'BS'       => 'B-S',
            'JODOH'    => 'MENJODOHKAN',
            'ISIAN'    => 'ISIAN',
            'CLOZE'    => 'ISIAN',
            'URAIAN'   => 'URAIAN',
        ];

        $bentukSoalList = $blueprint->items
            ->map(fn($i) => $bentukMap[$i->tipe_soal] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();

        return view('print.kisi-kisi-formal', compact(
            'blueprint', 'schoolName', 'schoolLogoUrl', 'bentukMap', 'bentukSoalList'
        ));
    }

    /** Pastikan Guru hanya cetak sesi miliknya. */
    private function authorize(ExamSession $session): void
    {
        $user = Auth::user();
        if ($user->level === \App\Models\User::LEVEL_GURU && $session->created_by !== $user->id) {
            abort(403);
        }
    }
}
