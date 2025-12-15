<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DataItemUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'category' => 'sometimes|required|string|max:255',
            'expected_format' => 'sometimes|nullable|string|max:255',
            'owner' => 'sometimes|required|string|max:255',
            'due_date' => 'sometimes|nullable|date',
            'status' => 'sometimes|required|string|max:50',
            'notes' => 'sometimes|nullable|string',
        ];
    }
}
