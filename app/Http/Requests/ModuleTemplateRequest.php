<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModuleTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('module_template');

        return [
            'key' => [
                'required',
                'string',
                'max:100',
                Rule::unique('module_templates', 'key')->ignore($id),
            ],
            'name' => 'required|string|max:255',
            'order_no' => 'required|integer|min:1',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
