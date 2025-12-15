<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TokenRequestStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|string|max:100',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'tokens_estimated' => 'required|integer|min:0',
            'status' => 'required|string|max:50',
        ];
    }
}
