<?php

namespace Database\Seeders;

use App\Models\Rombel;
use App\Models\User;
use Illuminate\Database\Seeder;

class RombelSeeder extends Seeder
{
    public function run(): void
    {
        // Buat 3 rombel dummy
        $rombelData = [
            [
                'nama'        => 'Kelas X IPA 1',
                'kode'        => 'X-IPA-1',
                'angkatan'    => 2024,
                'tahun_ajaran' => '2024/2025',
                'keterangan'  => 'Rombel IPA kelas 10 grup 1',
                'aktif'       => true,
            ],
            [
                'nama'        => 'Kelas X IPA 2',
                'kode'        => 'X-IPA-2',
                'angkatan'    => 2024,
                'tahun_ajaran' => '2024/2025',
                'keterangan'  => 'Rombel IPA kelas 10 grup 2',
                'aktif'       => true,
            ],
            [
                'nama'        => 'Kelas X IPS 1',
                'kode'        => 'X-IPS-1',
                'angkatan'    => 2024,
                'tahun_ajaran' => '2024/2025',
                'keterangan'  => 'Rombel IPS kelas 10 grup 1',
                'aktif'       => true,
            ],
        ];

        foreach ($rombelData as $data) {
            Rombel::updateOrCreate(['kode' => $data['kode']], $data);
        }

        $ripa1 = Rombel::where('kode', 'X-IPA-1')->first();
        $ripa2 = Rombel::where('kode', 'X-IPA-2')->first();
        $rips1 = Rombel::where('kode', 'X-IPS-1')->first();

        // Assign guru ke rombel (via pivot rombel_guru)
        $guru1 = User::where('email', 'guru1@cabt.local')->first();
        $guru2 = User::where('email', 'guru2@cabt.local')->first();

        if ($guru1) {
            $guru1->rombelsAmpu()->syncWithoutDetaching([$ripa1->id, $ripa2->id]);
        }
        if ($guru2) {
            $guru2->rombelsAmpu()->syncWithoutDetaching([$rips1->id]);
        }

        // Assign peserta ke rombel via dua jalur:
        //  1. rombel_id (FK langsung) — untuk backward-compat & display di profil
        //  2. rombel_peserta (pivot)  — digunakan oleh DashboardGuru & ParticipantsRelationManager

        // PST-001 s/d PST-005 → X-IPA-1 (diampu guru1)
        $ipa1Peserta = User::where('level', User::LEVEL_PESERTA)
            ->whereIn('nomor_peserta', ['PST-001', 'PST-002', 'PST-003', 'PST-004', 'PST-005'])
            ->get();
        $ipa1Peserta->each->update(['rombel_id' => $ripa1->id]);
        $ripa1->peserta()->syncWithoutDetaching($ipa1Peserta->pluck('id')->all());

        // PST-006 s/d PST-010 → X-IPA-2 (diampu guru1)
        $ipa2Peserta = User::where('level', User::LEVEL_PESERTA)
            ->whereIn('nomor_peserta', ['PST-006', 'PST-007', 'PST-008', 'PST-009', 'PST-010'])
            ->get();
        $ipa2Peserta->each->update(['rombel_id' => $ripa2->id]);
        $ripa2->peserta()->syncWithoutDetaching($ipa2Peserta->pluck('id')->all());

        // PST-011 s/d PST-015 → X-IPS-1 (diampu guru2)
        $ips1Peserta = User::where('level', User::LEVEL_PESERTA)
            ->whereIn('nomor_peserta', ['PST-011', 'PST-012', 'PST-013', 'PST-014', 'PST-015'])
            ->get();
        $ips1Peserta->each->update(['rombel_id' => $rips1->id]);
        $rips1->peserta()->syncWithoutDetaching($ips1Peserta->pluck('id')->all());
    }
}
