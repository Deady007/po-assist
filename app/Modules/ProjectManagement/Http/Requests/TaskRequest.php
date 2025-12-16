<?php

namespace App\Modules\ProjectManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $titleRule = $this->isMethod('post') ? 'required|string|max:255' : 'sometimes|required|string|max:255';

        return [
            'title' => $titleRule,
            'description' => 'nullable|string',
            'assignee_user_id' => 'nullable|exists:users,id',
            'status' => 'sometimes|in:TODO,IN_PROGRESS,BLOCKED,DONE',
            'due_date' => 'nullable|date',
            'blocker_reason' => 'nullable|string',
            'priority' => 'nullable|in:LOW,MEDIUM,HIGH',
        ];
    }
}
