<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MasterDataChangeUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'requirement_id' => 'sometimes|nullable|integer|exists:requirements,id',
            'object_name' => 'sometimes|required|string|max:255',
            'field_name' => 'sometimes|required|string|max:255',
            'change_type' => 'sometimes|required|string|max:100',
            'data_type' => 'sometimes|nullable|string|max:100',
            'description' => 'sometimes|nullable|string',
            'implemented_by' => 'sometimes|nullable|string|max:255',
            'implemented_at' => 'sometimes|nullable|date',
            'version_tag' => 'sometimes|nullable|string|max:100',
        ];
    }
}
