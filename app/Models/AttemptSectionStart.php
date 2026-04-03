<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttemptSectionStart extends Model
{
  public $timestamps = false;

  protected $fillable = [
    'attempt_id',
    'section_id',
    'waktu_mulai',
  ];

  protected function casts(): array
  {
    return [
      'waktu_mulai' => 'datetime',
    ];
  }

  public function attempt(): \Illuminate\Database\Eloquent\Relations\BelongsTo
  {
    return $this->belongsTo(ExamAttempt::class, 'attempt_id');
  }

  public function section(): \Illuminate\Database\Eloquent\Relations\BelongsTo
  {
    return $this->belongsTo(ExamSection::class, 'section_id');
  }

  /**
   * Remaining seconds for this section (0 if expired).
   */
  public function sisaWaktuDetik(): int
  {
    return max(0, (int) ($this->waktu_mulai
      ->copy()
      ->addMinutes($this->section->durasi_menit)
      ->diffInSeconds(now(), false) * -1));
  }
}
