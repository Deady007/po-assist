<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestCaseUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'requirement_id' => 'sometimes|nullable|integer|exists:requirements,id',
            'title' => 'sometimes|required|string|max:255',
            'steps' => 'sometimes|required|string',
            'expected_result' => 'sometimes|required|string',
            'created_from' => 'sometimes|nullable|string|max:100',
        ];
    }
}
