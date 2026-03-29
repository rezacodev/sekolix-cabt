<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionMatch>
 */
class QuestionMatchFactory extends Factory
{
    protected $model = QuestionMatch::class;

    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'premis'      => $this->faker->sentence(3),
            'respon'      => $this->faker->sentence(2),
            'urutan'      => $this->faker->numberBetween(0, 9),
        ];
    }

    /**
     * Set specific premis and respon
     */
    public function withPremisRespon(string $premis, string $respon): static
    {
        return $this->state([
            'premis' => $premis,
            'respon' => $respon,
        ]);
    }
}
