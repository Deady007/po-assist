<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequirementAssignmentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'sometimes|required|string|max:50',
            'eta_date' => 'sometimes|nullable|date',
            'notes' => 'sometimes|nullable|string',
        ];
    }
}
