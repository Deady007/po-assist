<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestRunStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tester_id' => 'required|integer|exists:testers,id',
            'run_date' => 'required|date',
            'notes' => 'nullable|string',
        ];
    }
}
