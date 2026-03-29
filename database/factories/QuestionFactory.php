<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Question;
use App\Models\QuestionKeyword;
use App\Models\QuestionMatch;
use App\Models\QuestionOption;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'kategori_id'       => null,
            'tipe'              => Question::TIPE_PG,
            'teks_soal'         => '<p>' . $this->faker->paragraph(2) . '</p>',
            'penjelasan'        => null,
            'tingkat_kesulitan' => $this->faker->randomElement(['mudah', 'sedang', 'sulit']),
            'bobot'             => $this->faker->randomElement([1, 2, 3, 5]),
            'lock_position'     => false,
            'aktif'             => true,
            'created_by'        => null,
        ];
    }

    /**
     * Buat soal PG (Pilihan Ganda) dengan 4 opsi.
     */
    public function pg(): static
    {
        return $this->state(['tipe' => Question::TIPE_PG])
            ->afterCreating(function (Question $question) {
                $correctIndex = $this->faker->numberBetween(0, 3);
                foreach (['A', 'B', 'C', 'D'] as $i => $kode) {
                    QuestionOption::create([
                        'question_id'  => $question->id,
                        'kode_opsi'    => $kode,
                        'teks_opsi'    => '<p>' . $this->faker->sentence(4) . '</p>',
                        'is_correct'   => $i === $correctIndex,
                        'bobot_persen' => 100,
                        'urutan'       => $i,
                        'aktif'        => true,
                    ]);
                }
            });
    }

    /**
     * Buat soal ISIAN dengan 2 kata kunci.
     */
    public function isian(): static
    {
        return $this->state(['tipe' => Question::TIPE_ISIAN])
            ->afterCreating(function (Question $question) {
                foreach ([0, 1] as $i) {
                    QuestionKeyword::create([
                        'question_id' => $question->id,
                        'keyword'     => $this->faker->word(),
                    ]);
                }
            });
    }

    /**
     * Buat soal JODOH dengan 3 pasangan.
     */
    public function jodoh(): static
    {
        return $this->state(['tipe' => Question::TIPE_JODOH])
            ->afterCreating(function (Question $question) {
                for ($i = 0; $i < 3; $i++) {
                    QuestionMatch::create([
                        'question_id' => $question->id,
                        'premis'      => $this->faker->sentence(3),
                        'respon'      => $this->faker->sentence(2),
                        'urutan'      => $i,
                    ]);
                }
            });
    }

    /**
     * Buat soal URAIAN (tanpa opsi).
     */
    public function uraian(): static
    {
        return $this->state(['tipe' => Question::TIPE_URAIAN]);
    }
}
