<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class UsersExport implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private ?int $levelFilter = null) {}

    public function query()
    {
        $query = User::query()->orderBy('level')->orderBy('name');
        if ($this->levelFilter) {
            $query->where('level', $this->levelFilter);
        }
        return $query;
    }

    public function title(): string
    {
        return 'Daftar User';
    }

    public function headings(): array
    {
        return ['ID', 'Nama Lengkap', 'Username', 'Email', 'Level', 'Nomor Peserta', 'Kode Rombel', 'Aktif', 'Dibuat'];
    }

    public function map($user): array
    {
        $rombelKodes = $user->level === \App\Models\User::LEVEL_PESERTA
            ? $user->rombels()->orderBy('kode')->pluck('kode')->implode(';')
            : ($user->rombelsAmpu()->orderBy('kode')->pluck('kode')->implode(';') ?: '-');

        return [
            $user->id,
            $user->name,
            $user->username ?? '-',
            $user->email,
            \App\Models\User::levelToCode($user->level),
            $user->nomor_peserta ?? '-',
            $rombelKodes ?: '-',
            $user->aktif ? 'Ya' : 'Tidak',
            $user->created_at->format('d/m/Y'),
        ];
    }
}
