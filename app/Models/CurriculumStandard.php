<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurriculumStandard extends Model
{
  const JENJANG_LABELS = [
    'SD'            => 'SD',
    'SMP'           => 'SMP',
    'SMA'           => 'SMA',
    'SMK'           => 'SMK',
  ];

  const KURIKULUM_LABELS = [
    'K13'           => 'Kurikulum 2013',
    'Merdeka'       => 'Kurikulum Merdeka',
    'Internasional' => 'Internasional',
  ];

  const BLOOM_LABELS = [
    'C1' => 'C1 — Mengingat',
    'C2' => 'C2 — Memahami',
    'C3' => 'C3 — Mengaplikasikan',
    'C4' => 'C4 — Menganalisis',
    'C5' => 'C5 — Mengevaluasi',
    'C6' => 'C6 — Mencipta',
  ];

  protected $fillable = [
    'kode',
    'nama',
    'mata_pelajaran',
    'jenjang',
    'kurikulum',
    'kelas',
    'tingkat_kognitif',
    'created_by',
  ];

  public function questions(): \Illuminate\Database\Eloquent\Relations\HasMany
  {
    return $this->hasMany(Question::class, 'curriculum_standard_id');
  }

  public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function getFullLabelAttribute(): string
  {
    return "[{$this->kode}] {$this->mata_pelajaran} — {$this->nama}";
  }
}
