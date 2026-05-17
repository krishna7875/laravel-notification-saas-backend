<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // TODO: Add admin authorization check
        return true;
    }

    /**
     * Validation rules for creating a subscription plan.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:subscription_plans,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
            'monthly_price' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'yearly_price' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:255'],
            'status' => ['boolean'],
        ];
    }
}
