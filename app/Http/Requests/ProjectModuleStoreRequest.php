<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectModuleStoreRequest extends FormRequest
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
            'order_no' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'owner_user_id' => 'nullable|exists:users,id',
        ];
    }
}
