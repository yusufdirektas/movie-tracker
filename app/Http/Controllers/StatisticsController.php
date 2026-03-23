<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Repositories\Contracts\MovieRepositoryInterface;
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
        
        $cacheKey = "user_stats_{$user->id}";
        
        /**
         * 📚 Cache + Repository birlikte kullanımı
         * 
         * Cache varsa → direkt döndür
         * Cache yoksa → Repository'den al → Cache'e yaz → döndür
         */
        $data = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($user) {
            return $this->movieRepository->getStatistics($user->id);
        });

        return view('movies.statistics', $data);
    }
}
