<?php

namespace Database\Factories;

use App\Models\ExamAttempt;
use App\Models\ExamSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamAttemptFactory extends Factory
{
    protected $model = ExamAttempt::class;

    public function definition(): array
    {
        $waktuMulai = $this->faker->dateTimeBetween('-7 days', 'now');
        $waktuSelesai = $this->faker->boolean(70) ? (clone $waktuMulai)->modify('+1 hour') : null;

        return [
            'exam_session_id' => ExamSession::factory(),
            'user_id' => User::factory(),
            'waktu_mulai' => $waktuMulai,
            'waktu_selesai' => $waktuSelesai,
            'status' => $waktuSelesai ? ExamAttempt::STATUS_SELESAI : ExamAttempt::STATUS_BERLANGSUNG,
            'nilai_akhir' => $this->faker->randomFloat(2, 0, 100),
            'jumlah_benar' => $this->faker->numberBetween(0, 20),
            'jumlah_salah' => $this->faker->numberBetween(0, 10),
            'jumlah_kosong' => $this->faker->numberBetween(0, 5),
            'attempt_ke' => $this->faker->numberBetween(1, 3),
        ];
    }

    public function berlangsung(): self
    {
        return $this->state([
            'waktu_selesai' => null,
            'status' => ExamAttempt::STATUS_BERLANGSUNG,
            'nilai_akhir' => null,
        ]);
    }

    public function selesai(): self
    {
        return $this->state([
            'waktu_selesai' => $this->faker->dateTime(),
            'status' => ExamAttempt::STATUS_SELESAI,
        ]);
    }
}
