<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BugUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'severity' => 'sometimes|required|string|max:50',
            'status' => 'sometimes|required|string|max:50',
            'reported_by' => 'sometimes|nullable|string|max:255',
            'assigned_to_developer_id' => 'sometimes|nullable|integer|exists:developers,id',
            'resolved_by_developer_id' => 'sometimes|nullable|integer|exists:developers,id',
            'fixed_at' => 'sometimes|nullable|date',
            'closed_at' => 'sometimes|nullable|date',
        ];
    }
}
