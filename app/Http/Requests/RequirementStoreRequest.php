<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequirementStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'source_type' => 'nullable|string|max:100',
            'source_ref_type' => 'nullable|string|max:100',
            'source_ref_id' => 'nullable|integer',
            'priority' => 'required|string|max:50',
            'status' => 'required|string|max:50',
        ];
    }
}
