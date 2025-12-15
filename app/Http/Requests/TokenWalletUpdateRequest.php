<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TokenWalletUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'total_tokens' => 'required|integer|min:0',
            'used_tokens' => 'nullable|integer|min:0',
        ];
    }
}
