<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BugStoreRequest extends FormRequest
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
            'description' => 'nullable|string',
            'severity' => 'required|string|max:50',
            'status' => 'required|string|max:50',
            'reported_by' => 'nullable|string|max:255',
            'assigned_to_developer_id' => 'nullable|integer|exists:developers,id',
            'resolved_by_developer_id' => 'nullable|integer|exists:developers,id',
        ];
    }
}
