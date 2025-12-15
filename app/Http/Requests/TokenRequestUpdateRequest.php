<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TokenRequestUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'sometimes|required|string|max:100',
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'tokens_estimated' => 'sometimes|required|integer|min:0',
            'status' => 'sometimes|required|string|max:50',
            'approved_at' => 'sometimes|nullable|date',
            'done_at' => 'sometimes|nullable|date',
        ];
    }
}
