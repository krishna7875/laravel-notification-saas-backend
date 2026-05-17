<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionPlanRequest extends FormRequest
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
     * Validation rules for updating a subscription plan.
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'max:255', 'unique:subscription_plans,slug,' . $this->route('subscriptionPlan')],
            'description' => ['nullable', 'string', 'max:1000'],
            'monthly_price' => ['sometimes', 'required', 'numeric', 'min:0', 'max:99999.99'],
            'yearly_price' => ['sometimes', 'required', 'numeric', 'min:0', 'max:99999.99'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:255'],
            'status' => ['boolean'],
        ];
    }
}
