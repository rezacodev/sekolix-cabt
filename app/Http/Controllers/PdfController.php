<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\ExamSession;
use App\Models\User;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PdfController extends Controller
{
  public function __construct(private ReportService $reportService) {}

  /** Rekap Nilai PDF */
  public function nilai(ExamSession $session)
  {
    $this->authorize($session);

    $rekap         = $this->reportService->rekapNilai($session->id);
    $statistik     = $this->reportService->statistikNilai($session->id);
    $schoolName    = AppSetting::getString('school_name', '');
    $schoolLogoUrl = AppSetting::getString('school_logo_url', '');

    $pdf = Pdf::loadView('print.rekap-nilai', compact(
      'session',
      'rekap',
      'statistik',
      'schoolName',
      'schoolLogoUrl'
    ))->setPaper('a4', 'portrait');

    return $pdf->download('rekap-nilai-' . str($session->nama_sesi)->slug() . '.pdf');
  }

  /** Daftar Hadir PDF */
  public function daftarHadir(ExamSession $session)
  {
    $this->authorize($session);

    $kehadiran     = $this->reportService->rekapKehadiran($session->id);
    $schoolName    = AppSetting::getString('school_name', '');
    $schoolLogoUrl = AppSetting::getString('school_logo_url', '');

    $pdf = Pdf::loadView('print.daftar-hadir', compact(
      'session',
      'kehadiran',
      'schoolName',
      'schoolLogoUrl'
    ))->setPaper('a4', 'portrait');

    return $pdf->download('daftar-hadir-' . str($session->nama_sesi)->slug() . '.pdf');
  }

  /** Berita Acara PDF */
  public function beritaAcara(ExamSession $session)
  {
    $this->authorize($session);

    $kehadiran     = $this->reportService->rekapKehadiran($session->id);
    $rekap         = $this->reportService->rekapNilai($session->id);
    $schoolName    = AppSetting::getString('school_name', '');
    $schoolLogoUrl = AppSetting::getString('school_logo_url', '');

    $pdf = Pdf::loadView('print.berita-acara', compact(
      'session',
      'kehadiran',
      'rekap',
      'schoolName',
      'schoolLogoUrl'
    ))->setPaper('a4', 'portrait');

    return $pdf->download('berita-acara-' . str($session->nama_sesi)->slug() . '.pdf');
  }

  /** Kartu Peserta PDF — 4 kartu per halaman A4 */
  public function kartupeserta(ExamSession $session)
  {
    $this->authorize($session);

    $participants  = $session->participants()->with('user.rombel')->get();
    $schoolName    = AppSetting::getString('school_name', '');
    $schoolLogoUrl = AppSetting::getString('school_logo_url', '');

    // Generate QR per peserta (base64 PNG, 80x80)
    $qrCodes = [];
    foreach ($participants as $participant) {
      $nomorPeserta = $participant->user?->nomor_peserta;
      if ($nomorPeserta) {
        $svg = QrCode::format('png')->size(80)->generate($nomorPeserta);
        $qrCodes[$participant->user_id] = 'data:image/png;base64,' . base64_encode($svg);
      }
    }

    $pdf = Pdf::loadView('print.kartu-peserta-pdf', compact(
      'session',
      'participants',
      'schoolName',
      'schoolLogoUrl',
      'qrCodes'
    ))->setPaper('a4', 'portrait');

    return $pdf->download('kartu-peserta-' . str($session->nama_sesi)->slug() . '.pdf');
  }

  private function authorize(ExamSession $session): void
  {
    /** @var \App\Models\User $user */
    $user = Auth::user();
    if ($user->level === User::LEVEL_GURU && $session->created_by !== $user->id) {
      abort(403);
    }
  }
}
