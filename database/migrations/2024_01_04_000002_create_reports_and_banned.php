<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Champ banned sur users
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('banned')->default(false)->after('role_id');
            $table->timestamp('banned_at')->nullable()->after('banned');
            $table->string('ban_reason')->nullable()->after('banned_at');
        });

        // Table signalements
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // qui signale
            $table->string('reportable_type'); // Question ou QuestionGroup
            $table->unsignedBigInteger('reportable_id');
            $table->enum('reason', [
                'inapproprie',
                'spam',
                'harcèlement',
                'fausse_information',
                'autre'
            ])->default('inapproprie');
            $table->text('comment')->nullable();
            $table->enum('status', ['pending','resolved','rejected'])->default('pending');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['reportable_type','reportable_id']);
        });

        // Champ hidden sur questions et groups
        Schema::table('questions', function (Blueprint $table) {
            $table->boolean('is_hidden')->default(false)->after('is_published');
        });

        Schema::table('question_groups', function (Blueprint $table) {
            $table->boolean('is_hidden')->default(false)->after('is_published');
        });
    }

    public function down(): void {
        Schema::table('question_groups', function (Blueprint $table) {
            $table->dropColumn('is_hidden');
        });
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('is_hidden');
        });
        Schema::dropIfExists('reports');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['banned','banned_at','ban_reason']);
        });
    }
};
