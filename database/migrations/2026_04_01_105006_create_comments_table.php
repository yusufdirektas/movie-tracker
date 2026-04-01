<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 📚 COMMENTS TABLOSU
 *
 * @KAVRAM: Polymorphic İlişki
 *
 * Normalde: comments tablosu sadece movie_id tutar
 * Polymorphic: commentable_type + commentable_id ile HER modele yorum yapılabilir
 *
 * Örnek:
 * - commentable_type = 'App\Models\Movie', commentable_id = 5 → Film yorumu
 * - commentable_type = 'App\Models\Collection', commentable_id = 3 → Koleksiyon yorumu
 *
 * Bu esneklik sağlar: Gelecekte koleksiyonlara da yorum eklenebilir.
 *
 * @KAVRAM: Foreign Key Constraints
 *
 * constrained()->cascadeOnDelete() ne yapar?
 * - Kullanıcı silinirse → yorumları da silinir
 * - Film silinirse → yorumları da silinir
 * - Veri bütünlüğü korunur
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();

            // Yorum sahibi
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // Polymorphic ilişki - hangi modele yorum yapılıyor?
            $table->morphs('commentable'); // commentable_type + commentable_id oluşturur

            // Yorum içeriği (500 karakter sınırı)
            $table->string('body', 500);

            // Spoiler içeriyor mu?
            $table->boolean('has_spoiler')->default(false);

            $table->timestamps();

            // Index: Bir içeriğin yorumlarını hızlı çek
            $table->index(['commentable_type', 'commentable_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
