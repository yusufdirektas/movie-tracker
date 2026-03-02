<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TmdbService
{
    protected $baseUrl = 'https://api.themoviedb.org/3';
    protected $token;

    // Sınıf çağrıldığında ilk çalışan yer (Şifremizi hazırlıyoruz)
    public function __construct()
    {
        $this->token = config('services.tmdb.token');
    }

    /**
     * Tüm isteklerin geçtiği ANA MERKEZ (Böylece kodu tekrar etmiyoruz)
     */
    protected function request($endpoint, $params = [])
    {
        // Her isteğe otomatik olarak Türkçe dil desteğini ekliyoruz
        $defaultParams = ['language' => 'tr-TR'];

        return Http::withToken($this->token)
            ->get($this->baseUrl . $endpoint, array_merge($defaultParams, $params));
    }

    // 1. Film Arama Uç Noktası
    public function searchMovies($query)
    {
        return $this->request('/search/movie', [
            'query' => $query,
            'include_adult' => false
        ]);
    }

    // 2. Film Detayı ve Yönetmen Bilgisi Uç Noktası
    public function getMovieDetails($id)
    {
        return $this->request("/movie/{$id}", [
            'append_to_response' => 'credits'
        ]);
    }

    // 3. Öneriler Uç Noktası
    public function getRecommendations($id)
    {
        return $this->request("/movie/{$id}/recommendations");
    }

    // 4. Benzer Filmler Uç Noktası
    public function getSimilar($id)
    {
        return $this->request("/movie/{$id}/similar");
    }

    // 5. Vizyondakiler Uç Noktası
    public function getNowPlaying()
    {
        return $this->request('/movie/now_playing', [
            'region' => 'TR'
        ]);
    }
}
