<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendSmsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'phone_number' => ['required', 'string', 'regex:/^\+?[1-9]\d{1,14}$/'],
            'message' => ['required', 'string', 'max:160'],
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'phone_number.regex' => 'Phone number must be in E.164 format (e.g., +1234567890)',
            'message.max' => 'SMS message cannot exceed 160 characters',
        ];
    }
}
