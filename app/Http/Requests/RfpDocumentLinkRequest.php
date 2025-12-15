<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RfpDocumentLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'drive_file_id' => 'required|string',
        ];
    }
}
