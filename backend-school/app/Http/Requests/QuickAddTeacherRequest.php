<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuickAddTeacherRequest extends FormRequest
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
            'subject_ids' => 'required|array|min:1',
            'subject_ids.*' => [
                'exists:subjects,id',
                function ($attribute, $value, $fail) use ($schoolId) {
                    if ($schoolId) {
                        $subject = \App\Modules\Subject\Models\Subject::find($value);
                        if ($subject && $subject->school_id != $schoolId) {
                            $fail('The selected subject does not belong to the specified school.');
                        }
                    }
                }
            ],
            
            // Optional fields with validation
            'employee_id' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('teachers', 'employee_id')->where(function ($query) use ($schoolId) {
                    return $query->where('school_id', $schoolId);
                })
            ],
            'date_of_birth' => 'nullable|date|before:today|after:' . now()->subYears(65)->toDateString(),
            'gender' => 'nullable|in:male,female,other',
            'phone' => 'nullable|string|max:15|regex:/^[0-9+\-\s()]+$/',
            'address' => 'nullable|string|max:500',
            'qualification' => 'nullable|string|max:100',
            'experience_years' => 'nullable|integer|min:0|max:50',
            'employment_type' => 'nullable|in:full_time,part_time,contract,substitute',
            'salary' => 'nullable|numeric|min:0|max:999999.99',
            'joining_date' => 'nullable|date|before_or_equal:today',
            
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
            'subject_ids.required' => 'At least one subject must be selected.',
            'subject_ids.array' => 'Subjects must be provided as a list.',
            'subject_ids.min' => 'At least one subject must be selected.',
            'subject_ids.*.exists' => 'One or more selected subjects do not exist.',
            'employee_id.unique' => 'This employee ID already exists.',
            'date_of_birth.date' => 'Please enter a valid date of birth.',
            'date_of_birth.before' => 'Date of birth must be before today.',
            'date_of_birth.after' => 'Teacher must be under 65 years old.',
            'gender.in' => 'Gender must be male, female, or other.',
            'phone.regex' => 'Please enter a valid phone number.',
            'qualification.max' => 'Qualification cannot exceed 100 characters.',
            'experience_years.integer' => 'Experience years must be a valid number.',
            'experience_years.min' => 'Experience years cannot be negative.',
            'experience_years.max' => 'Experience years cannot exceed 50.',
            'employment_type.in' => 'Employment type must be full_time, part_time, contract, or substitute.',
            'salary.numeric' => 'Salary must be a valid number.',
            'salary.min' => 'Salary cannot be negative.',
            'salary.max' => 'Salary cannot exceed 999,999.99.',
            'joining_date.before_or_equal' => 'Joining date cannot be in the future.',
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
            'joining_date' => $this->joining_date ?? now()->toDateString(),
            'gender' => $this->gender ?? 'male',
            'employment_type' => $this->employment_type ?? 'full_time',
            'experience_years' => $this->experience_years ?? 0,
        ]);

        // Clean phone number
        if ($this->phone) {
            $this->merge(['phone' => preg_replace('/[^0-9+\-\s()]/', '', $this->phone)]);
        }

        // Ensure subject_ids is an array
        if ($this->subject_ids && !is_array($this->subject_ids)) {
            $this->merge(['subject_ids' => [$this->subject_ids]]);
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
            'subject_ids' => 'subjects',
            'employee_id' => 'employee ID',
            'date_of_birth' => 'date of birth',
            'gender' => 'gender',
            'phone' => 'phone number',
            'address' => 'address',
            'qualification' => 'qualification',
            'experience_years' => 'experience years',
            'employment_type' => 'employment type',
            'salary' => 'salary',
            'joining_date' => 'joining date',
            'password' => 'password',
        ];
    }
}