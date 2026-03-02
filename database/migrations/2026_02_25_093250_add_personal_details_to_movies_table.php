<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            // TMDB'nin verdiği genel puanın (rating) hemen yanına kendi puanımızı ekliyoruz.
            // Başlangıçta boş (nullable) olabilir.
            $table->tinyInteger('personal_rating')->nullable()->after('rating');

            // Ne zaman izlediğimizi tutacağımız tarih sütunu.
            $table->date('watched_at')->nullable()->after('is_watched');
        });
    }

    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            // Eğer işlemi geri almak istersek bu sütunları uçur:
            $table->dropColumn(['personal_rating', 'watched_at']);
        });
    }
};
