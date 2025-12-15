<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delivery_date' => 'required|date',
            'delivered_requirements_json' => 'required|array',
            'signoff_notes' => 'nullable|string',
        ];
    }
}
