<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 📚 COMMENT REACTIONS (Like/Dislike) TABLOSU
 *
 * @KAVRAM: Unique Constraint
 *
 * unique(['comment_id', 'user_id']) ne yapar?
 * - Bir kullanıcı aynı yoruma sadece 1 kez tepki verebilir
 * - İkinci kez denerse veritabanı hata verir
 * - updateOrCreate() ile "toggle" mantığı uygulanır
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comment_reactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('comment_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // true = like, false = dislike
            $table->boolean('is_like');

            $table->timestamps();

            // Bir kullanıcı bir yoruma sadece 1 tepki verebilir
            $table->unique(['comment_id', 'user_id']);
        });

        // Comments tablosuna tmdb_id ekle (global yorumlar için)
        Schema::table('comments', function (Blueprint $table) {
            $table->unsignedBigInteger('tmdb_id')->nullable()->after('has_spoiler');
            $table->index('tmdb_id');
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex(['tmdb_id']);
            $table->dropColumn('tmdb_id');
        });

        Schema::dropIfExists('comment_reactions');
    }
};
