<?php

namespace Tests\Unit\Services;

use App\Models\Question;
use App\Models\QuestionMatch;
use App\Models\QuestionOption;
use App\Models\User;
use App\Services\ShuffleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ShuffleServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ShuffleService $shuffleService;
    protected User $guru;

    public function setUp(): void
    {
        parent::setUp();

        $this->shuffleService = new ShuffleService();
        $this->guru = User::factory()->create(['level' => 2]);
    }

    /**
     * Test shuffleQuestions respects lock_position
     */
    public function test_shuffled_questions_preserve_locked_positions(): void
    {
        // Create 5 questions: locked at positions 0, 2, 4
        $questions = collect([
            0 => Question::factory()->create(['lock_position' => true, 'created_by' => $this->guru->id]),
            1 => Question::factory()->create(['lock_position' => false, 'created_by' => $this->guru->id]),
            2 => Question::factory()->create(['lock_position' => true, 'created_by' => $this->guru->id]),
            3 => Question::factory()->create(['lock_position' => false, 'created_by' => $this->guru->id]),
            4 => Question::factory()->create(['lock_position' => true, 'created_by' => $this->guru->id]),
        ]);

        $locked0 = $questions[0]->id;
        $locked2 = $questions[2]->id;
        $locked4 = $questions[4]->id;

        $shuffled = $this->shuffleService->shuffleQuestions($questions);

        // Locked items should remain at same positions
        $this->assertEquals($locked0, $shuffled[0]->id);
        $this->assertEquals($locked2, $shuffled[2]->id);
        $this->assertEquals($locked4, $shuffled[4]->id);
    }

    /**
     * Test unlocked questions are shuffled
     */
    public function test_unlocked_questions_are_shuffled(): void
    {
        // Create 5 questions, none locked
        $questions = collect(
            Question::factory()->count(5)->create(['lock_position' => false, 'created_by' => $this->guru->id])
                ->mapWithKeys(fn ($q, $i) => [$i => $q])
        );

        $originalIds = $questions->pluck('id')->toArray();

        // Shuffle multiple times to see variations
        $shuffleResults = [];
        for ($i = 0; $i < 10; $i++) {
            $shuffled = $this->shuffleService->shuffleQuestions($questions);
            $shuffleResults[] = $shuffled->pluck('id')->toArray();
        }

        // At least one shuffle should differ from original (probability is very high)
        $differs = false;
        foreach ($shuffleResults as $result) {
            if ($result !== $originalIds) {
                $differs = true;
                break;
            }
        }

        $this->assertTrue($differs, 'Expected questions to be shuffled, but all remained in original order');
    }

    /**
     * Test all locked questions returns collection unchanged
     */
    public function test_all_locked_questions_returns_unchanged(): void
    {
        $questions = collect(
            Question::factory()->count(3)->create(['lock_position' => true, 'created_by' => $this->guru->id])
                ->mapWithKeys(fn ($q, $i) => [$i => $q])
        );

        $originalIds = $questions->pluck('id')->toArray();

        $shuffled = $this->shuffleService->shuffleQuestions($questions);

        $this->assertEquals($originalIds, $shuffled->pluck('id')->toArray());
    }

    /**
     * Test empty collection
     */
    public function test_empty_questions_collection_returns_empty(): void
    {
        $questions = collect([]);

        $shuffled = $this->shuffleService->shuffleQuestions($questions);

        $this->assertEmpty($shuffled);
    }

    /**
     * Test single question
     */
    public function test_single_question_returns_same(): void
    {
        $question = Question::factory()->create(['lock_position' => false, 'created_by' => $this->guru->id]);
        $questions = collect([0 => $question]);

        $shuffled = $this->shuffleService->shuffleQuestions($questions);

        $this->assertEquals(1, $shuffled->count());
        $this->assertEquals($question->id, $shuffled[0]->id);
    }

    /**
     * Test shuffleOptions returns random order
     */
    public function test_shuffle_options_returns_different_order(): void
    {
        $question = Question::factory()
            ->create(['tipe' => 'PG', 'created_by' => $this->guru->id]);
        QuestionOption::factory()->count(4)->create(['question_id' => $question->id]);

        $originalOrder = $question->options->pluck('id')->toArray();

        // Shuffle options multiple times
        $shuffleResults = [];
        for ($i = 0; $i < 20; $i++) {
            $shuffled = $this->shuffleService->shuffleOptions($question);
            $shuffleResults[] = $shuffled->pluck('id')->toArray();
        }

        // At least one shuffle different from original (very likely)
        $hasDifferent = false;
        foreach ($shuffleResults as $result) {
            if ($result !== $originalOrder) {
                $hasDifferent = true;
                break;
            }
        }

        $this->assertTrue($hasDifferent, 'Expected options to be shuffled');
    }

    /**
     * Test shuffleOptions preserves all options
     */
    public function test_shuffled_options_contain_all_original_options(): void
    {
        $question = Question::factory()
            ->create(['tipe' => 'PG', 'created_by' => $this->guru->id]);
        QuestionOption::factory()->count(4)->create(['question_id' => $question->id]);

        $originalIds = $question->options->pluck('id')->toArray();
        $shuffled = $this->shuffleService->shuffleOptions($question);
        $shuffledIds = $shuffled->pluck('id')->toArray();

        $this->assertEquals(sort($originalIds), sort($shuffledIds));
    }

    /**
     * Test shuffleOptions for question type without options
     */
    public function test_shuffle_options_for_uraian_question_returns_unchanged(): void
    {
        $question = Question::factory()
            ->create(['tipe' => 'URAIAN', 'created_by' => $this->guru->id]);

        $options = $this->shuffleService->shuffleOptions($question);

        $this->assertEmpty($options);
    }

    /**
     * Test shuffleOptions for JODOH question (no options)
     */
    public function test_shuffle_options_for_jodoh_question_returns_empty(): void
    {
        $question = Question::factory()
            ->has(QuestionMatch::factory()->count(3), 'matches')
            ->create(['tipe' => 'JODOH', 'created_by' => $this->guru->id]);

        $options = $this->shuffleService->shuffleOptions($question);

        $this->assertEmpty($options);
    }

    /**
     * Test shuffleQuestions with mixed locked/unlocked
     */
    public function test_shuffled_questions_mixed_locked_unlocked_state(): void
    {
        // Create pattern: U L U L U (U = unlocked, L = locked)
        $questions = collect([
            0 => Question::factory()->create(['lock_position' => false, 'created_by' => $this->guru->id]),
            1 => Question::factory()->create(['lock_position' => true, 'created_by' => $this->guru->id]),
            2 => Question::factory()->create(['lock_position' => false, 'created_by' => $this->guru->id]),
            3 => Question::factory()->create(['lock_position' => true, 'created_by' => $this->guru->id]),
            4 => Question::factory()->create(['lock_position' => false, 'created_by' => $this->guru->id]),
        ]);

        $locked1Id = $questions[1]->id;
        $locked3Id = $questions[3]->id;

        $shuffled = $this->shuffleService->shuffleQuestions($questions);

        // Verify locked positions
        $this->assertEquals($locked1Id, $shuffled[1]->id);
        $this->assertEquals($locked3Id, $shuffled[3]->id);

        // Verify they are still locked
        $this->assertTrue($shuffled[1]->lock_position);
        $this->assertTrue($shuffled[3]->lock_position);
    }
}
