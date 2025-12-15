<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestCaseStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'requirement_id' => 'nullable|integer|exists:requirements,id',
            'title' => 'required|string|max:255',
            'steps' => 'required|string',
            'expected_result' => 'required|string',
            'created_from' => 'nullable|string|max:100',
        ];
    }
}
