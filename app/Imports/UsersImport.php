<?php

namespace App\Imports;

use App\Models\Rombel;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsersImport implements ToCollection, WithHeadingRow
{
    public array $errors = [];
    public int $imported = 0;

    public function collection(Collection $rows): void
    {
        // Cache rombel kode → id untuk menghindari query berulang
        $rombelMap = Rombel::pluck('id', 'kode');

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            $data = [
                'nama_lengkap'  => $row['nama_lengkap'] ?? null,
                'username'      => $row['username'] ?? null,
                'email'         => $row['email'] ?? null,
                'password'      => $row['password'] ?? null,
                'level'         => (int) ($row['level'] ?? 1),
                'nomor_peserta' => $row['nomor_peserta'] ?? null,
                'kode_rombel'   => $row['kode_rombel'] ?? null,
            ];

            $validator = Validator::make($data, [
                'nama_lengkap'  => ['required', 'string', 'max:255'],
                'username'      => ['nullable', 'string', 'max:50', 'unique:users,username', 'regex:/^[a-zA-Z0-9._-]+$/'],
                'email'         => ['required', 'email', 'unique:users,email'],
                'password'      => ['required', 'string', 'min:6'],
                'level'         => ['required', 'integer', 'in:1,2,3,4'],
                'nomor_peserta' => ['nullable', 'string', 'max:50', 'unique:users,nomor_peserta'],
                'kode_rombel'   => ['nullable', 'string'],
            ]);

            if ($validator->fails()) {
                $this->errors[] = "Baris {$rowNumber}: " . implode(' | ', $validator->errors()->all());
                continue;
            }

            // Resolve rombel_id dari kode_rombel
            $rombelId = null;
            if (!empty($data['kode_rombel'])) {
                $rombelId = $rombelMap->get($data['kode_rombel']);
                if (!$rombelId) {
                    $this->errors[] = "Baris {$rowNumber}: Kode rombel '{$data['kode_rombel']}' tidak ditemukan.";
                    continue;
                }
            }

            User::create([
                'name'          => $data['nama_lengkap'],
                'username'      => $data['username'] ?: null,
                'email'         => $data['email'],
                'password'      => Hash::make($data['password']),
                'level'         => $data['level'],
                'nomor_peserta' => $data['nomor_peserta'] ?: null,
                'rombel_id'     => $rombelId,
                'aktif'         => true,
            ]);

            $this->imported++;
        }
    }
}
