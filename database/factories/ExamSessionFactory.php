<?php

namespace Database\Factories;

use App\Models\ExamPackage;
use App\Models\ExamSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamSessionFactory extends Factory
{
    protected $model = ExamSession::class;

    public function definition(): array
    {
        $waktuMulai = $this->faker->dateTimeBetween('-30 days', 'now');
        $waktuSelesai = (clone $waktuMulai)->modify('+2 days');

        return [
            'exam_package_id' => ExamPackage::factory(),
            'nama_sesi' => 'Sesi ' . $this->faker->word(),
            'waktu_mulai' => $waktuMulai,
            'waktu_selesai' => $waktuSelesai,
            'status' => ExamSession::STATUS_AKTIF,
            'token_akses' => strtoupper($this->faker->bothify('???-###')),
            'created_by' => User::factory(),
        ];
    }

    public function aktif(): self
    {
        return $this->state([
            'status' => ExamSession::STATUS_AKTIF,
        ]);
    }

    public function draft(): self
    {
        return $this->state([
            'status' => ExamSession::STATUS_DRAFT,
        ]);
    }

    public function selesai(): self
    {
        return $this->state([
            'status' => ExamSession::STATUS_SELESAI,
        ]);
    }
}
