<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'assignee_user_id' => 'nullable|exists:users,id',
            'status' => 'sometimes|string|in:TODO,IN_PROGRESS,BLOCKED,DONE',
            'priority' => 'sometimes|string|in:low,medium,high,LOW,MEDIUM,HIGH',
            'due_date' => 'nullable|date',
            'blocker_reason' => 'nullable|string',
        ];
    }
}
