<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    private static array $questionsData = [
        ['Voyager dans le temps vers le passé', 'Voyager dans le futur'],
        ['Avoir le pouvoir de voler', 'Être invisible à volonté'],
        ['Vivre sans internet', 'Vivre sans télévision'],
        ['Manger des insectes pour survivre', 'Ne jamais manger de sucre'],
        ['Parler toutes les langues du monde', 'Jouer de tous les instruments de musique'],
        ['Être toujours en retard', 'Toujours partir trop tôt'],
        ['Ne jamais dormir', 'Dormir 16 heures par jour'],
        ['Avoir une mémoire parfaite', 'Être capable d\'apprendre n\'importe quoi en 1 heure'],
        ['Vivre à la montagne', 'Vivre au bord de la mer'],
        ['Être célèbre mais pauvre', 'Être riche mais inconnu'],
    ];

    private static int $index = 0;

    public function definition(): array
    {
        $categories = ['amour', 'aventure', 'nourriture', 'technologie', 'voyage', 'sport', 'musique', 'cinéma', 'divers'];

        return [
            'user_id'      => User::factory(),
            'title'        => fake('fr_FR')->sentence(6),
            'category'     => fake()->randomElement($categories),
            'is_published' => true,
            'is_anonymous' => fake()->boolean(20),
        ];
    }

    /**
     * Crée une question avec ses 2 options attachées.
     * Usage dans un seeder :
     *   QuestionFactory::new()->withOptions()->create();
     */
    public function withOptions(string $labelA = null, string $labelB = null): static
    {
        return $this->afterCreating(function ($question) use ($labelA, $labelB) {
            $data = self::$questionsData[self::$index % count(self::$questionsData)];
            self::$index++;

            \App\Models\QuestionOption::factory()->create([
                'question_id' => $question->id,
                'label'       => $labelA ?? $data[0],
                'order'       => 0,
            ]);

            \App\Models\QuestionOption::factory()->create([
                'question_id' => $question->id,
                'label'       => $labelB ?? $data[1],
                'order'       => 1,
            ]);
        });
    }
}
