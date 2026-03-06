<div align="center">

<img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
<img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
<img src="https://img.shields.io/badge/Alpine.js-3.x-8BC0D0?style=for-the-badge&logo=alpinedotjs&logoColor=white" alt="Alpine.js">
<img src="https://img.shields.io/badge/Tailwind_CSS-3.x-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="Tailwind CSS">
<img src="https://img.shields.io/badge/TMDB-API-01B4E4?style=for-the-badge&logo=themoviedatabase&logoColor=white" alt="TMDB">

# 🎬 Film Arşivim

**Kişisel film arşivini yönetmek için geliştirilmiş, TMDB entegrasyonlu modern bir web uygulaması.**

</div>

---

## 📖 Proje Hakkında

Film Arşivim, izlediğin ve izlemek istediğin filmleri tek bir platformda yönetmeni sağlayan bir kişisel film takip uygulamasıdır. TMDB (The Movie Database) API entegrasyonu sayesinde film adını yazdığında afiş, yönetmen, özet, puan ve süre bilgileri otomatik olarak çekilir.

---

## ✨ Özellikler

| Özellik | Açıklama |
|---------|----------|
| 🔍 **Akıllı Film Arama** | TMDB API üzerinden gerçek zamanlı film arama |
| 📚 **Film Arşivi** | İzlediğin filmleri kişisel puanlarınla kaydet |
| 📋 **İzleme Listesi** | İzlemek istediğin filmleri takip et |
| 📥 **Toplu İçe Aktarma** | Letterboxd listeleri dahil toplu film ekleme |
| 🎯 **Kişisel Öneriler** | Son eklediğin filme göre öneri sistemi (TMDB) |
| 🎟️ **Vizyondakiler** | Türkiye'deki güncel vizyondaki filmleri görüntüle |
| 📊 **İstatistikler** | Toplam izleme süresi, film sayısı ve zirvedeki film |
| ⭐ **Kişisel Puanlama** | Her film için 1-5 yıldız kendi puanını ver |
| 📱 **Tam Responsive** | Mobil ve masaüstü uyumlu modern arayüz |

---

## 🛠️ Teknoloji Yığını

