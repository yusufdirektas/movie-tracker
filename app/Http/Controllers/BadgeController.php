<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use App\Models\User;
use App\Services\BadgeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 📚 ROZET CONTROLLER
 *
 * Rozet listesi, ilerleme durumu ve detay sayfaları
 */
class BadgeController extends Controller
{
    public function __construct(
        private BadgeService $badgeService
    ) {}

    /**
     * Tüm rozetleri listele (kullanıcının ilerleme durumu ile)
     *
     * GET /badges
     */
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        // Tüm rozetleri kategorilere göre grupla
        $badges = Badge::ordered()->get();

        // Her rozet için ilerleme hesapla
        $badgesWithProgress = $badges->map(function (Badge $badge) use ($user) {
            return [
                'badge' => $badge,
                'progress' => $this->badgeService->getBadgeProgress($user, $badge),
            ];
        });

        // Kategorilere göre grupla
        $grouped = $badgesWithProgress->groupBy(function ($item) {
            return match ($item['badge']->requirement_type) {
                Badge::TYPE_WATCH_COUNT, Badge::TYPE_FIRST_MOVIE => 'Film İzleme',
                Badge::TYPE_GENRE_COUNT => 'Tür Uzmanlığı',
                Badge::TYPE_COMMENT_COUNT, Badge::TYPE_RATING_COUNT => 'Eleştirmenlik',
                Badge::TYPE_FOLLOW_COUNT => 'Sosyal',
                Badge::TYPE_COLLECTION_COUNT => 'Koleksiyon',
                Badge::TYPE_STREAK => 'Düzenlilik',
                default => 'Diğer',
            };
        });

        // İstatistikler
        $stats = [
            'total' => $badges->count(),
            'earned' => $user->badges()->count(),
            'percentage' => $badges->count() > 0
                ? round(($user->badges()->count() / $badges->count()) * 100)
                : 0,
        ];

        return view('badges.index', compact('grouped', 'stats'));
    }

    /**
     * Tek bir rozetin detayını göster
     *
     * GET /badges/{badge}
     */
    public function show(Badge $badge)
    {
        /** @var User $user */
        $user = Auth::user();

        $progress = $this->badgeService->getBadgeProgress($user, $badge);

        // Bu rozete sahip kullanıcılar
        $usersWithBadge = $badge->users()
            ->orderBy('user_badges.earned_at', 'desc')
            ->take(20)
            ->get();

        return view('badges.show', compact('badge', 'progress', 'usersWithBadge'));
    }
}
