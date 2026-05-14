<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MataPelajaran extends Model
{
  protected $table = 'mata_pelajaran';

  const JENJANG_LABELS = [
    'SD'    => 'SD',
    'SMP'   => 'SMP',
    'SMA'   => 'SMA',
    'SMK'   => 'SMK',
    'Umum'  => 'Umum',
  ];

  protected $fillable = [
    'nama',
    'kode',
    'jenjang',
    'keterangan',
    'aktif',
    'created_by',
  ];

  protected function casts(): array
  {
    return [
      'aktif' => 'boolean',
    ];
  }

  public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }
}
