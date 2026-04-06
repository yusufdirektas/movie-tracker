<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 📚 ROZET SİSTEMİ TABLOLARI
 *
 * İki tablo oluşturuyoruz:
 * 1. badges: Tüm rozetlerin tanımları
 * 2. user_badges: Hangi kullanıcının hangi rozeti kazandığı (pivot tablo)
 *
 * @KAVRAM: Many-to-Many İlişki
 * - Bir kullanıcı birden fazla rozet kazanabilir
 * - Bir rozet birden fazla kullanıcıda olabilir
 * - Bu ilişkiyi pivot tablo (user_badges) sağlar
 *
 * @KAVRAM: String Primary Key
 * - badges tablosunda id yerine string kullanıyoruz ('film-lover')
 * - Kod okunabilirliği artar: Badge::find('film-lover')
 * - Seeder'da ID'leri ezberlemek gerekmez
 */
return new class extends Migration
{
    public function up(): void
    {
        // Rozetler tablosu
        Schema::create('badges', function (Blueprint $table) {
            // String ID: 'film-lover', 'horror-hunter' gibi
            $table->string('id', 50)->primary();

            // Görünen isim
            $table->string('name', 100);

            // Açıklama
            $table->text('description');

            // Emoji ikon
            $table->string('icon', 10);

            // Kazanma koşulu türü
            // watch_count, genre_count, comment_count, follow_count, collection_count, streak
            $table->string('requirement_type', 50);

            // Gerekli değer (örn: 10 film izle)
            $table->unsignedInteger('requirement_value');

            // Tür bazlı rozetler için (örn: 'Horror', 'Romance')
            $table->string('requirement_genre', 50)->nullable();

            // Sıralama (profilde gösterim sırası)
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
        });

        // Kullanıcı-Rozet pivot tablosu
        Schema::create('user_badges', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('badge_id', 50);
            $table->timestamp('earned_at');

            // Composite primary key
            $table->primary(['user_id', 'badge_id']);

            // Foreign key
            $table->foreign('badge_id')->references('id')->on('badges')->cascadeOnDelete();

            // Index: Kullanıcının rozetlerini hızlı çekmek için
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_badges');
        Schema::dropIfExists('badges');
    }
};
