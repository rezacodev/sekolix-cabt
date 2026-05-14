<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
  public function run(): void
  {
    User::updateOrCreate(
      ['email' => 'superadmin@cabt.mtsnbatang.sch.id'],
      [
        'name'     => 'Super Admin',
        'username' => 'superadmin',
        'password' => Hash::make('superbatang123!'),
        'level'    => User::LEVEL_SUPER_ADMIN,
        'aktif'    => true,
      ]
    );

    User::updateOrCreate(
      ['email' => 'adminsekolah@cabt.mtsnbatang.sch.id'],
      [
        'name'     => 'Admin Sekolah',
        'username' => 'adminsekolah',
        'password' => Hash::make('adminbatang123#'),
        'level'    => User::LEVEL_ADMIN,
        'aktif'    => true,
      ]
    );
  }
}
