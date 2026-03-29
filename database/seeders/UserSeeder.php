<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ─── 1. Super Admin (Level 4) ─────────────────────────────────────
        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@cabt.local'],
            [
                'name'     => 'Super Admin',
                'username' => 'superadmin',
                'password' => Hash::make('admin123'),
                'level'    => User::LEVEL_SUPER_ADMIN,
                'aktif'    => true,
            ]
        );

        // ─── 2. Admin (Level 3) ───────────────────────────────────────────
        $admin = User::updateOrCreate(
            ['email' => 'adminsekolah@cabt.local'],
            [
                'name'     => 'Admin Sekolah',
                'username' => 'adminsekolah',
                'password' => Hash::make('admin123'),
                'level'    => User::LEVEL_ADMIN,
                'aktif'    => true,
            ]
        );

        // ─── 3. Guru (Level 2) ────────────────────────────────────────────
        $guru1 = User::updateOrCreate(
            ['email' => 'guru1@cabt.local'],
            [
                'name'     => 'Budi Waluyo',
                'username' => 'guru.budi',
                'password' => Hash::make('guru123'),
                'level'    => User::LEVEL_GURU,
                'aktif'    => true,
            ]
        );

        $guru2 = User::updateOrCreate(
            ['email' => 'guru2@cabt.local'],
            [
                'name'     => 'Siti Rahayu',
                'username' => 'guru.siti',
                'password' => Hash::make('guru123'),
                'level'    => User::LEVEL_GURU,
                'aktif'    => true,
            ]
        );

        // ─── 4. Peserta (Level 1) — dengan nomor peserta ──────────────────
        $pesertaData = [
            ['name' => 'Andi Prasetyo',   'username' => 'andi.p',    'email' => 'andi@cabt.local',   'nomor_peserta' => 'PST-001'],
            ['name' => 'Dewi Kusuma',     'username' => 'dewi.k',    'email' => 'dewi@cabt.local',   'nomor_peserta' => 'PST-002'],
            ['name' => 'Fahmi Ramadhan',  'username' => 'fahmi.r',   'email' => 'fahmi@cabt.local',  'nomor_peserta' => 'PST-003'],
            ['name' => 'Galih Santosa',   'username' => 'galih.s',   'email' => 'galih@cabt.local',  'nomor_peserta' => 'PST-004'],
            ['name' => 'Hana Pertiwi',    'username' => 'hana.p',    'email' => 'hana@cabt.local',   'nomor_peserta' => 'PST-005'],
        ];

        foreach ($pesertaData as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'           => $data['name'],
                    'username'       => $data['username'],
                    'password'       => Hash::make('peserta123'),
                    'level'          => User::LEVEL_PESERTA,
                    'nomor_peserta'  => $data['nomor_peserta'],
                    'aktif'          => true,
                ]
            );
        }

        // ─── 5. Generate 10 peserta tambahan via Factory ──────────────────
        // Hanya buat jika belum ada (idempotent)
        $existingNomors = User::whereNotNull('nomor_peserta')
            ->pluck('nomor_peserta')
            ->toArray();

        for ($i = 6; $i <= 15; $i++) {
            $nomor = 'PST-' . str_pad($i, 3, '0', STR_PAD_LEFT);
            if (!in_array($nomor, $existingNomors)) {
                User::factory()->peserta()->create([
                    'nomor_peserta' => $nomor,
                ]);
            }
        }

        $counts = [
            'Super Admin' => User::where('level', User::LEVEL_SUPER_ADMIN)->count(),
            'Admin'       => User::where('level', User::LEVEL_ADMIN)->count(),
            'Guru'        => User::where('level', User::LEVEL_GURU)->count(),
            'Peserta'     => User::where('level', User::LEVEL_PESERTA)->count(),
        ];

        $this->command->info('UserSeeder selesai:');
        foreach ($counts as $label => $count) {
            $this->command->line("  {$label}: {$count} user");
        }
    }
}
