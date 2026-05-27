<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MakeUser5AdminSeeder extends Seeder
{
    /**
     * Passe l'utilisateur ID 6 en admin (role_id = 2).
     */
    public function run(): void
    {
        DB::table('users')
            ->where('id', 6)
            ->update(['role_id' => 2]);

        $this->command->info('User 6 est maintenant admin (role_id = 2).');
    }
}
