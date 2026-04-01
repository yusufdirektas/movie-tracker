<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 📚 INDEX NEDİR?
 *
 * Veritabanı indexi, sorguları hızlandıran bir veri yapısıdır.
 * Kitabın arkasındaki "dizin" gibi düşün - aradığın kelimeyi
 * her sayfayı okumadan bulabilirsin.
 *
 * NE ZAMAN INDEX EKLE?
 * - WHERE koşulunda sık kullanılan sütunlar (user_id, status)
 * - JOIN'lerde kullanılan foreign key'ler
 * - ORDER BY'da kullanılan sütunlar
 *
 * NE ZAMAN EKLEME?
 * - Nadiren sorgulanan sütunlar
 * - Çok sık güncellenen sütunlar (her UPDATE index'i de günceller)
 * - Çok az satırı olan tablolar (index overhead'i faydayı aşar)
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. collections tablosuna user_id indexi
        // Neden: Kullanıcının koleksiyonlarını listelerken WHERE user_id = ? kullanılıyor
        Schema::table('collections', function (Blueprint $table) {
            $table->index('user_id', 'idx_collections_user');
            $table->index('is_public', 'idx_collections_public'); // Public koleksiyonları filtrelemek için
        });

        // 2. collection_movie pivot tablosuna movie_id indexi
        // Neden: Bir filmin hangi koleksiyonlarda olduğunu bulmak için
        Schema::table('collection_movie', function (Blueprint $table) {
            $table->index('movie_id', 'idx_collection_movie_movie');
        });
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropIndex('idx_collections_user');
            $table->dropIndex('idx_collections_public');
        });

        Schema::table('collection_movie', function (Blueprint $table) {
            $table->dropIndex('idx_collection_movie_movie');
        });
    }
};
