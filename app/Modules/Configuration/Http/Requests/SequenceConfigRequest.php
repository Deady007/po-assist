<?php

namespace App\Modules\Configuration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SequenceConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sequenceId = $this->route('sequence');

        return [
            'model_name' => 'required|string|max:255|unique:sequence_configs,model_name' . ($sequenceId ? ',' . $sequenceId : ''),
            'prefix' => 'nullable|string|max:20',
            'padding' => 'required|integer|min:1|max:10',
            'start_from' => 'required|integer|min:1',
            'reset_policy' => 'required|in:none,yearly,monthly',
            'format_template' => 'nullable|string|max:255',
        ];
    }
}
