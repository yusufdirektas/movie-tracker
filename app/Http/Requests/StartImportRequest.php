<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'raw_text' => ['required', 'string', 'max:60000'],
            'is_watched' => ['sometimes', 'boolean'],
        ];
    }
}