### Backend
- **[Laravel 12](https://laravel.com/)** — PHP web framework
- **PHP 8.2+** — Uygulama dili
- **Laravel Breeze** — Kimlik doğrulama scaffolding
- **SQLite** — Yerel geliştirme veritabanı

### Frontend
- **[Blade](https://laravel.com/docs/blade)** — Laravel şablon motoru
- **[Alpine.js 3](https://alpinejs.dev/)** — Hafif reaktif JavaScript kütüphanesi
- **[Tailwind CSS 3](https://tailwindcss.com/)** — Utility-first CSS framework
- **[Vite](https://vitejs.dev/)** — Frontend build aracı

### Harici Servisler
- **[TMDB API](https://www.themoviedb.org/documentation/api)** — Film veritabanı ve metadata

---

## 📋 Gereksinimler

- PHP >= 8.2
- Composer
- Node.js >= 18 & NPM
- SQLite (veya tercih ettiğin bir veritabanı)
- [TMDB API Token](https://www.themoviedb.org/settings/api)

---

## 🚀 Kurulum

### 1. Projeyi klonla

```bash
git clone https://github.com/yusufdirektas/movie-tracker.git
cd movie-tracker
```

### 2. Otomatik kurulum (önerilen)

```bash
composer run setup
```

Bu komut sırasıyla şunları yapar:
- `composer install`
- `.env` dosyasını oluşturur
- Uygulama anahtarı üretir
- Veritabanı migration'larını çalıştırır
- `npm install` ve `npm run build`

### 3. Manuel kurulum

```bash
# PHP bağımlılıklarını yükle
composer install

# Ortam dosyasını oluştur
cp .env.example .env
php artisan key:generate

# Veritabanını hazırla
touch database/database.sqlite
php artisan migrate

# NPM bağımlılıklarını yükle ve derle
npm install
npm run build
```

### 4. TMDB API Token'ı ekle

`.env` dosyasına şu satırı ekle:

```env
TMDB_TOKEN=your_api_read_access_token_here
```

> TMDB Token almak için [https://www.themoviedb.org/settings/api](https://www.themoviedb.org/settings/api) adresine kayıt ol. **API Read Access Token (v4)** kullanılır.

`config/services.php` dosyasında bu token şu şekilde kullanılır:
```php
'tmdb' => [
    'token' => env('TMDB_TOKEN'),
],
```

---

## 💻 Geliştirme Ortamını Başlatma

```bash
composer run dev
```

Bu komut aynı anda şunları başlatır:
- `php artisan serve` — Laravel geliştirme sunucusu
- `php artisan queue:listen` — Kuyruk işleyici
- `php artisan pail` — Log izleyici
- `npm run dev` — Vite HMR sunucusu

Uygulama varsayılan olarak `http://localhost:8000` adresinde çalışır.

---

## 🗄️ Veritabanı Yapısı

```
users
├── id
├── name
├── email
└── password

movies
├── id
├── user_id (FK → users)     # Kime ait
├── tmdb_id                  # TMDB film ID'si
├── title                    # Film adı
├── director                 # Yönetmen
├── poster_path              # Afiş yolu (TMDB)
├── rating                   # TMDB puanı (0-10)
├── personal_rating          # Kullanıcı puanı (1-5)
├── runtime                  # Süre (dakika)
├── overview                 # Film özeti
├── release_date             # Çıkış tarihi
├── is_watched               # İzlendi mi?
├── watched_at               # İzlenme tarihi
└── timestamps
```

### İlişkiler

```
User  ──hasMany──▶  Movie
Movie ──belongsTo──▶ User
```

---

## 🛣️ Rota Haritası

| Method | URL | Açıklama | Auth |
|--------|-----|----------|------|
| `GET` | `/` | Hoş geldiniz sayfası | — |
| `GET` | `/movies` | Film arşivi | ✅ |
| `GET` | `/movies/watchlist` | İzlenecekler listesi | ✅ |
| `GET` | `/movies/create` | Film arama sayfası | ✅ |
| `POST` | `/movies` | Film kaydet | ✅ |
| `PATCH` | `/movies/{id}` | Güncelle (puan/izlendi) | ✅ |
| `DELETE` | `/movies/{id}` | Film sil | ✅ |
| `GET` | `/movies/import-list` | Toplu içe aktarma | ✅ |
| `GET` | `/movies/api-search` | Canlı arama (JSON) | ✅ |
| `GET` | `/movies/recommendations` | Kişisel öneriler | ✅ |
| `GET` | `/movies/now-playing` | Vizyondaki filmler | ✅ |

---

## 🧪 Testleri Çalıştırma

```bash
composer run test
```

veya

```bash
php artisan test
```

---

## 📁 Proje Yapısı

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── MovieController.php     # Tüm film işlemleri
│   │   └── ProfileController.php   # Profil yönetimi
│   └── Requests/
├── Models/
│   ├── Movie.php                   # Film modeli
│   └── User.php                    # Kullanıcı modeli
└── Services/
    └── TmdbService.php             # TMDB API istemcisi

resources/views/
├── layouts/
│   ├── app.blade.php               # Ana layout
│   └── navigation.blade.php        # Navigasyon
└── movies/
    ├── index.blade.php             # Film arşivi
    ├── watchlist.blade.php         # İzleme listesi
    ├── create.blade.php            # Film ekle
    ├── import.blade.php            # Toplu içe aktar
    ├── recommendations.blade.php   # Öneriler
    └── now_playing.blade.php       # Vizyondakiler

routes/
├── web.php                         # Web rotaları
└── auth.php                        # Auth rotaları
```

---

## ⚡ Performans

- **Cache:** TMDB'den gelen öneriler **24 saat**, vizyondaki filmler **12 saat** önbellekte tutulur — gereksiz API istekleri engellenir.
- **Sayfalama:** Film listeleri 20'li gruplar halinde sayfalanır.
- **Lazy Loading:** Görseller `loading="lazy"` ile yüklenir.

---

## 🤝 Katkıda Bulunma

1. Bu repoyu fork'la
2. Feature branch oluştur: `git checkout -b feature/yeni-ozellik`
3. Değişikliklerini commit'le: `git commit -m 'feat: yeni özellik eklendi'`
4. Branch'ini push'la: `git push origin feature/yeni-ozellik`
5. Pull Request aç

---

## 📄 Lisans

Bu proje [MIT Lisansı](LICENSE) ile lisanslanmıştır.

---

<div align="center">
  <p>Developed by <strong>YSD</strong></p>
  <p>
    <a href="https://www.themoviedb.org/">
      <img src="https://www.themoviedb.org/assets/2/v4/logos/v2/blue_short-8e7b30f73a4020692ccca9c88bafe5dcb20f475b19e80a4498b55b2af1f4d8b.svg" width="120" alt="TMDB">
    </a>
  </p>
  <p><em>Bu ürün TMDB API'sini kullanır ancak TMDB tarafından onaylanmamış veya sertifikalandırılmamıştır.</em></p>
</div>
