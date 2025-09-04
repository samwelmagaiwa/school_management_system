<?php

namespace App\Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Modules\User\Models\User;

class CreateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->canCreateUsers();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $creatableRoles = $this->user()->getCreatableRoles();

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'string', Rule::in($creatableRoles)],
            'school_id' => [
                'nullable',
                'integer',
                'exists:schools,id',
                function ($attribute, $value, $fail) {
                    // School Admin can only assign users to their own school
                    if ($this->user()->isSchoolAdmin() && $value != $this->user()->school_id) {
                        $fail('You can only assign users to your own school.');
                    }
                },
            ],
            'profile_picture' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'string', Rule::in(['male', 'female', 'other'])],
            'address' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
            'password' => ['nullable', 'string', 'min:6'], // Optional, will use last_name if not provided
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
            'email.unique' => 'This email address is already taken.',
            'role.required' => 'User role is required.',
            'role.in' => 'You do not have permission to create users with this role.',
            'school_id.exists' => 'The selected school does not exist.',
            'profile_picture.image' => 'Profile picture must be an image.',
            'profile_picture.max' => 'Profile picture must not be larger than 2MB.',
            'date_of_birth.before' => 'Date of birth must be before today.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default status if not provided
        if (!$this->has('status')) {
            $this->merge(['status' => 'active']);
        }

        // For School Admin, automatically set school_id to their own school
        if ($this->user()->isSchoolAdmin() && !$this->has('school_id')) {
            $this->merge(['school_id' => $this->user()->school_id]);
        }
    }
}