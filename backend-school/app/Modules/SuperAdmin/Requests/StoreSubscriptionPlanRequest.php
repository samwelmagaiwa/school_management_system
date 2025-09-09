<?php

namespace App\Modules\SuperAdmin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'SuperAdmin';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:subscription_plans,name',
            'description' => 'nullable|string|max:1000',
            'price_monthly' => 'required|numeric|min:0|max:999999.99',
            'price_yearly' => 'nullable|numeric|min:0|max:999999.99',
            'currency' => 'nullable|string|size:3|in:USD,EUR,GBP,CAD,AUD',
            'billing_cycle' => 'nullable|in:monthly,yearly,both',
            'trial_days' => 'nullable|integer|min:0|max:365',
            'features' => 'nullable|array',
            'features.*' => 'string|max:255',
            'limits' => 'nullable|array',
            'limits.users' => 'nullable|integer|min:1|max:100000',
            'limits.storage_gb' => 'nullable|integer|min:1|max:10000',
            'limits.schools' => 'nullable|integer|min:1|max:1000',
            'status' => 'nullable|in:active,inactive,draft',
            'type' => 'nullable|in:free,basic,standard,premium,enterprise',
            'sort_order' => 'nullable|integer|min:0|max:999',
            'is_popular' => 'boolean',
            'is_featured' => 'boolean',
            'metadata' => 'nullable|array'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Plan name is required.',
            'name.unique' => 'A subscription plan with this name already exists.',
            'price_monthly.required' => 'Monthly price is required.',
            'price_monthly.numeric' => 'Monthly price must be a valid number.',
            'price_monthly.min' => 'Monthly price cannot be negative.',
            'price_yearly.numeric' => 'Yearly price must be a valid number.',
            'currency.size' => 'Currency must be a 3-letter code (e.g., USD).',
            'currency.in' => 'Invalid currency. Supported currencies: USD, EUR, GBP, CAD, AUD.',
            'billing_cycle.in' => 'Invalid billing cycle. Must be monthly, yearly, or both.',
            'trial_days.integer' => 'Trial days must be a whole number.',
            'trial_days.max' => 'Trial period cannot exceed 365 days.',
            'features.array' => 'Features must be provided as a list.',
            'limits.array' => 'Limits must be provided as an object.',
            'status.in' => 'Invalid status. Must be active, inactive, or draft.',
            'type.in' => 'Invalid plan type. Must be free, basic, standard, premium, or enterprise.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'price_monthly' => 'monthly price',
            'price_yearly' => 'yearly price',
            'billing_cycle' => 'billing cycle',
            'trial_days' => 'trial days',
            'sort_order' => 'sort order',
            'is_popular' => 'popular flag',
            'is_featured' => 'featured flag'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default yearly price if not provided (10 months price = 2 months free)
        if ($this->has('price_monthly') && !$this->has('price_yearly')) {
            $this->merge([
                'price_yearly' => $this->price_monthly * 10
            ]);
        }

        // Set default currency
        if (!$this->has('currency')) {
            $this->merge(['currency' => 'USD']);
        }

        // Set default billing cycle
        if (!$this->has('billing_cycle')) {
            $this->merge(['billing_cycle' => 'monthly']);
        }

        // Set default status
        if (!$this->has('status')) {
            $this->merge(['status' => 'active']);
        }

        // Set default type
        if (!$this->has('type')) {
            $this->merge(['type' => 'standard']);
        }
    }
}