<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Film ekleme isteğinin doğrulama kuralları.
 *
 * Controller'a ulaşmadan ÖNCE çalışır:
 *  1. authorize() → Bu kullanıcı bu isteği yapabilir mi?
 *  2. rules()     → Gelen veri kurallara uyuyor mu?
 *
 * Kural geçemezse Laravel otomatik olarak kullanıcıyı geri gönderir
 * ve hata mesajlarını session'a yazar.
 */
class StoreMovieRequest extends FormRequest
{
    /**
     * Bu isteği yapmaya yetkili mi?
     * Auth middleware zaten kontrol ediyor, o yüzden true.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Doğrulama kuralları.
     *
     * 'tmdb_id' → zorunlu, sayısal olmalı (güvenlik: rastgele metin gönderilmesini engeller)
     * 'is_watched' → opsiyonel, boolean olmalı (0 veya 1)
     */
    public function rules(): array
    {
        return [
            'tmdb_id'    => ['required', 'integer'],
            'is_watched' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Türkçe hata mesajları.
     * Varsayılan İngilizce mesajlar yerine kullanıcı dostu mesajlar.
     */
    public function messages(): array
    {
        return [
            'tmdb_id.required' => 'Film seçimi zorunludur.',
            'tmdb_id.integer'  => 'Geçersiz film kimliği.',
        ];
    }
}
