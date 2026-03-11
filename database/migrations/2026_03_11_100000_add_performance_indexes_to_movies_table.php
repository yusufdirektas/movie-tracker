<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * =================================================================
 *  PERFORMANS MİGRASYONU: İndex ve Constraint Ekleme
 * =================================================================
 *
 *  📚 ÖĞRENİLECEK KAVRAMLAR:
 *
 *  1. DATABASE INDEX NEDİR?
 *     - Bir kitabın "içindekiler" sayfası gibi düşün.
 *     - Veritabanı, normalde bir veriyi bulmak için TÜM satırları tarar (Full Table Scan).
 *     - Index oluşturduğunda, veritabanı o kolon için bir "kısa yol haritası" (B-Tree) oluşturur.
 *     - Artık arama yaparken tüm tabloyu taramak yerine, direkt doğru satıra atlar.
 *     - Örnek: 10.000 filmlik tabloda "is_watched = true" aramak →
 *       Index YOKKEN: 10.000 satır taranır
 *       Index VARKEN: ~3-4 adımda bulunur (logaritmik karmaşıklık)
 *
 *  2. COMPOSITE INDEX NEDİR?
 *     - Birden fazla kolonu TEK bir index'te birleştirme.
 *     - Sıralama önemlidir! (user_id, is_watched) demek:
 *       "Önce user_id'ye göre grupla, sonra her grup içinde is_watched'a göre sırala"
 *     - Bu index şu sorguları hızlandırır:
 *       ✅ WHERE user_id = ?                      (ilk kolon tek başına da çalışır)
 *       ✅ WHERE user_id = ? AND is_watched = ?    (her iki kolon birlikte)
 *       ❌ WHERE is_watched = ?                    (sadece ikinci kolon → index kullanılmaz!)
 *
 *  3. UNIQUE CONSTRAINT NEDİR?
 *     - Veritabanına "bu kolon kombinasyonunda tekrar olamaz" kuralı koyar.
 *     - PHP'de zaten $user->movies()->where('tmdb_id', ...)->exists() kontrolü var,
 *       ama bu yarış koşullarında (race condition) işe yaramayabilir.
 *     - Unique constraint, VERİTABANI SEVİYESİNDE garantili koruma sağlar.
 *     - Bonus: Unique constraint otomatik olarak bir index de oluşturur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $table) {

            // ─────────────────────────────────────────────────────────
            // 1. COMPOSITE INDEX: user_id + is_watched
            // ─────────────────────────────────────────────────────────
            // NEDEN: Uygulamadaki en sık çalışan iki sorgu bunlar:
            //   - Film Arşivim:   WHERE user_id = ? AND is_watched = true
            //   - İzleme Listem:  WHERE user_id = ? AND is_watched = false
            //
            // Bu index bu iki sorguyu dramatik şekilde hızlandırır.
            // Laravel'e özel index adı veriyoruz ki migration geri alınırken
            // doğru index silinsin.
            $table->index(['user_id', 'is_watched'], 'idx_movies_user_watched');

            // ─────────────────────────────────────────────────────────
            // 2. INDEX: title
            // ─────────────────────────────────────────────────────────
            // NEDEN: Kullanıcı arama yaptığında:
            //   WHERE title LIKE '%Inception%'
            // sorgusu çalışır. LIKE '%...' başında % olduğunda index
            // tam verimli çalışmaz, ama SQLite'ta yine de yardımcı olur.
            // İleride MySQL/PostgreSQL'e geçilirse full-text search
            // eklenebilir.
            $table->index('title', 'idx_movies_title');

            // ─────────────────────────────────────────────────────────
            // 3. UNIQUE CONSTRAINT: user_id + tmdb_id
            // ─────────────────────────────────────────────────────────
            // NEDEN: Aynı kullanıcı aynı filmi iki kez eklemesin.
            // Controller'daki PHP kontrolünün ($alreadyExists) yanı sıra
            // veritabanı seviyesinde de garanti sağlar.
            //
            // DİKKAT: tmdb_id nullable olduğu için, tmdb_id NULL olan
            // kayıtlar bu constraint'ten etkilenmez (SQL standardı).
            $table->unique(['user_id', 'tmdb_id'], 'uq_movies_user_tmdb');
        });
    }

    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            // Geri alırken eklediğimiz index ve constraint'leri siliyoruz.
            // Index adlarını açıkça belirtmek önemli — Laravel bazen
            // otomatik isimlendirmeyi yanlış tahmin edebilir.
            $table->dropIndex('idx_movies_user_watched');
            $table->dropIndex('idx_movies_title');
            $table->dropUnique('uq_movies_user_tmdb');
        });
    }
};
