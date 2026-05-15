<?php

namespace Database\Factories;

use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionOptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'label'       => fake('fr_FR')->sentence(4),
            'image'       => null,
            'audio'       => null,
            'order'       => 0,
        ];
    }

    /**
     * Option avec image.
     * Usage : QuestionOptionFactory::new()->withImage('path/to/image.jpg')->create();
     */
    public function withImage(string $path): static
    {
        return $this->state(['image' => $path]);
    }

    /**
     * Option avec audio.
     * Usage : QuestionOptionFactory::new()->withAudio('path/to/audio.mp3')->create();
     */
    public function withAudio(string $path): static
    {
        return $this->state(['audio' => $path]);
    }
}
