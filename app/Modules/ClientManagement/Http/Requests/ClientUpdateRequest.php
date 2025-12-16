<?php

namespace App\Modules\ClientManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $clientId = $this->route('client');

        return [
            'client_code' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
                Rule::unique('clients', 'client_code')->ignore($clientId),
            ],
            'name' => 'required|string|max:255',
            'industry' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
            'contact_person_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:30',
            'billing_address' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
