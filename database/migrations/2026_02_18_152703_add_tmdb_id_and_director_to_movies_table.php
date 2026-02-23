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
        // tmdb_id sütununu ekle ve aramalar için indexle
        $table->string('tmdb_id')->nullable()->after('id')->index();

        // director sütununu başlığın hemen sonrasına ekle
        $table->string('director')->nullable()->after('title');
    });
}

public function down(): void
{
    Schema::table('movies', function (Blueprint $table) {
        // Geri alırken eklediğimiz sütunları siliyoruz
        $table->dropColumn(['tmdb_id', 'director']);
    });
}
};
