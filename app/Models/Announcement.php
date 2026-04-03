<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
  const TIPE_INFO    = 'info';
  const TIPE_WARNING = 'warning';
  const TIPE_PENTING = 'penting';

  const TIPE_LABELS = [
    self::TIPE_INFO    => 'Info',
    self::TIPE_WARNING => 'Peringatan',
    self::TIPE_PENTING => 'Penting',
  ];

  const TARGET_SEMUA     = 'semua';
  const TARGET_PER_ROMBEL = 'per_rombel';

  protected $fillable = [
    'judul',
    'isi',
    'tipe',
    'target',
    'rombel_id',
    'tanggal_mulai',
    'tanggal_selesai',
    'aktif',
  ];

  protected function casts(): array
  {
    return [
      'tanggal_mulai'   => 'datetime',
      'tanggal_selesai' => 'datetime',
      'aktif'           => 'boolean',
    ];
  }

  /** Pengumuman yang sedang aktif dan dalam rentang tanggal. */
  public function scopeAktif(Builder $query): Builder
  {
    return $query
      ->where('aktif', true)
      ->where(fn($q) => $q->whereNull('tanggal_mulai')->orWhere('tanggal_mulai', '<=', now()))
      ->where(fn($q) => $q->whereNull('tanggal_selesai')->orWhere('tanggal_selesai', '>=', now()));
  }

  public function rombel(): BelongsTo
  {
    return $this->belongsTo(Rombel::class);
  }
}
