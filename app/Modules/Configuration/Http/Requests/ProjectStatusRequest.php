<?php

namespace App\Modules\Configuration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $statusId = $this->route('status');

        return [
            'name' => 'required|string|max:255|unique:project_statuses,name' . ($statusId ? ',' . $statusId : ''),
            'order_no' => 'required|integer|min:1',
            'is_default' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
