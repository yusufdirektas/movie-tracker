<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 📚 SELF-REFERENTIAL MANY-TO-MANY İLİŞKİ
 *
 * Bu tablo, kullanıcıların birbirini takip etmesini sağlar.
 * "Self-referential" çünkü aynı tablo (users) kendisine referans veriyor.
 *
 * Örnek:
 *   follower_id=1, following_id=2 → Kullanıcı 1, Kullanıcı 2'yi takip ediyor
 *   follower_id=2, following_id=1 → Kullanıcı 2, Kullanıcı 1'i takip ediyor (karşılıklı)
 *
 * @KAVRAM: Pivot Table
 * Many-to-Many ilişkilerde iki tablonun ID'lerini tutan ara tabloya "pivot table" denir.
 * Laravel'de belongsToMany() ile otomatik yönetilir.
 *
 * @KAVRAM: Composite Unique Index
 * [follower_id, following_id] çifti benzersiz olmalı - aynı kişiyi iki kez takip edemezsin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follows', function (Blueprint $table) {
            $table->id();

            // Takip EDEN kullanıcı
            $table->foreignId('follower_id')
                  ->constrained('users')
                  ->onDelete('cascade'); // Kullanıcı silinirse takipler de silinsin

            // Takip EDİLEN kullanıcı
            $table->foreignId('following_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            $table->timestamps();

            // Aynı kişiyi iki kez takip etmeyi engelle
            $table->unique(['follower_id', 'following_id']);

            // Sorgu performansı için indexler
            $table->index('follower_id');  // "Kimi takip ediyorum?" sorguları için
            $table->index('following_id'); // "Beni kim takip ediyor?" sorguları için
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
