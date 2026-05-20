<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Historique complet des votes (permet le revote)
        Schema::create('vote_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_option_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_current')->default(true); // le vote actuel
            $table->timestamps();
        });

        // Supprimer la contrainte unique sur votes pour permettre le revote
        // On garde votes pour le vote actuel, vote_history pour l'historique
    }

    public function down(): void {
        Schema::dropIfExists('vote_history');
    }
};
