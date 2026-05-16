<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('question_groups', function (Blueprint $table) {
            $table->enum('category', ['amour','aventure','nourriture','technologie','voyage','sport','musique','cinéma','divers'])->default('divers')->after('description');
        });
    }
    public function down(): void {
        Schema::table('question_groups', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
