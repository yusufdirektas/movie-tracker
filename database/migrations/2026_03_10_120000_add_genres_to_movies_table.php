<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Filmlere tür (genre) bilgisi ekliyoruz.
 *
 * JSON sütun kullanma sebebi:
 * Bir filmin birden fazla türü olabilir (Aksiyon + Bilim Kurgu + Gerilim).
 * Ayrı bir genres tablosu + pivot tablo oluşturmak bu proje için fazla karmaşık.
 * JSON sütun ile ["Aksiyon", "Bilim Kurgu", "Gerilim"] şeklinde basitçe saklıyoruz.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->json('genres')->nullable()->after('director');
        });
    }

    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn('genres');
        });
    }
};
