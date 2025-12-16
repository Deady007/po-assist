<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectModuleUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'module_name' => 'sometimes|nullable|string|max:255',
            'order_no' => 'sometimes|integer|min:1',
            'status' => 'sometimes|string|in:NOT_STARTED,IN_PROGRESS,BLOCKED,DONE',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'owner_user_id' => 'nullable|exists:users,id',
            'blocker_reason' => 'nullable|string',
        ];
    }
}
