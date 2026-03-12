<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_public')->default(false)->after('email');
            $table->uuid('share_token')->nullable()->unique()->after('is_public');
        });

        Schema::table('collections', function (Blueprint $table) {
            $table->boolean('is_public')->default(false)->after('user_id');
            $table->uuid('share_token')->nullable()->unique()->after('is_public');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_public', 'share_token']);
        });

        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn(['is_public', 'share_token']);
        });
    }
};
