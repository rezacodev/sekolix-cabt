<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KalenderController extends Controller
{
  public function data(Request $request): JsonResponse
  {
    $start = $request->query('start');
    $end   = $request->query('end');

    /** @var \App\Models\User $user */
    $user = Auth::user();

    $query = ExamSession::query()
      ->with('package:id,nama')
      ->where('status', '!=', ExamSession::STATUS_DIBATALKAN);

    // Guru hanya melihat sesi milik sendiri
    if ($user->level === User::LEVEL_GURU) {
      $query->where('created_by', $user->id);
    }

    if ($start) {
      $query->where('waktu_selesai', '>=', $start);
    }
    if ($end) {
      $query->where('waktu_mulai', '<=', $end);
    }

    $colorMap = [
      ExamSession::STATUS_DRAFT   => '#6b7280',
      ExamSession::STATUS_AKTIF   => '#16a34a',
      ExamSession::STATUS_SELESAI => '#2563eb',
    ];

    $events = $query->get()->map(function (ExamSession $session) use ($colorMap): array {
      return [
        'id'    => $session->id,
        'title' => $session->nama_sesi,
        'start' => $session->waktu_mulai?->toIso8601String(),
        'end'   => $session->waktu_selesai?->toIso8601String(),
        'color' => $colorMap[$session->status] ?? '#6b7280',
        'url'   => url('/cabt/sesi/' . $session->id . '/edit'),
        'extendedProps' => [
          'status'  => $session->status,
          'package' => $session->package?->nama,
        ],
      ];
    });

    return response()->json($events);
  }
}
