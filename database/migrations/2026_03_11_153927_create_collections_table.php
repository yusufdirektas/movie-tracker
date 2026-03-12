<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 📚 Collections tablosu:
     * Her kullanıcı kendi film koleksiyonlarını oluşturabilir.
     * Örnek: "Marvel Filmleri", "Tarantino Klasikleri", "Aile ile İzlenenler"
     */
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('color', 7)->default('#6366f1'); // HEX renk kodu
            $table->string('icon')->default('folder'); // FontAwesome ikon adı
            $table->timestamps();
        });

        // Pivot tablosu: Bir filmin birden fazla koleksiyona ait olabilmesi için (Many-to-Many)
        Schema::create('collection_movie', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('movie_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['collection_id', 'movie_id']); // Aynı film aynı koleksiyona iki kez eklenemez
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_movie');
        Schema::dropIfExists('collections');
    }
};
