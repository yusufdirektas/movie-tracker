<?php

namespace App\Observers;

use App\Models\Activity;
use App\Models\Comment;
use App\Models\Movie;
use App\Services\BadgeService;

/**
 * 📚 YORUM OBSERVER
 *
 * Yorum yapıldığında aktivite oluşturur ve rozetleri kontrol eder.
 *
 * @KAVRAM: Observer Registration
 * - Bu observer'ı Comment modeline bağlamak için:
 *   Comment.php'de #[ObservedBy([CommentObserver::class])] ekle
 *   VEYA AppServiceProvider'da Comment::observe(CommentObserver::class);
 */
class CommentObserver
{
    /**
     * Yorum oluşturulduğunda aktivite kaydet ve rozet kontrol et
     */
    public function created(Comment $comment): void
    {
        // Sadece film yorumları için aktivite oluştur
        // (Collection yorumları vs. için farklı logic gerekebilir)
        if ($comment->commentable_type === Movie::class) {
            $movie = $comment->commentable;

            if ($movie) {
                Activity::logCommented($comment->user, $comment, $movie);
            }
        }

        // 📚 ROZET KONTROLÜ
        // Yorum sayısına bağlı rozetleri kontrol et (critic)
        // app() ile resolve etmek constructor injection'dan daha güvenilir test ortamında
        app(BadgeService::class)->checkAndAwardBadges($comment->user);
    }
}
