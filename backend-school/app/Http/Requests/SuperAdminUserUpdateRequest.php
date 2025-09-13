<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models$1;
use Illuminate\Validation\Rule;

class SuperAdminUserUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId)
            ],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'role' => 'sometimes|required|in:' . implode(',', User::ROLES),
            'school_id' => 'sometimes|required|exists:schools,id',
            'password' => 'nullable|string|min:6|confirmed',
            'status' => 'nullable|boolean',
            'profile_picture' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048|dimensions:min_width=50,min_height=50,max_width=2000,max_height=2000'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'role.required' => 'User role is required.',
            'role.in' => 'Please select a valid user role.',
            'school_id.required' => 'School assignment is required.',
            'school_id.exists' => 'Selected school does not exist.',
            'password.min' => 'Password must be at least 6 characters long.',
            'password.confirmed' => 'Password confirmation does not match.',
            'date_of_birth.before' => 'Date of birth must be before today.',
            'gender.in' => 'Please select a valid gender option.',
            'status.boolean' => 'The status field must be true or false.',
            'profile_picture.file' => 'Profile picture must be a file.',
            'profile_picture.image' => 'Profile picture must be an image file.',
            'profile_picture.mimes' => 'Profile picture must be a JPEG, PNG, JPG, GIF, SVG, or WebP file.',
            'profile_picture.max' => 'Profile picture must not exceed 2MB.',
            'profile_picture.dimensions' => 'Profile picture must be between 50x50 and 2000x2000 pixels.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'email' => 'email address',
            'phone' => 'phone number',
            'date_of_birth' => 'date of birth',
            'school_id' => 'school',
            'profile_picture' => 'profile picture'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Handle status field properly - convert to boolean
        if ($this->has('status')) {
            $status = $this->input('status');
            // Convert string boolean values to actual boolean
            if (is_string($status)) {
                $status = filter_var($status, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($status !== null) {
                    $this->merge(['status' => $status]);
                }
            }
        }

        // Clean phone number
        if ($this->has('phone')) {
            $this->merge([
                'phone' => preg_replace('/[^0-9+\-\s]/', '', $this->phone)
            ]);
        }

        // Normalize email
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower(trim($this->email))
            ]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->route('user');
            
            // Prevent changing role of SuperAdmin users
            if ($user->isSuperAdmin() && $this->has('role') && $this->role !== 'SuperAdmin') {
                $validator->errors()->add('role', 'Cannot change role of SuperAdmin users.');
            }

            // Prevent changing school of SuperAdmin users
            if ($user->isSuperAdmin() && $this->has('school_id')) {
                $validator->errors()->add('school_id', 'Cannot change school assignment of SuperAdmin users.');
            }
        });
    }
}