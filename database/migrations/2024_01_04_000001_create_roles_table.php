<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        // 1. Créer la table roles
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->timestamps();
        });

        // 2. Insérer les rôles AVANT d'ajouter la FK
        DB::table('roles')->insert([
            ['name' => 'user',  'label' => 'Utilisateur',    'created_at' => now(), 'updated_at' => now()],
            ['name' => 'admin', 'label' => 'Administrateur', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 3. Ajouter la colonne role_id avec default(1) + FK
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->default(1)->after('bio')->constrained('roles');
        });

        // 4. Mettre tous les users existants en user (role_id = 1)
        DB::table('users')->whereNull('role_id')->update(['role_id' => 1]);
    }

    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
        Schema::dropIfExists('roles');
    }
};
