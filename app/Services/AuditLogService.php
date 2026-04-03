<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
  public static function log(string $action, ?Model $model = null, ?string $deskripsi = null): void
  {
    AuditLog::create([
      'user_id'    => Auth::id(),
      'action'     => $action,
      'model_type' => $model ? class_basename($model) : null,
      'model_id'   => $model?->getKey(),
      'deskripsi'  => $deskripsi,
      'ip_address' => Request::ip(),
      'user_agent' => Request::userAgent(),
    ]);
  }
}
