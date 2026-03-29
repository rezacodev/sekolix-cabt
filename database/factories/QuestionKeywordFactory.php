<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionKeyword;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionKeyword>
 */
class QuestionKeywordFactory extends Factory
{
    protected $model = QuestionKeyword::class;

    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'keyword'     => $this->faker->word(),
        ];
    }

    /**
     * Set specific keyword
     */
    public function withKeyword(string $keyword): static
    {
        return $this->state(['keyword' => $keyword]);
    }
}
