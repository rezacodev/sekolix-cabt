<?php

namespace Database\Factories;

use App\Models\AttemptQuestion;
use App\Models\ExamAttempt;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttemptQuestionFactory extends Factory
{
    protected $model = AttemptQuestion::class;

    public function definition(): array
    {
        return [
            'attempt_id' => ExamAttempt::factory(),
            'question_id' => Question::factory(),
            'urutan' => $this->faker->numberBetween(1, 30),
            'jawaban_peserta' => $this->faker->optional(0.3)->word(),
            'jawaban_file' => null,
            'nilai_perolehan' => $this->faker->optional(0.2)->randomFloat(2, 0, 100),
            'is_correct' => $this->faker->boolean(50),
            'is_ragu' => $this->faker->boolean(20),
            'waktu_jawab' => $this->faker->optional(0.7)->dateTime(),
        ];
    }

    public function answered(): self
    {
        return $this->state([
            'jawaban_peserta' => $this->faker->word(),
            'is_correct' => $this->faker->boolean(70),
            'nilai_perolehan' => $this->faker->numberBetween(0, 100),
        ]);
    }

    public function unanswered(): self
    {
        return $this->state([
            'jawaban_peserta' => null,
            'is_correct' => false,
            'nilai_perolehan' => 0,
        ]);
    }
}
