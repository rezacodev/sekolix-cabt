<?php

namespace Database\Factories;

use App\Models\Rombel;
use Illuminate\Database\Eloquent\Factories\Factory;

class RombelFactory extends Factory
{
    protected $model = Rombel::class;

    public function definition(): array
    {
        return [
            'nama' => $this->faker->word() . ' ' . $this->faker->randomElement(['IPA', 'IPS']),
            'kode' => strtoupper($this->faker->bothify('??-???-#')),
            'angkatan' => $this->faker->year(),
            'tahun_ajaran' => $this->faker->year() . '/' . ($this->faker->year() + 1),
            'keterangan' => $this->faker->sentence(),
            'aktif' => true,
        ];
    }
}
