<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LikeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'question_id' => Question::factory(),
        ];
    }

    /**
     * Usage : LikeFactory::new()->forQuestion($question)->forUser($user)->create();
     */
    public function forQuestion(Question $question): static
    {
        return $this->state(['question_id' => $question->id]);
    }

    public function forUser(User $user): static
    {
        return $this->state(['user_id' => $user->id]);
    }
}
