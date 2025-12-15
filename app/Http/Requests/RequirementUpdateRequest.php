<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequirementUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'source_type' => 'sometimes|nullable|string|max:100',
            'source_ref_type' => 'sometimes|nullable|string|max:100',
            'source_ref_id' => 'sometimes|nullable|integer',
            'priority' => 'sometimes|required|string|max:50',
            'status' => 'sometimes|required|string|max:50',
            'is_change_request' => 'sometimes|boolean',
            'approved_at' => 'sometimes|nullable|date',
            'delivered_at' => 'sometimes|nullable|date',
        ];
    }
}
