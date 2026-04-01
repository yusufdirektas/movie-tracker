<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @KAVRAM: Schema::table() - Mevcut Tablo Değişikliği
     *
     * commentable_type ve commentable_id alanlarını nullable yapalım.
     * Artık polymorphic ilişki (Movie, Collection) yerine
     * tmdb_id kullanıyoruz (Global Comments Pattern).
     *
     * Eski sistem: commentable_id → Kullanıcının movie kaydı
     * Yeni sistem: tmdb_id → TMDB film ID (global)
     *
     * Polymorphic alanları koruyoruz çünkü gelecekte
     * Collection yorumları için kullanılabilir.
     */
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            // Polymorphic alanları nullable yap
            $table->string('commentable_type')->nullable()->change();
            $table->unsignedBigInteger('commentable_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            // Geri al (NOT NULL yap)
            $table->string('commentable_type')->nullable(false)->change();
            $table->unsignedBigInteger('commentable_id')->nullable(false)->change();
        });
    }
};
