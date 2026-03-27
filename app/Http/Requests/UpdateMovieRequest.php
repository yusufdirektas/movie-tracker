<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Film güncelleme isteğinin doğrulama kuralları.
 *
 * İki farklı güncelleme senaryosunu tek sınıfta yönetiyoruz:
 *  1. Kişisel puan güncelleme (personal_rating gönderildiğinde)
 *  2. İzlendi/izlenmedi toggle (personal_rating gönderilmediğinde)
 */
class UpdateMovieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 'sometimes' → Sadece o alan gönderildiyse kontrol et.
     * Bu sayede aynı endpoint iki farklı iş için kullanılabiliyor.
     */
    public function rules(): array
    {
        return [
            'personal_rating' => ['sometimes', 'required', 'integer', 'min:1', 'max:5'],
            'personal_note' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'personal_rating.integer' => 'Puan bir tam sayı olmalıdır.',
            'personal_rating.min'     => 'Puan en az 1 olmalıdır.',
            'personal_rating.max'     => 'Puan en fazla 5 olabilir.',
            'personal_note.max'       => 'Kişisel not en fazla 1000 karakter olabilir.',
        ];
    }
}
