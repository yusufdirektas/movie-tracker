<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 📚 PROFİL ALANLARI MİGRATION
 *
 * @KAVRAM: JSON Column
 * - showcase_movies alanı JSON tipinde
 * - Laravel bu alanı otomatik array'e çevirir ($casts ile)
 * - Vitrin filmlerinin ID'lerini array olarak saklar
 *
 * @KAVRAM: nullable()
 * - Alan boş bırakılabilir
 * - Kullanıcı profili doldurmak zorunda değil
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Profil fotoğrafı - dosya yolu saklanır
            $table->string('avatar')->nullable()->after('name');

            // Hakkında metni - uzun metin için text tipi
            $table->text('bio')->nullable()->after('avatar');

            // Vitrin filmleri - JSON array olarak film ID'leri
            // Örnek: [1, 5, 12] şeklinde max 5 film
            $table->json('showcase_movies')->nullable()->after('bio');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar', 'bio', 'showcase_movies']);
        });
    }
};
