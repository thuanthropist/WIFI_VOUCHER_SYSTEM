<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitiatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone_number' => ['required', 'string', 'regex:/^(0|255)[67]\d{8}$/'],
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'site_id' => ['nullable', 'integer', 'exists:sites,id'],
        ];
    }
}
