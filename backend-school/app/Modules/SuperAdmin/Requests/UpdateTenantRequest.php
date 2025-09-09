<?php

namespace App\Modules\SuperAdmin\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
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
        $tenantId = $this->route('tenant')->id;

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('tenants', 'name')->ignore($tenantId)
            ],
            'domain' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('tenants', 'domain')->ignore($tenantId)
            ],
            'status' => 'sometimes|required|in:pending,active,suspended,cancelled',
            'subscription_status' => 'sometimes|required|in:active,expired,cancelled,suspended',
            'contact_person' => 'sometimes|required|string|max:255',
            'contact_email' => 'sometimes|required|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'billing_address' => 'nullable|string|max:500',
            'subscription_plan_id' => 'nullable|exists:subscription_plans,id',
            'users_limit' => 'nullable|integer|min:1|max:10000',
            'storage_limit_gb' => 'nullable|integer|min:1|max:1000',
            'is_trial' => 'boolean',
            'trial_expires_at' => 'nullable|date|after:now',
            'subscription_expires_at' => 'nullable|date',
            'features_enabled' => 'nullable|array',
            'features_enabled.*' => 'string|in:students,teachers,classes,subjects,attendance,exams,fees,library,transport,hr'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tenant name is required.',
            'name.unique' => 'A tenant with this name already exists.',
            'domain.unique' => 'A tenant with this domain already exists.',
            'status.in' => 'Invalid status selected.',
            'subscription_status.in' => 'Invalid subscription status selected.',
            'contact_person.required' => 'Contact person name is required.',
            'contact_email.required' => 'Contact email is required.',
            'contact_email.email' => 'Please provide a valid email address.',
            'subscription_plan_id.exists' => 'The selected subscription plan does not exist.',
            'users_limit.min' => 'Users limit must be at least 1.',
            'users_limit.max' => 'Users limit cannot exceed 10,000.',
            'storage_limit_gb.min' => 'Storage limit must be at least 1 GB.',
            'storage_limit_gb.max' => 'Storage limit cannot exceed 1,000 GB.',
            'trial_expires_at.after' => 'Trial expiration date must be in the future.',
            'features_enabled.*.in' => 'Invalid feature selected.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'contact_person' => 'contact person',
            'contact_email' => 'contact email',
            'contact_phone' => 'contact phone',
            'billing_address' => 'billing address',
            'subscription_plan_id' => 'subscription plan',
            'subscription_status' => 'subscription status',
            'users_limit' => 'users limit',
            'storage_limit_gb' => 'storage limit',
            'trial_expires_at' => 'trial expiration date',
            'subscription_expires_at' => 'subscription expiration date',
            'features_enabled' => 'features'
        ];
    }
}