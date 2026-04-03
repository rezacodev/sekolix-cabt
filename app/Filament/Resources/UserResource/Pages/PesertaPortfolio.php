<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\ExamAttempt;
use App\Models\User;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;

class PesertaPortfolio extends Page
{
  protected static string $resource = UserResource::class;

  protected static string $view = 'filament.resources.user-resource.pages.peserta-portfolio';

  public User $record;

  public function mount(User $record): void
  {
    /** @var \App\Models\User $viewer */
    $viewer = Auth::user();

    // Only admin+ can view another user's portfolio
    if ($viewer->level < User::LEVEL_ADMIN) {
      abort(403);
    }

    if ($record->level !== User::LEVEL_PESERTA) {
      abort(404, 'Halaman ini hanya tersedia untuk akun peserta.');
    }

    $this->record = $record;
  }

  public function getTitle(): string
  {
    return 'Portofolio — ' . $this->record->name;
  }

  public function getBreadcrumbs(): array
  {
    return [
      UserResource::getUrl()           => 'Manajemen User',
      '#'                              => 'Portofolio ' . str($this->record->name)->limit(30),
    ];
  }

  public function getViewData(): array
  {
    $userId = $this->record->id;

    $attempts = ExamAttempt::with(['session.package'])
      ->where('user_id', $userId)
      ->whereIn('status', [
        ExamAttempt::STATUS_SELESAI,
        ExamAttempt::STATUS_TIMEOUT,
        ExamAttempt::STATUS_DISKUALIFIKASI,
      ])
      ->orderByDesc('waktu_mulai')
      ->get();

    $chartData = $attempts->take(20)->reverse()->values()->map(fn($a) => [
      'label' => str($a->session->nama_sesi ?? '?')->limit(25)->toString(),
      'nilai' => $a->nilai_akhir !== null ? (float) $a->nilai_akhir : null,
      'date'  => $a->waktu_mulai?->format('d M Y'),
    ]);

    $selesai  = $attempts->where('status', ExamAttempt::STATUS_SELESAI);
    $nilaiArr = $selesai->filter(fn($a) => $a->nilai_akhir !== null)->pluck('nilai_akhir')->map(fn($v) => (float) $v);

    $stats = [
      'total'     => $attempts->count(),
      'rata'      => $nilaiArr->count() ? round($nilaiArr->avg(), 2) : null,
      'tertinggi' => $nilaiArr->count() ? round($nilaiArr->max(), 2) : null,
      'terendah'  => $nilaiArr->count() ? round($nilaiArr->min(), 2) : null,
    ];

    return compact('attempts', 'chartData', 'stats');
  }
}
