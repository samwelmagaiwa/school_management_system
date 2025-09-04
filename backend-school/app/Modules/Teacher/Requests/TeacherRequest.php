<?php

namespace App\Modules\Teacher\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TeacherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Add authorization logic as needed
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $teacherId = $this->route('teacher') ? $this->route('teacher')->id : null;

        return [
            'user_id' => 'required|exists:users,id',
            'school_id' => 'required|exists:schools,id',
            'employee_id' => 'required|string|max:50|unique:teachers,employee_id,' . $teacherId,
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:male,female,other',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
            'qualification' => 'required|string|max:255',
            'specialization' => 'required|string|max:255',
            'experience_years' => 'required|integer|min:0|max:50',
            'joining_date' => 'required|date',
            'salary' => 'required|numeric|min:0',
            'emergency_contact' => 'required|string|max:20',
            'is_active' => 'boolean',
            'subject_ids' => 'nullable|array',
            'subject_ids.*' => 'exists:subjects,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'User is required',
            'user_id.exists' => 'Selected user does not exist',
            'school_id.required' => 'School is required',
            'school_id.exists' => 'Selected school does not exist',
            'employee_id.required' => 'Employee ID is required',
            'employee_id.unique' => 'This employee ID is already taken',
            'date_of_birth.required' => 'Date of birth is required',
            'date_of_birth.before' => 'Date of birth must be before today',
            'gender.required' => 'Gender is required',
            'gender.in' => 'Gender must be male, female, or other',
            'phone.required' => 'Phone number is required',
            'address.required' => 'Address is required',
            'qualification.required' => 'Qualification is required',
            'specialization.required' => 'Specialization is required',
            'experience_years.required' => 'Experience years is required',
            'experience_years.min' => 'Experience years cannot be negative',
            'experience_years.max' => 'Experience years cannot exceed 50',
            'joining_date.required' => 'Joining date is required',
            'salary.required' => 'Salary is required',
            'salary.min' => 'Salary cannot be negative',
            'emergency_contact.required' => 'Emergency contact is required',
            'subject_ids.array' => 'Subject IDs must be an array',
            'subject_ids.*.exists' => 'One or more selected subjects do not exist',
        ];
    }
}