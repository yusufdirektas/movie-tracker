<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Site artık sadece film destekliyor, tüm TV içeriklerini sil.
     */
    public function up(): void
    {
        // Collection pivot'lardan da kaldır
        DB::table('collection_movie')
            ->whereIn('movie_id', function ($query) {
                $query->select('id')->from('movies')->where('media_type', 'tv');
            })
            ->delete();

        // TV içeriklerini sil
        DB::table('movies')->where('media_type', 'tv')->delete();

        // Import item'lardan da TV olanları temizle
        DB::table('import_items')->where('media_type', 'tv')->update([
            'media_type' => null,
            'error_message' => 'TV içerikleri artık desteklenmiyor.',
        ]);
    }

    /**
     * Reverse the migrations.
     * Bu işlem geri alınamaz - veriler silinmiştir.
     */
    public function down(): void
    {
        // TV içerikleri geri getirilemez
    }
};
