<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DataItemStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'expected_format' => 'nullable|string|max:255',
            'owner' => 'required|string|max:255',
            'due_date' => 'nullable|date',
            'status' => 'required|string|max:50',
            'notes' => 'nullable|string',
        ];
    }
}
