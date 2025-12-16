<?php

namespace App\Modules\ProjectManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'module_name' => 'nullable|string|max:255',
            'status' => 'sometimes|in:NOT_STARTED,IN_PROGRESS,BLOCKED,DONE',
            'order_no' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'owner_user_id' => 'nullable|exists:users,id',
            'blocker_reason' => 'nullable|string',
        ];
    }
}
