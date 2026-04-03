<?php

namespace App\Services;

use App\Models\Question;
use Illuminate\Support\Collection;

class ShuffleService
{
    /**
     * Shuffle questions while keeping lock_position=true items in place,
     * and keeping grouped questions (same question_group_id) together as a unit.
     *
     * Algorithm:
     *  1. Identify "units": a unit is either a single standalone question or
     *     the entire ordered block of questions sharing the same question_group_id.
     *  2. Among unlocked units, shuffle the units.
     *  3. Locked units (all questions in the unit have lock_position=true) stay
     *     anchored at their original position in the unit sequence.
     *
     * @param  Collection<int, Question>  $questions  Ordered collection of Question models.
     * @return Collection<int, Question>
     */
    public function shuffleQuestions(Collection $questions): Collection
    {
        if ($questions->isEmpty()) {
            return $questions;
        }

        // Build list of "units" preserving order.
        // Each unit = array of Question objects.
        $units = [];
        $groupMap = []; // group_id => unit index

        foreach ($questions->values() as $question) {
            $gid = $question->question_group_id ?? null;

            if ($gid !== null) {
                if (isset($groupMap[$gid])) {
                    // Already started this group unit — append
                    $units[$groupMap[$gid]][] = $question;
                } else {
                    $groupMap[$gid] = count($units);
                    $units[] = [$question];
                }
            } else {
                // Standalone question: own unit
                $units[] = [$question];
            }
        }

        // A unit is "locked" if ALL its questions have lock_position=true.
        $lockedMask = array_map(
            fn($unit) => collect($unit)->every(fn($q) => $q->lock_position),
            $units
        );

        // Separate unlocked unit indices from locked.
        $unlockedIdxs = array_values(array_keys(array_filter($lockedMask, fn($l) => ! $l)));

        if (! empty($unlockedIdxs)) {
            $unlockedUnits = array_map(fn($i) => $units[$i], $unlockedIdxs);
            shuffle($unlockedUnits);
            foreach ($unlockedIdxs as $pos => $idx) {
                $units[$idx] = $unlockedUnits[$pos];
            }
        }

        // Flatten units back into a single collection.
        $result = collect();
        foreach ($units as $unit) {
            foreach ($unit as $q) {
                $result->push($q);
            }
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

        // BS options (B/S) must always stay in fixed order — never shuffle.
        if ($question->tipe === Question::TIPE_BS) {
            return $options;
        }

        return $options->shuffle();
    }
}
