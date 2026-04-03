<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionNote extends Model
{
  public $timestamps = false;

  protected $fillable = ['exam_session_id', 'user_id', 'catatan', 'created_at'];

  protected function casts(): array
  {
    return [
      'created_at' => 'datetime',
    ];
  }

  public function session(): BelongsTo
  {
    return $this->belongsTo(ExamSession::class, 'exam_session_id');
  }

  public function author(): BelongsTo
  {
    return $this->belongsTo(User::class, 'user_id');
  }
}
