<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AnalisisUlanganExport;
use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\ExamSession;
use App\Models\User;
use App\Services\AnalisisService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class AnalisisUlanganController extends Controller
{
    public function __construct(private readonly AnalisisService $service) {}

    public function index(ExamSession $session)
    {
        $this->gate($session);
        [$analisisData, $hasilData, $pengayaanData, $schoolName, $schoolLogoUrl] = $this->buildData($session);

        return view('print.analisis-ulangan', compact(
            'session', 'analisisData', 'hasilData', 'pengayaanData', 'schoolName', 'schoolLogoUrl'
        ));
    }

    public function exportPdf(ExamSession $session)
    {
        $this->gate($session);
        [$analisisData, $hasilData, $pengayaanData, $schoolName, $schoolLogoUrl] = $this->buildData($session);

        $pdf = Pdf::loadView('print.analisis-ulangan', compact(
            'session', 'analisisData', 'hasilData', 'pengayaanData', 'schoolName', 'schoolLogoUrl'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('analisis-ulangan-' . str($session->nama_sesi)->slug() . '.pdf');
    }

    public function exportExcel(ExamSession $session)
    {
        $this->gate($session);
        return Excel::download(
            new AnalisisUlanganExport($session, $this->service),
            'analisis-ulangan-' . str($session->nama_sesi)->slug() . '.xlsx'
        );
    }

    // ── Private ──────────────────────────────────────────────────────────────

    private function buildData(ExamSession $session): array
    {
        $analisisData  = $this->service->analisisUlangan($session->id);
        $hasilData     = $this->service->hasilAnalisis($analisisData);
        $pengayaanData = $this->service->programPengayaan($analisisData);
        $schoolName    = AppSetting::getString('school_name', '');
        $schoolLogoUrl = AppSetting::getString('school_logo_url', '');

        return [$analisisData, $hasilData, $pengayaanData, $schoolName, $schoolLogoUrl];
    }

    private function gate(ExamSession $session): void
    {
        /** @var User $user */
        $user = Auth::user();
        if ($user->level === User::LEVEL_GURU && $session->created_by !== $user->id) {
            abort(403);
        }
    }
}
