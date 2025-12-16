<?php

namespace App\Modules\ProjectManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_code' => 'sometimes|nullable|string|max:50|unique:projects,project_code',
            'name' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'description' => 'nullable|string',
            'status_id' => 'nullable|exists:project_statuses,id',
            'start_date' => 'nullable|date',
            'due_date' => 'required|date|after_or_equal:start_date',
            'priority' => 'required|in:low,medium,high',
            'owner_user_id' => 'nullable|exists:users,id',
            'is_active' => 'sometimes|boolean',
            'team_members' => 'sometimes|array',
            'team_members.*.user_id' => 'required_with:team_members|exists:users,id',
            'team_members.*.role_in_project' => 'nullable|string|max:255',
        ];
    }
}
