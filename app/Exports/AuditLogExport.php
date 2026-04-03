<?php

namespace App\Exports;

use App\Models\AuditLog;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class AuditLogExport implements FromQuery, WithHeadings, WithMapping, WithTitle
{
  public function query()
  {
    return AuditLog::query()->with('user')->latest();
  }

  public function title(): string
  {
    return 'Audit Log';
  }

  public function headings(): array
  {
    return ['ID', 'Waktu', 'User', 'Aksi', 'Model', 'Model ID', 'Deskripsi', 'IP Address', 'User Agent'];
  }

  public function map($log): array
  {
    return [
      $log->id,
      $log->created_at->format('d/m/Y H:i:s'),
      $log->user?->name ?? '-',
      $log->action,
      $log->model_type ?? '-',
      $log->model_id ?? '-',
      $log->deskripsi ?? '-',
      $log->ip_address ?? '-',
      $log->user_agent ?? '-',
    ];
  }
}
