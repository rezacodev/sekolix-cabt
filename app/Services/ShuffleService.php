<?php

namespace App\Services;

use App\Models\Question;
use Illuminate\Support\Collection;

class ShuffleService
{
    /**
     * Shuffle questions while keeping lock_position=true items in place.
     *
     * Algorithm:
     *  1. Collect indices of unlocked questions.
     *  2. Collect those questions, shuffle them.
     *  3. Put them back at the unlocked indices.
     *
     * @param  Collection<int, Question>  $questions  Ordered collection of Question models.
     * @return Collection<int, Question>
     */
    public function shuffleQuestions(Collection $questions): Collection
    {
        $result    = $questions->values();
        $unlocked  = $result->keys()->filter(fn ($i) => ! $result[$i]->lock_position)->values();

        if ($unlocked->isEmpty()) {
            return $result;
        }

        $shuffled = $unlocked->map(fn ($i) => $result[$i])->shuffle()->values();

        foreach ($unlocked as $pos => $idx) {
            $result[$idx] = $shuffled[$pos];
        }

        return $result;
    }

    /**
     * Return a question's options in random order.
     * Returns the options collection unchanged if question type has no options.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function shuffleOptions(Question $question): \Illuminate\Database\Eloquent\Collection
    {
        $options = $question->options()->get();

        if ($options->isEmpty() || ! Question::tipeHasOptions($question->tipe)) {
            return $options;
        }

        return $options->shuffle();
    }
}
