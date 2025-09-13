<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models$1;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $user = $this->route('user');
        $availableRoles = User::ROLES;

        // Non-SuperAdmin users cannot create SuperAdmin users
        if (!$this->user()->isSuperAdmin()) {
            $availableRoles = array_filter($availableRoles, fn($role) => $role !== 'SuperAdmin');
        }

        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id)
            ],
            'password' => 'nullable|string|min:6',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string|max:500',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:Male,Female,Other',
            'role' => ['required', Rule::in($availableRoles)],
            'school_id' => [
                'required_if:role,Admin,Teacher,Student,Parent',
                'nullable',
                'exists:schools,id'
            ],
            'profile_picture' => 'nullable|string|max:255',
            'status' => 'boolean'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required',
            'last_name.required' => 'Last name is required',
            'email.unique' => 'Email address already exists',
            'date_of_birth.before' => 'Date of birth must be before today',
            'school_id.required_if' => 'School is required for this role',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('status')) {
            $this->merge([
                'status' => $this->boolean('status'),
            ]);
        }

        // Prevent non-SuperAdmin users from changing school_id
        if (!$this->user()->isSuperAdmin() && $this->has('school_id')) {
            $this->merge([
                'school_id' => $this->user()->school_id,
            ]);
        }
    }
}