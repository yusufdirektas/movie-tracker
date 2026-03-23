<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * 📚 BLADE COMPONENT OLUŞTURMA
 *
 * Component Nedir?
 * - Tekrar kullanılabilir UI parçacıklarıdır
 * - Props (parametreler) alabilirler
 * - Kendi logic'lerini içerebilirler
 *
 * Neden Component Kullandık?
 * - Film posteri birçok yerde kullanılıyor (index, show, watchlist, recommendations)
 * - Lazy loading, placeholder, hata yönetimi hepsinde aynı olmalı
 * - Tek yerden değişiklik yapabilmek için (DRY prensibi)
 *
 * Kullanım:
 *   <x-movie-poster :path="$movie->poster_path" :alt="$movie->title" />
 *   <x-movie-poster :path="$movie->poster_path" size="w342" />
 */
class MoviePoster extends Component
{
    /**
     * TMDB poster yolu (örn: /abc123.jpg)
     */
    public ?string $path;

    /**
     * Alt text (erişilebilirlik için önemli)
     */
    public string $alt;

    /**
     * 📚 TMDB RESIM BOYUTLARI
     *
     * TMDB farklı boyutlarda resim sunar:
     * - w92, w154, w185, w342, w500, w780, original
     *
     * Neden farklı boyutlar?
     * - Küçük kartlar için w342 yeterli (daha az veri, daha hızlı)
     * - Detay sayfası için w500 veya w780
     * - Tam ekran için original
     *
     * Mobilde w342, masaüstünde w500 kullanmak bandwidth tasarrufu sağlar.
     */
    public string $size;

    /**
     * Ekstra CSS sınıfları
     */
    public string $class;

    public function __construct(
        ?string $path = null,
        string $alt = 'Film Posteri',
        string $size = 'w500',
        string $class = ''
    ) {
        $this->path = $path;
        $this->alt = $alt;
        $this->size = $size;
        $this->class = $class;
    }

    /**
     * Tam TMDB URL'ini oluştur
     *
     * 📚 HELPER METOD
     * Component içinde logic tutmak view'ı temiz tutar.
     * View sadece görüntüleme ile ilgilenir.
     */
    public function imageUrl(): ?string
    {
        if (empty($this->path)) {
            return null;
        }

        return "https://image.tmdb.org/t/p/{$this->size}{$this->path}";
    }

    /**
     * Düşük kaliteli placeholder URL'i (blur-up efekti için)
     *
     * 📚 BLUR-UP TEKNİĞİ
     * 1. Önce çok küçük (w92) resim yüklenir - hızlı, bulanık
     * 2. Arka planda büyük resim yüklenir
     * 3. Büyük resim hazır olunca geçiş yapılır
     *
     * Kullanıcı deneyimi: Boş alan yerine bulanık önizleme görür
     */
    public function placeholderUrl(): ?string
    {
        if (empty($this->path)) {
            return null;
        }

        return "https://image.tmdb.org/t/p/w92{$this->path}";
    }

    public function render(): View|Closure|string
    {
        return view('components.movie-poster');
    }
}
