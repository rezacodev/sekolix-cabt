<?php

namespace App\Imports;

use App\Models\Rombel;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;

class UsersImport implements ToCollection, WithHeadingRow
{
    public array $errors   = [];
    public int   $imported = 0;

    // ── Parse tanpa tulis ke DB (untuk preview) ───────────────────────

    /**
     * Baca file Excel dan kembalikan array baris dengan status valid/invalid
     * tanpa menulis apapun ke database.
     *
     * @return array<int, array{row: array, valid: bool, errors: string[], resolved: array}>
     */
    public static function parseForPreview(string $filePath): array
    {
        $rombelMap = Rombel::pluck('id', 'kode');
        $result    = [];

        $rows = Excel::toCollection(new self(), $filePath)->first() ?? collect();

        $existingEmails    = User::pluck('email')->flip();
        $existingUsernames = User::whereNotNull('username')->pluck('username')->flip();
        $existingNomors    = User::whereNotNull('nomor_peserta')->pluck('nomor_peserta')->flip();

        // Track values seen in this file to detect intra-file duplicates
        $seenEmails    = [];
        $seenUsernames = [];
        $seenNomors    = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $data      = self::normalizeRow($row->toArray());

            $errors      = [];
            $resolved    = [];
            $rombelKodes = [];
            $rombelIds   = [];

            // ── Validasi level ─────────────────────────────────────
            $levelInt = User::levelFromCode($data['level'] ?? '');
            if ($levelInt === null) {
                $errors[] = "Level '{$data['level']}' tidak dikenal. Gunakan: " . implode(', ', array_keys(User::LEVEL_CODES));
            } else {
                $resolved['level'] = $levelInt;
            }

            // ── Validasi field umum ────────────────────────────────
            $validator = Validator::make($data, [
                'nama_lengkap'  => ['required', 'string', 'max:255'],
                'username'      => ['nullable', 'string', 'max:50', 'regex:/^[a-zA-Z0-9._-]+$/'],
                'email'         => ['required', 'email', 'max:255'],
                'password'      => ['required', 'string', 'min:6'],
                'nomor_peserta' => ['nullable', 'string', 'max:50'],
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $msg) {
                    $errors[] = $msg;
                }
            }

            // ── Cek duplikat email ─────────────────────────────────
            $email = strtolower(trim($data['email'] ?? ''));
            if ($email) {
                if (isset($existingEmails[$email])) {
                    $errors[] = "Email '{$email}' sudah terdaftar di sistem.";
                } elseif (in_array($email, $seenEmails, true)) {
                    $errors[] = "Email '{$email}' duplikat dalam file ini.";
                } else {
                    $seenEmails[] = $email;
                }
            }

            // ── Cek duplikat username ──────────────────────────────
            $uname = trim($data['username'] ?? '');
            if ($uname) {
                if (isset($existingUsernames[$uname])) {
                    $errors[] = "Username '{$uname}' sudah terdaftar di sistem.";
                } elseif (in_array($uname, $seenUsernames, true)) {
                    $errors[] = "Username '{$uname}' duplikat dalam file ini.";
                } else {
                    $seenUsernames[] = $uname;
                }
            }

            // ── Cek duplikat nomor_peserta ─────────────────────────
            $nomor = trim($data['nomor_peserta'] ?? '');
            if ($nomor) {
                if (isset($existingNomors[$nomor])) {
                    $errors[] = "Nomor peserta '{$nomor}' sudah terdaftar di sistem.";
                } elseif (in_array($nomor, $seenNomors, true)) {
                    $errors[] = "Nomor peserta '{$nomor}' duplikat dalam file ini.";
                } else {
                    $seenNomors[] = $nomor;
                }
            }

            // ── Resolve kode_rombel (bisa multi, pisah ;) ──────────
            $kodeRombelRaw = trim($data['kode_rombel'] ?? '');
            if ($kodeRombelRaw !== '') {
                foreach (preg_split('/[;,]+/', $kodeRombelRaw) as $kode) {
                    $kode = trim($kode);
                    if ($kode === '') continue;
                    $rombelId = $rombelMap->get($kode);
                    if (!$rombelId) {
                        $errors[] = "Kode rombel '{$kode}' tidak ditemukan.";
                    } else {
                        $rombelKodes[] = $kode;
                        $rombelIds[]   = $rombelId;
                    }
                }
            }
            $resolved['rombel_ids']   = array_unique($rombelIds);
            $resolved['rombel_kodes'] = $rombelKodes;

            $result[] = [
                'row'      => $data,
                'row_num'  => $rowNumber,
                'valid'    => empty($errors),
                'errors'   => $errors,
                'resolved' => $resolved,
            ];
        }

