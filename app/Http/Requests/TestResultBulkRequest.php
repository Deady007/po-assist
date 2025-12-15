<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestResultBulkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'results' => 'required|array|min:1',
            'results.*.test_case_id' => 'required|integer|exists:test_cases,id',
            'results.*.status' => 'required|string|in:PASS,FAIL,BLOCKED',
            'results.*.remarks' => 'nullable|string',
            'results.*.create_bug' => 'nullable|boolean',
            'results.*.bug_severity' => 'nullable|string|max:50',
        ];
    }
}
