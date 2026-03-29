<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropUnique('uq_movies_user_tmdb');
            $table->unique(['user_id', 'tmdb_id', 'media_type'], 'uq_movies_user_tmdb_media');
        });
    }

    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropUnique('uq_movies_user_tmdb_media');
            $table->unique(['user_id', 'tmdb_id'], 'uq_movies_user_tmdb');
        });
    }
};

