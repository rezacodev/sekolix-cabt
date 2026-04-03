<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
  const UPDATED_AT = null;

  protected $fillable = [
    'user_id',
    'action',
    'model_type',
    'model_id',
    'deskripsi',
    'ip_address',
    'user_agent',
  ];

  protected function casts(): array
  {
    return [
      'created_at' => 'datetime',
    ];
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }
}
