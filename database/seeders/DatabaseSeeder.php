<?php

namespace Database\Seeders;

use App\Models\Like;
use App\Models\Question;
use App\Models\QuestionHistory;
use App\Models\QuestionOption;
use App\Models\Share;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─────────────────────────────────────────────────────────────────────
        // UTILISATION DES FACTORIES (exemples commentés)
        // ─────────────────────────────────────────────────────────────────────
        //
        // Créer 10 utilisateurs aléatoires :
        // User::factory(10)->create();
        //
        // Créer 20 questions avec leurs options (via withOptions) :
        // Question::factory(20)->withOptions()->create();
        //
        // Créer des votes pour une question existante :
        // $question = Question::find(1);
        // $users = User::factory(50)->create();
        // foreach ($users as $user) {
        //     $option = $question->options->random();
        //     Vote::factory()->forQuestion($question, $option)->create(['user_id' => $user->id]);
        // }
        //
        // Créer des likes :
        // Like::factory(30)->create();
        //
        // Créer des partages :
        // Share::factory(15)->forQuestion($question)->create();
        //
        // Créer l'historique d'un utilisateur :
        // $user = User::find(1);
        // QuestionHistory::factory(10)->forUser($user)->create();
        //
        // ─────────────────────────────────────────────────────────────────────

        // ── Utilisateurs de démonstration ────────────────────────────────────

        $admin = User::create([
            'name'              => 'Alice Dupont',
            'username'          => 'alice',
            'email'             => 'alice@example.com',
            'email_verified_at' => now(),
            'password'          => Hash::make('password'),
            'bio'               => 'Passionnée de jeux et de bonnes questions !',
        ]);

        $users = collect([
            User::create([
                'name'              => 'Baptiste Martin',
                'username'          => 'baptiste',
                'email'             => 'baptiste@example.com',
                'email_verified_at' => now(),
                'password'          => Hash::make('password'),
                'bio'               => 'Amateur de dilemmes impossibles.',
            ]),
            User::create([
                'name'              => 'Chloé Bernard',
                'username'          => 'chloe',
                'email'             => 'chloe@example.com',
                'email_verified_at' => now(),
                'password'          => Hash::make('password'),
            ]),
            User::create([
                'name'              => 'David Leroy',
                'username'          => 'david',
                'email'             => 'david@example.com',
                'email_verified_at' => now(),
                'password'          => Hash::make('password'),
            ]),
        ]);

        $allUsers = $users->prepend($admin);

        // ── Questions (5 en français) ─────────────────────────────────────────

        $questionsData = [
            [
                'title'    => null,
                'category' => 'aventure',
                'user_id'  => $admin->id,
                'options'  => [
                    ['label' => 'Voyager dans le temps vers le passé', 'order' => 0],
                    ['label' => 'Voyager dans le futur', 'order' => 1],
                ],
            ],
            [
                'title'    => null,
                'category' => 'divers',
                'user_id'  => $users[0]->id,
                'options'  => [
                    ['label' => 'Avoir le pouvoir de voler', 'order' => 0],
                    ['label' => 'Être invisible à volonté', 'order' => 1],
                ],
            ],
            [
                'title'    => null,
                'category' => 'nourriture',
                'user_id'  => $users[1]->id,
                'options'  => [
                    ['label' => 'Ne jamais manger de sucre toute ta vie', 'order' => 0],
                    ['label' => 'Ne jamais manger de sel toute ta vie', 'order' => 1],
                ],
            ],
            [
                'title'    => null,
                'category' => 'technologie',
                'user_id'  => $users[2]->id,
                'options'  => [
                    ['label' => 'Vivre sans smartphone pendant un an', 'order' => 0],
                    ['label' => 'Vivre sans internet pendant un an', 'order' => 1],
                ],
            ],
            [
                'title'    => null,
                'category' => 'voyage',
                'user_id'  => $admin->id,
                'options'  => [
                    ['label' => 'Vivre à la montagne toute ta vie', 'order' => 0],
                    ['label' => 'Vivre au bord de la mer toute ta vie', 'order' => 1],
                ],
            ],
        ];

        foreach ($questionsData as $qData) {
            $question = Question::create([
                'user_id'      => $qData['user_id'],
                'title'        => $qData['title'],
                'category'     => $qData['category'],
                'is_published' => true,
                'is_anonymous' => false,
            ]);

            $createdOptions = [];
            foreach ($qData['options'] as $optData) {
                $createdOptions[] = QuestionOption::create([
                    'question_id' => $question->id,
                    'label'       => $optData['label'],
                    'order'       => $optData['order'],
                ]);
            }

            // Simule des votes pour avoir des statistiques visibles
            foreach ($allUsers as $user) {
                $option = $createdOptions[array_rand($createdOptions)];
                Vote::create([
                    'user_id'            => $user->id,
                    'question_id'        => $question->id,
                    'question_option_id' => $option->id,
                ]);
            }

            // Simule des likes
            $likingUsers = $allUsers->random(rand(1, $allUsers->count()));
            foreach ($likingUsers as $user) {
                Like::create([
                    'user_id'     => $user->id,
                    'question_id' => $question->id,
                ]);
            }
        }
    }
}
