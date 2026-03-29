<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionOption>
 */
class QuestionOptionFactory extends Factory
{
    protected $model = QuestionOption::class;

    public function definition(): array
    {
        return [
            'question_id'  => Question::factory(),
            'kode_opsi'    => $this->faker->randomElement(['A', 'B', 'C', 'D', 'E']),
            'teks_opsi'    => '<p>' . $this->faker->sentence(4) . '</p>',
            'is_correct'   => false,
            'bobot_persen' => 100,
            'urutan'       => $this->faker->numberBetween(0, 4),
            'aktif'        => true,
        ];
    }

    /**
     * Mark this option as correct
     */
    public function correct(): static
    {
        return $this->state(['is_correct' => true]);
    }

    /**
     * Mark this option as incorrect
     */
    public function incorrect(): static
    {
        return $this->state(['is_correct' => false]);
    }

    /**
     * Set bobot percentage
     */
    public function withBobot(int $persen): static
    {
        return $this->state(['bobot_persen' => $persen]);
    }
}
