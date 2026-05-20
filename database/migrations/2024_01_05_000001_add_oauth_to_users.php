<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->after('email');
            $table->string('apple_id')->nullable()->after('google_id');
            $table->string('oauth_provider')->nullable()->after('apple_id');
            $table->text('oauth_token')->nullable()->after('oauth_provider');
            $table->string('password')->nullable()->change(); // password optionnel pour OAuth
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id','apple_id','oauth_provider','oauth_token']);
            $table->string('password')->nullable(false)->change();
        });
    }
};
