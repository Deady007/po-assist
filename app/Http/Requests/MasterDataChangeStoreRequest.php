<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MasterDataChangeStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'requirement_id' => 'nullable|integer|exists:requirements,id',
            'object_name' => 'required|string|max:255',
            'field_name' => 'required|string|max:255',
            'change_type' => 'required|string|max:100',
            'data_type' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'implemented_by' => 'nullable|string|max:255',
            'implemented_at' => 'nullable|date',
            'version_tag' => 'nullable|string|max:100',
        ];
    }
}
