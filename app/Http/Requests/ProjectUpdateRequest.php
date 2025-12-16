<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $projectId = $this->route('project');

        return [
            'project_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('projects', 'project_code')->ignore($projectId),
            ],
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status_id' => 'nullable|exists:project_statuses,id',
            'start_date' => 'nullable|date',
            'due_date' => 'required|date',
            'priority' => 'required|in:low,medium,high,LOW,MEDIUM,HIGH',
            'owner_user_id' => 'required|exists:users,id',
            'is_active' => 'sometimes|boolean',
            'team_members' => 'sometimes|array',
            'team_members.*.user_id' => 'required_with:team_members|exists:users,id',
            'team_members.*.role_in_project' => 'nullable|string|max:255',
        ];
    }
}
