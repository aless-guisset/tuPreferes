<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShareFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'question_id' => Question::factory(),
            'platform'    => fake()->randomElement(['link', 'twitter', 'facebook', 'whatsapp']),
        ];
    }

    /**
     * Usage : ShareFactory::new()->forQuestion($question)->create();
     */
    public function forQuestion(Question $question): static
    {
        return $this->state(['question_id' => $question->id]);
    }
}
