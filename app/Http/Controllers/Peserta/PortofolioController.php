<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use Illuminate\Http\Request;

class PortofolioController extends Controller
{
  public function index(Request $request): \Illuminate\View\View
  {
    $userId = $request->user()->id;

    $attempts = ExamAttempt::with(['session.package'])
      ->where('user_id', $userId)
      ->whereIn('status', [
        ExamAttempt::STATUS_SELESAI,
        ExamAttempt::STATUS_TIMEOUT,
        ExamAttempt::STATUS_DISKUALIFIKASI,
      ])
      ->orderByDesc('waktu_mulai')
      ->get();

    // Chart.js data: nilai per sesi (maks 20 terakhir, urutan kronologis)
    $chartData = $attempts->take(20)->reverse()->values()->map(fn($a) => [
      'label' => str($a->session->nama_sesi ?? '?')->limit(25)->toString(),
      'nilai' => $a->nilai_akhir !== null ? (float) $a->nilai_akhir : null,
      'date'  => $a->waktu_mulai?->format('d M Y'),
    ]);

    // Statistik ringkasan
    $selesai = $attempts->where('status', ExamAttempt::STATUS_SELESAI);
    $nilaiArr = $selesai->filter(fn($a) => $a->nilai_akhir !== null)->pluck('nilai_akhir')->map(fn($v) => (float) $v);

    $stats = [
      'total'    => $attempts->count(),
      'rata'     => $nilaiArr->count() ? round($nilaiArr->avg(), 2) : null,
      'tertinggi' => $nilaiArr->count() ? round($nilaiArr->max(), 2) : null,
      'terendah' => $nilaiArr->count() ? round($nilaiArr->min(), 2) : null,
    ];

    return view('peserta.portofolio', compact('attempts', 'chartData', 'stats'));
  }
}
