<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 📚 AKTİVİTE FEED CONTROLLER
 *
 * Kullanıcının takip ettiği kişilerin aktivitelerini gösterir.
 *
 * @KAVRAM: Cursor-Based Pagination (Infinite Scroll için)
 *
 * Geleneksel sayfalama: /feed?page=2
 * - Sorun: Yeni aktivite eklenince sayfa kayar, aynı içerik tekrar görülür
 *
 * Cursor pagination: /feed?before=123
 * - "123 ID'li aktiviteden öncekiler" der
 * - Yeni içerik eklense bile eski içerikler kaybolmaz
 * - Infinite scroll için ideal
 */
class ActivityController extends Controller
{
    /**
     * Feed sayfasını göster
     *
     * GET /feed
     *
     * @KAVRAM: Eager Loading (with())
     * - $activities->user, $activities->subject her erişimde query atacaktı
     * - with(['user', 'subject']) ile tek seferde yükler
     * - N+1 query problemini önler
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // İlk yükleme veya "Daha fazla göster" için
        $beforeId = $request->input('before');

        $activities = $user->getFeed(20, $beforeId);

        // AJAX isteği (infinite scroll için)
        if ($request->wantsJson()) {
            return response()->json([
                'activities' => $activities->map(function ($activity) {
                    return [
                        'id' => $activity->id,
                        'html' => view('components.activity-card', compact('activity'))->render(),
                    ];
                }),
                'hasMore' => $activities->count() === 20,
                'lastId' => $activities->last()?->id,
            ]);
        }

        return view('activities.index', compact('activities'));
    }
}
