<?php

namespace App\Modules\SuperAdmin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenantRequest extends FormRequest
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
            'name' => 'required|string|max:255|unique:tenants,name',
            'domain' => 'nullable|string|max:255|unique:tenants,domain',
            'contact_person' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'billing_address' => 'nullable|string|max:500',
            'subscription_plan_id' => 'nullable|exists:subscription_plans,id',
            'users_limit' => 'nullable|integer|min:1|max:10000',
            'storage_limit_gb' => 'nullable|integer|min:1|max:1000',
            'is_trial' => 'boolean',
            'trial_days' => 'nullable|integer|min:1|max:90',
            'features_enabled' => 'nullable|array',
            'features_enabled.*' => 'string|in:students,teachers,classes,subjects,attendance,exams,fees,library,transport,hr',
            'create_admin_user' => 'boolean',
            'admin_first_name' => 'required_if:create_admin_user,true|string|max:255',
            'admin_last_name' => 'required_if:create_admin_user,true|string|max:255',
            'admin_email' => 'required_if:create_admin_user,true|email|max:255|unique:users,email',
            'admin_password' => 'required_if:create_admin_user,true|string|min:8|max:255'
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
            'contact_person.required' => 'Contact person name is required.',
            'contact_email.required' => 'Contact email is required.',
            'contact_email.email' => 'Please provide a valid email address.',
            'subscription_plan_id.exists' => 'The selected subscription plan does not exist.',
            'users_limit.min' => 'Users limit must be at least 1.',
            'users_limit.max' => 'Users limit cannot exceed 10,000.',
            'storage_limit_gb.min' => 'Storage limit must be at least 1 GB.',
            'storage_limit_gb.max' => 'Storage limit cannot exceed 1,000 GB.',
            'trial_days.min' => 'Trial period must be at least 1 day.',
            'trial_days.max' => 'Trial period cannot exceed 90 days.',
            'features_enabled.*.in' => 'Invalid feature selected.',
            'admin_email.unique' => 'A user with this email already exists.',
            'admin_password.min' => 'Admin password must be at least 8 characters.'
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
            'users_limit' => 'users limit',
            'storage_limit_gb' => 'storage limit',
            'trial_days' => 'trial days',
            'features_enabled' => 'features',
            'admin_first_name' => 'admin first name',
            'admin_last_name' => 'admin last name',
            'admin_email' => 'admin email',
            'admin_password' => 'admin password'
        ];
    }
}