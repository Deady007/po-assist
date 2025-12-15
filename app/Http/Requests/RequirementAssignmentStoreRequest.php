<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequirementAssignmentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'requirement_id' => 'required|integer|exists:requirements,id',
            'developer_id' => 'required|integer|exists:developers,id',
            'status' => 'required|string|max:50',
            'eta_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ];
    }
}
