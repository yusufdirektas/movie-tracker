<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Repositories\Contracts\MovieRepositoryInterface;
use App\Support\CacheKeys;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * 📚 REPOSITORY + CACHE KOMBINASYONU
 *
 * Controller sadece:
 * 1. Cache kontrolü yapar
 * 2. Repository'den veri ister
 * 3. View'a gönderir
 *
 * Controller, veritabanı sorgularının nasıl yapıldığını BİLMEZ.
 * Bu bilgi Repository'de gizlidir (Encapsulation).
 */
class StatisticsController extends Controller
{
    public function __construct(
        protected MovieRepositoryInterface $movieRepository
    ) {}

    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        /**
         * 📚 Cache + Repository birlikte kullanımı
         *
         * Cache varsa → direkt döndür
         * Cache yoksa → Repository'den al → Cache'e yaz → döndür
         *
         * CacheKeys sınıfı tüm key'leri merkezi yönetir:
         * - Tutarlı isimlendirme (user:{id}:stats:v1)
         * - TTL sabitleri (TTL_SHORT = 5 dk)
         * - Kolay invalidation
         */
        $data = Cache::remember(
            CacheKeys::userStats($user->id),
            CacheKeys::TTL_SHORT,
            fn() => $this->movieRepository->getStatistics($user->id)
        );

        return view('movies.statistics', $data);
    }
}
