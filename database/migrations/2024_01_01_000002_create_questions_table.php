<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title')->nullable(); // Titre optionnel
            $table->enum('category', [
                'amour', 'aventure', 'nourriture', 'technologie',
                'voyage', 'sport', 'musique', 'cinéma', 'divers'
            ])->default('divers');
            $table->boolean('is_published')->default(true);
            $table->boolean('is_anonymous')->default(false);
            $table->timestamps();
        });

        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('label'); // Texte de l'option (ex: "Manger des escargots")
            $table->string('image')->nullable(); // Chemin vers l'image
            $table->string('audio')->nullable(); // Chemin vers l'audio
            $table->integer('order')->default(0); // Ordre d'affichage (option A ou B)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('questions');
    }
};
