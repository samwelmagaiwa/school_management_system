<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuickAddStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return in_array(auth()->user()->role, ['SuperAdmin', 'Admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $schoolId = auth()->user()->isSuperAdmin() ? $this->school_id : auth()->user()->school_id;

        return [
            // Required fields
            'first_name' => 'required|string|max:50|regex:/^[a-zA-Z\s]+$/',
            'last_name' => 'required|string|max:50|regex:/^[a-zA-Z\s]+$/',
            'email' => [
                'required',
                'email',
                'max:100',
                Rule::unique('users', 'email')
            ],
            'school_id' => [
                'required_if:role,SuperAdmin',
                'exists:schools,id'
            ],
            'class_id' => [
                'required',
                'exists:classes,id',
                function ($attribute, $value, $fail) use ($schoolId) {
                    if ($schoolId) {
                        $class = \App\Modules\Class\Models\SchoolClass::find($value);
                        if ($class && $class->school_id != $schoolId) {
                            $fail('The selected class does not belong to the specified school.');
                        }
                    }
                }
            ],
            
            // Optional fields with validation
            'admission_number' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('students', 'admission_number')->where(function ($query) use ($schoolId) {
                    return $query->where('school_id', $schoolId);
                })
            ],
            'roll_number' => 'nullable|integer|min:1|max:999',
            'section' => 'nullable|string|max:10|in:A,B,C,D,E',
            'date_of_birth' => 'nullable|date|before:today|after:' . now()->subYears(25)->toDateString(),
            'gender' => 'nullable|in:male,female,other',
            'blood_group' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'phone' => 'nullable|string|max:15|regex:/^[0-9+\-\s()]+$/',
            'address' => 'nullable|string|max:500',
            'admission_date' => 'nullable|date|before_or_equal:today',
            
            // Parent information
            'parent_name' => 'nullable|string|max:100|regex:/^[a-zA-Z\s]+$/',
            'parent_phone' => 'nullable|string|max:15|regex:/^[0-9+\-\s()]+$/',
            'parent_email' => 'nullable|email|max:100',
            
            // Optional password (will use default if not provided)
            'password' => 'nullable|string|min:6|max:20',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'first_name.regex' => 'First name should only contain letters and spaces.',
            'last_name.required' => 'Last name is required.',
            'last_name.regex' => 'Last name should only contain letters and spaces.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'school_id.required_if' => 'School selection is required.',
            'school_id.exists' => 'Selected school does not exist.',
            'class_id.required' => 'Class selection is required.',
            'class_id.exists' => 'Selected class does not exist.',
            'admission_number.unique' => 'This admission number already exists.',
            'roll_number.integer' => 'Roll number must be a valid number.',
            'roll_number.min' => 'Roll number must be at least 1.',
            'roll_number.max' => 'Roll number cannot exceed 999.',
            'section.in' => 'Section must be one of: A, B, C, D, E.',
            'date_of_birth.date' => 'Please enter a valid date of birth.',
            'date_of_birth.before' => 'Date of birth must be before today.',
            'date_of_birth.after' => 'Student must be under 25 years old.',
            'gender.in' => 'Gender must be male, female, or other.',
            'blood_group.in' => 'Please select a valid blood group.',
            'phone.regex' => 'Please enter a valid phone number.',
            'parent_phone.regex' => 'Please enter a valid parent phone number.',
            'parent_email.email' => 'Please enter a valid parent email address.',
            'admission_date.before_or_equal' => 'Admission date cannot be in the future.',
            'password.min' => 'Password must be at least 6 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set school_id for non-SuperAdmin users
        if (!auth()->user()->isSuperAdmin()) {
            $this->merge([
                'school_id' => auth()->user()->school_id
            ]);
        }

        // Set default values
        $this->merge([
            'section' => $this->section ?? 'A',
            'admission_date' => $this->admission_date ?? now()->toDateString(),
            'gender' => $this->gender ?? 'male',
        ]);

        // Clean phone numbers
        if ($this->phone) {
            $this->merge(['phone' => preg_replace('/[^0-9+\-\s()]/', '', $this->phone)]);
        }
        if ($this->parent_phone) {
            $this->merge(['parent_phone' => preg_replace('/[^0-9+\-\s()]/', '', $this->parent_phone)]);
        }
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'email' => 'email address',
            'school_id' => 'school',
            'class_id' => 'class',
            'admission_number' => 'admission number',
            'roll_number' => 'roll number',
            'section' => 'section',
            'date_of_birth' => 'date of birth',
            'gender' => 'gender',
            'blood_group' => 'blood group',
            'phone' => 'phone number',
            'address' => 'address',
            'admission_date' => 'admission date',
            'parent_name' => 'parent name',
            'parent_phone' => 'parent phone',
            'parent_email' => 'parent email',
            'password' => 'password',
        ];
    }
}