<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 📚 AKTİVİTE TABLOSU MİGRATİON
 *
 * Bu tablo kullanıcıların yaptığı tüm aksiyonları kaydeder.
 * Takip feed'i için kullanılır: "Ahmet 'Inception' filmini izledi"
 *
 * @KAVRAM: Polymorphic İlişki (morphTo)
 * - subject_type: 'App\Models\Movie', 'App\Models\Collection', 'App\Models\User'
 * - subject_id: İlgili modelin ID'si
 * - Bu sayede tek tablo ile farklı model türlerini referans edebiliriz
 *
 * @KAVRAM: JSON Kolonu
 * - metadata: Ek bilgiler (rating değeri, eski/yeni değerler vs.)
 * - SQLite'da JSON fonksiyonları ile sorgulanabilir
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();

            // Aktiviteyi yapan kullanıcı
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Aktivite türü: watched, rated, added_to_watchlist, commented, followed, created_collection
            $table->string('type', 50);

            // Polymorphic ilişki: Hangi model üzerinde aksiyon yapıldı?
            // nullableMorphs() → subject_type ve subject_id kolonları oluşturur (NULL olabilir)
            $table->nullableMorphs('subject');

            // Ek veriler (rating değeri, eski değer vs.)
            $table->json('metadata')->nullable();

            $table->timestamps();

            // INDEX: Kullanıcının aktivitelerini hızlı çekmek için
            $table->index(['user_id', 'created_at']);

            // INDEX: Belirli bir konu hakkındaki aktiviteleri bulmak için
            // NOT: nullableMorphs() zaten bu index'i oluşturur, tekrar eklemeye gerek yok
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
