<?php

namespace Database\Factories;

use App\Models\ExamPackage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamPackageFactory extends Factory
{
    protected $model = ExamPackage::class;

    public function definition(): array
    {
        return [
            'nama' => $this->faker->sentence(3),
            'deskripsi' => $this->faker->paragraph(),
            'durasi_menit' => $this->faker->numberBetween(30, 180),
            'waktu_minimal_menit' => $this->faker->numberBetween(5, 30),
            'acak_soal' => $this->faker->boolean(70),
            'acak_opsi' => $this->faker->boolean(70),
            'max_pengulangan' => $this->faker->numberBetween(1, 5),
            'tampilkan_hasil' => true,
            'tampilkan_review' => true,
            'grading_mode' => ExamPackage::GRADING_REALTIME,
            'created_by' => User::factory(),
        ];
    }
}
