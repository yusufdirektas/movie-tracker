<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Seeder;

/**
 * 📚 ROZET SEEDER
 *
 * Tüm rozetleri veritabanına ekler.
 * Bu seeder production'da da çalıştırılabilir (upsert kullanıyoruz).
 *
 * @KAVRAM: Seeder
 * - Veritabanına örnek/başlangıç verisi ekler
 * - php artisan db:seed --class=BadgeSeeder
 * - Migration'dan farklı: schema değil, veri ekler
 *
 * @KAVRAM: upsert()
 * - Insert or Update yapar
 * - Aynı ID varsa günceller, yoksa ekler
 * - Migration sonrası tekrar çalıştırılabilir
 */
class BadgeSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            // ═══════════════════════════════════════════════════════════════
            // 🎬 FİLM İZLEME ROZETLERİ
            // ═══════════════════════════════════════════════════════════════
            [
                'id' => 'first-movie',
                'name' => 'İlk Adım',
                'description' => 'İlk filmini arşivine ekledin!',
                'icon' => '🌟',
                'requirement_type' => Badge::TYPE_FIRST_MOVIE,
                'requirement_value' => 1,
                'requirement_genre' => null,
                'sort_order' => 1,
            ],
            [
                'id' => 'film-lover',
                'name' => 'Film Sever',
                'description' => '10 film izleyerek sinema yolculuğuna başladın.',
                'icon' => '🎬',
                'requirement_type' => Badge::TYPE_WATCH_COUNT,
                'requirement_value' => 10,
                'requirement_genre' => null,
                'sort_order' => 2,
            ],
            [
                'id' => 'cinephile',
                'name' => 'Sinefil',
                'description' => '50 film izleyerek gerçek bir sinema tutkunu oldun!',
                'icon' => '🎥',
                'requirement_type' => Badge::TYPE_WATCH_COUNT,
                'requirement_value' => 50,
                'requirement_genre' => null,
                'sort_order' => 3,
            ],
            [
                'id' => 'film-master',
                'name' => 'Film Ustası',
                'description' => '100 film! Artık bir sinema uzmanısın.',
                'icon' => '🏆',
                'requirement_type' => Badge::TYPE_WATCH_COUNT,
                'requirement_value' => 100,
                'requirement_genre' => null,
                'sort_order' => 4,
            ],
            [
                'id' => 'movie-marathon',
                'name' => 'Maraton Koşucusu',
                'description' => '250 film izledin! Helal olsun!',
                'icon' => '🏃',
                'requirement_type' => Badge::TYPE_WATCH_COUNT,
                'requirement_value' => 250,
                'requirement_genre' => null,
                'sort_order' => 5,
            ],

            // ═══════════════════════════════════════════════════════════════
            // 🎭 TÜR BAZLI ROZETLERİ
            // ═══════════════════════════════════════════════════════════════
            [
                'id' => 'horror-hunter',
                'name' => 'Korku Avcısı',
                'description' => '20 korku filmi izleyerek cesaretini kanıtladın!',
                'icon' => '👻',
                'requirement_type' => Badge::TYPE_GENRE_COUNT,
                'requirement_value' => 20,
                'requirement_genre' => 'Korku',
                'sort_order' => 10,
            ],
            [
                'id' => 'romantic-soul',
                'name' => 'Romantik Ruh',
                'description' => '20 romantik film izledin. Aşk seni bulacak!',
                'icon' => '💕',
                'requirement_type' => Badge::TYPE_GENRE_COUNT,
                'requirement_value' => 20,
                'requirement_genre' => 'Romantik',
                'sort_order' => 11,
            ],
            [
                'id' => 'action-hero',
                'name' => 'Aksiyon Kahramanı',
                'description' => '20 aksiyon filmi ile adrenalin dozunu aldın!',
                'icon' => '💥',
                'requirement_type' => Badge::TYPE_GENRE_COUNT,
                'requirement_value' => 20,
                'requirement_genre' => 'Aksiyon',
                'sort_order' => 12,
            ],
            [
                'id' => 'comedy-king',
                'name' => 'Komedi Kralı',
                'description' => '20 komedi filmi ile gülmekten karnın ağrıdı!',
                'icon' => '😂',
                'requirement_type' => Badge::TYPE_GENRE_COUNT,
                'requirement_value' => 20,
                'requirement_genre' => 'Komedi',
                'sort_order' => 13,
            ],
            [
                'id' => 'drama-enthusiast',
                'name' => 'Drama Tutkunu',
                'description' => '20 drama filmi ile duygusal bir yolculuk yaptın.',
                'icon' => '🎭',
                'requirement_type' => Badge::TYPE_GENRE_COUNT,
                'requirement_value' => 20,
                'requirement_genre' => 'Dram',
                'sort_order' => 14,
            ],
            [
                'id' => 'scifi-explorer',
                'name' => 'Bilim Kurgu Kaşifi',
                'description' => '20 bilim kurgu filmi ile galaksiyi dolaştın!',
                'icon' => '🚀',
                'requirement_type' => Badge::TYPE_GENRE_COUNT,
                'requirement_value' => 20,
                'requirement_genre' => 'Bilim Kurgu',
                'sort_order' => 15,
            ],

            // ═══════════════════════════════════════════════════════════════
            // 💬 SOSYAL ROZETLERİ
            // ═══════════════════════════════════════════════════════════════
            [
                'id' => 'critic',
                'name' => 'Eleştirmen',
                'description' => '10 yorum yaparak fikirlerini paylaştın.',
                'icon' => '📝',
                'requirement_type' => Badge::TYPE_COMMENT_COUNT,
                'requirement_value' => 10,
                'requirement_genre' => null,
                'sort_order' => 20,
            ],
            [
                'id' => 'social-butterfly',
                'name' => 'Sosyal Kelebek',
                'description' => '10 kişiyi takip ederek topluluğa katıldın!',
                'icon' => '👥',
                'requirement_type' => Badge::TYPE_FOLLOW_COUNT,
                'requirement_value' => 10,
                'requirement_genre' => null,
                'sort_order' => 21,
            ],

            // ═══════════════════════════════════════════════════════════════
            // 📚 KOLEKSİYON ROZETLERİ
            // ═══════════════════════════════════════════════════════════════
            [
                'id' => 'collector',
                'name' => 'Koleksiyoner',
                'description' => '5 koleksiyon oluşturarak filmlerini düzenledin.',
                'icon' => '📚',
                'requirement_type' => Badge::TYPE_COLLECTION_COUNT,
                'requirement_value' => 5,
                'requirement_genre' => null,
                'sort_order' => 30,
            ],

            // ═══════════════════════════════════════════════════════════════
            // ⭐ PUANLAMA ROZETLERİ
            // ═══════════════════════════════════════════════════════════════
            [
                'id' => 'rater',
                'name' => 'Puanlayıcı',
                'description' => '25 filme puan vererek değerlendirme yaptın.',
                'icon' => '⭐',
                'requirement_type' => Badge::TYPE_RATING_COUNT,
                'requirement_value' => 25,
                'requirement_genre' => null,
                'sort_order' => 40,
            ],

            // ═══════════════════════════════════════════════════════════════
            // 🔥 SERİ ROZETLERİ
            // ═══════════════════════════════════════════════════════════════
            [
                'id' => 'weekly-streak',
                'name' => 'Haftalık Seri',
                'description' => '7 gün üst üste film izledin! Harika tempO!',
                'icon' => '🔥',
                'requirement_type' => Badge::TYPE_STREAK,
                'requirement_value' => 7,
                'requirement_genre' => null,
                'sort_order' => 50,
            ],
        ];

        // Upsert: varsa güncelle, yoksa ekle
        foreach ($badges as $badge) {
            Badge::updateOrCreate(
                ['id' => $badge['id']],
                $badge
            );
        }

        $this->command->info('✅ ' . count($badges) . ' rozet eklendi/güncellendi.');
    }
}
