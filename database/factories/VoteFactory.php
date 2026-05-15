<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class VoteFactory extends Factory
{
    public function definition(): array
    {
        $question = Question::factory()->create();
        $option   = QuestionOption::factory()->create(['question_id' => $question->id]);

        return [
            'user_id'            => User::factory(),
            'question_id'        => $question->id,
            'question_option_id' => $option->id,
        ];
    }

    /**
     * Vote sur une question et option spécifiques.
     * Usage : VoteFactory::new()->forQuestion($question, $option)->create();
     */
    public function forQuestion(Question $question, QuestionOption $option): static
    {
        return $this->state([
            'question_id'        => $question->id,
            'question_option_id' => $option->id,
        ]);
    }
}
