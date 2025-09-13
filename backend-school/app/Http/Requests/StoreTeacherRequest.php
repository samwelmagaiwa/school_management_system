<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // User details
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20|unique:users,phone',
            'password' => 'required|string|min:8|confirmed',
            'gender' => 'required|in:male,female,other',
            'date_of_birth' => 'required|date|before:today',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            
            // Teacher-specific details
            'school_id' => 'required|exists:schools,id',
            'employee_id' => 'required|string|max:50|unique:teachers,employee_id',
            'department_id' => 'nullable|exists:departments,id',
            'designation' => 'required|string|max:100',
            'joining_date' => 'required|date|before_or_equal:today',
            'qualification' => 'required|string|max:255',
            'experience_years' => 'nullable|integer|min:0|max:50',
            'specialization' => 'nullable|string|max:255',
            'salary' => 'nullable|numeric|min:0',
            'status' => 'boolean',
            'subjects' => 'nullable|array',
            'subjects.*' => 'exists:subjects,id',
            'classes' => 'nullable|array',
            'classes.*' => 'exists:classes,id',
            
            // Emergency contact
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'emergency_contact_relation' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'email.required' => 'Email address is required.',
            'email.unique' => 'This email address is already registered.',
            'phone.required' => 'Phone number is required.',
            'phone.unique' => 'This phone number is already registered.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'gender.required' => 'Gender is required.',
            'date_of_birth.required' => 'Date of birth is required.',
            'date_of_birth.before' => 'Date of birth must be before today.',
            'school_id.required' => 'School is required.',
            'school_id.exists' => 'Selected school does not exist.',
            'employee_id.required' => 'Employee ID is required.',
            'employee_id.unique' => 'This employee ID is already taken.',
            'designation.required' => 'Designation is required.',
            'joining_date.required' => 'Joining date is required.',
            'joining_date.before_or_equal' => 'Joining date cannot be in the future.',
            'qualification.required' => 'Qualification is required.',
            'experience_years.integer' => 'Experience years must be a number.',
            'experience_years.max' => 'Experience years cannot exceed 50.',
            'salary.numeric' => 'Salary must be a number.',
            'salary.min' => 'Salary cannot be negative.',
            'subjects.*.exists' => 'Selected subject does not exist.',
            'classes.*.exists' => 'Selected class does not exist.',
        ];
    }

    protected function prepareForValidation()
    {
        if (!$this->has('status')) {
            $this->merge(['status' => true]);
        }

        if (!$this->has('country')) {
            $this->merge(['country' => 'India']);
        }
    }
}
