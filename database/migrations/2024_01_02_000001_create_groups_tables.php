<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('question_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('description')->nullable();
            $table->enum('type', ['group','elimination'])->default('group');
            $table->enum('order', ['sequential','random'])->default('sequential');
            $table->boolean('is_published')->default(true);
            $table->boolean('is_anonymous')->default(false);
            $table->timestamps();
        });
        Schema::create('question_group_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('question_groups')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->integer('position')->default(0);
            $table->timestamps();
        });
        Schema::create('elimination_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('question_groups')->cascadeOnDelete();
            $table->string('label');
            $table->string('image')->nullable();
            $table->integer('position')->default(0);
            $table->timestamps();
        });
        Schema::create('elimination_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('question_groups')->cascadeOnDelete();
            $table->json('remaining_items');
            $table->json('eliminated_items')->nullable();
            $table->unsignedBigInteger('winner_item_id')->nullable();
            $table->foreign('winner_item_id')->references('id')->on('elimination_items')->nullOnDelete();
            $table->boolean('completed')->default(false);
            $table->timestamps();
            $table->unique(['user_id','group_id']);
        });
        Schema::create('group_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('question_groups')->cascadeOnDelete();
            $table->integer('current_position')->default(0);
            $table->boolean('completed')->default(false);
            $table->timestamps();
            $table->unique(['user_id','group_id']);
        });
        Schema::table('questions', function (Blueprint $table) {
            $table->enum('question_type',['simple','group','elimination'])->default('simple')->after('is_anonymous');
            $table->foreignId('group_id')->nullable()->after('question_type')->constrained('question_groups')->nullOnDelete();
        });
    }
    public function down(): void {
        Schema::table('questions', function(Blueprint $t) {
            $t->dropForeign(['group_id']);
            $t->dropColumn(['question_type','group_id']);
        });
        Schema::dropIfExists('group_progress');
        Schema::dropIfExists('elimination_sessions');
        Schema::dropIfExists('elimination_items');
        Schema::dropIfExists('question_group_items');
        Schema::dropIfExists('question_groups');
    }
};
