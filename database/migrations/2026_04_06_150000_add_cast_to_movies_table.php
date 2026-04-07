<?php

/**
 * 📚 MİGRASYON: `cast` (Oyuncu Kadrosu) Kolonu Ekleme
 *
 * ─────────────────────────────────────────────────────────────
 * NEDEN JSON KOLONU?
 * ─────────────────────────────────────────────────────────────
 *
 * Bir filmin birden fazla oyuncusu vardır. Bunu saklama seçenekleri:
 *
 * 1. Ayrı tablo (actors + movie_actor pivot) → Çok normalleştirilmiş ama karmaşık
 * 2. JSON kolonu → Basit, hızlı okuma, analiz için yeterli
 *
 * Bizim senaryomuzda sadece oyuncu **isimlerini** saklıyoruz
 * (detaylı oyuncu profili değil), bu yüzden JSON yeterli.
 * Aynı yaklaşımı `genres` alanında da kullanıyoruz.
 *
 * Saklanan format: ["Tom Hanks", "Leonardo DiCaprio", "Scarlett Johansson"]
 * İlk 5 oyuncu saklanacak (TMDB billing order'a göre baş roller).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            // nullable() → Eski filmler bu alan olmadan eklendi, null olabilir
            // after('director') → Sütunu director'dan sonra yerleştir (okunabilirlik)
            $table->json('cast')->nullable()->after('director');
        });
    }

    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn('cast');
        });
    }
};
