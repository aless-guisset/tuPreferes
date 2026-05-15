<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionHistoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'question_id' => Question::factory(),
            'viewed_at'   => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Usage : QuestionHistoryFactory::new()->forUser($user)->create();
     */
    public function forUser(User $user): static
    {
        return $this->state(['user_id' => $user->id]);
    }
}