        return $result;
    }

    // ── Import ke DB (hanya baris valid) ──────────────────────────────

    /**
     * Import baris-baris valid yang sudah diparsing untuk preview.
     * Terima hasil dari parseForPreview() — tidak re-parse file.
     */
    public static function importValidRows(array $parsedRows): array
    {
        $imported = 0;
        $errors   = [];

        foreach ($parsedRows as $item) {
            if (!$item['valid']) continue;

            $data     = $item['row'];
            $resolved = $item['resolved'];

            try {
                $user = User::create([
                    'name'          => $data['nama_lengkap'],
                    'username'      => $data['username'] ?: null,
                    'email'         => strtolower(trim($data['email'])),
                    'password'      => Hash::make($data['password']),
                    'level'         => $resolved['level'],
                    'nomor_peserta' => $data['nomor_peserta'] ?: null,
                    'rombel_id'     => $resolved['rombel_ids'][0] ?? null,
                    'aktif'         => true,
                ]);

                if (!empty($resolved['rombel_ids']) && $resolved['level'] === User::LEVEL_PESERTA) {
                    $user->rombels()->sync($resolved['rombel_ids']);
                }

                $imported++;
            } catch (\Throwable $e) {
                $errors[] = "Baris {$item['row_num']}: " . $e->getMessage();
            }
        }

        return ['imported' => $imported, 'errors' => $errors];
    }

    // ── ToCollection (legacy / ImportService compat) ──────────────────

    public function collection(Collection $rows): void
    {
        $rombelMap = Rombel::pluck('id', 'kode');

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $data      = self::normalizeRow($row->toArray());

            $levelInt = User::levelFromCode($data['level'] ?? '');
            if ($levelInt === null) {
                $this->errors[] = "Baris {$rowNumber}: Level '{$data['level']}' tidak dikenal.";
                continue;
            }

            $validator = Validator::make($data, [
                'nama_lengkap'  => ['required', 'string', 'max:255'],
                'username'      => ['nullable', 'string', 'max:50', 'unique:users,username', 'regex:/^[a-zA-Z0-9._-]+$/'],
                'email'         => ['required', 'email', 'unique:users,email'],
                'password'      => ['required', 'string', 'min:6'],
                'nomor_peserta' => ['nullable', 'string', 'max:50', 'unique:users,nomor_peserta'],
            ]);

            if ($validator->fails()) {
                $this->errors[] = "Baris {$rowNumber}: " . implode(' | ', $validator->errors()->all());
                continue;
            }

            // Resolve multi-rombel (pisah ;)
            $rombelIds = [];
            $kodeRombelRaw = trim($data['kode_rombel'] ?? '');
            if ($kodeRombelRaw !== '') {
                foreach (preg_split('/[;,]+/', $kodeRombelRaw) as $kode) {
                    $kode = trim($kode);
                    if ($kode === '') continue;
                    $rombelId = $rombelMap->get($kode);
                    if (!$rombelId) {
                        $this->errors[] = "Baris {$rowNumber}: Kode rombel '{$kode}' tidak ditemukan.";
                        continue 2;
                    }
                    $rombelIds[] = $rombelId;
                }
                $rombelIds = array_unique($rombelIds);
            }

            $user = User::create([
                'name'          => $data['nama_lengkap'],
                'username'      => $data['username'] ?: null,
                'email'         => strtolower(trim($data['email'])),
                'password'      => Hash::make($data['password']),
                'level'         => $levelInt,
                'nomor_peserta' => $data['nomor_peserta'] ?: null,
                'rombel_id'     => $rombelIds[0] ?? null,
                'aktif'         => true,
            ]);

            if (!empty($rombelIds) && $levelInt === User::LEVEL_PESERTA) {
                $user->rombels()->sync($rombelIds);
            }

            $this->imported++;
        }
    }

    // ── Helper ────────────────────────────────────────────────────────

    private static function normalizeRow(array $row): array
    {
        return [
            'nama_lengkap'  => $row['nama_lengkap']  ?? null,
            'username'      => isset($row['username'])      ? (string) $row['username']      : null,
            'email'         => $row['email']          ?? null,
            'password'      => isset($row['password'])      ? (string) $row['password']      : null,
            'level'         => isset($row['level'])         ? (string) $row['level']         : null,
            'nomor_peserta' => isset($row['nomor_peserta']) ? (string) $row['nomor_peserta'] : null,
            'kode_rombel'   => isset($row['kode_rombel'])   ? (string) $row['kode_rombel']   : null,
        ];
    }
}
