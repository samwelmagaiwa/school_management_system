<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudentRequest extends FormRequest
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
        $studentId = $this->route('student') ? $this->route('student')->id : null;

        return [
            'user_id' => 'required|exists:users,id',
            'school_id' => 'required|exists:schools,id',
            'class_id' => 'required|exists:classes,id',
            'student_id' => 'required|string|max:50|unique:students,student_id,' . $studentId,
            'roll_number' => 'required|string|max:20',
            'admission_date' => 'required|date',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:male,female,other',
            'blood_group' => 'nullable|string|max:5',
            'address' => 'required|string|max:500',
            'parent_name' => 'required|string|max:255',
            'parent_phone' => 'required|string|max:20',
            'parent_email' => 'nullable|email|max:255',
            'emergency_contact' => 'required|string|max:20',
            'medical_conditions' => 'nullable|string|max:1000',
            'academic_year' => 'required|string|max:20',
            'is_active' => 'boolean',
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
            'class_id.required' => 'Class is required',
            'class_id.exists' => 'Selected class does not exist',
            'student_id.required' => 'Student ID is required',
            'student_id.unique' => 'This student ID is already taken',
            'roll_number.required' => 'Roll number is required',
            'admission_date.required' => 'Admission date is required',
            'date_of_birth.required' => 'Date of birth is required',
            'date_of_birth.before' => 'Date of birth must be before today',
            'gender.required' => 'Gender is required',
            'gender.in' => 'Gender must be male, female, or other',
            'address.required' => 'Address is required',
            'parent_name.required' => 'Parent name is required',
            'parent_phone.required' => 'Parent phone number is required',
            'emergency_contact.required' => 'Emergency contact is required',
            'academic_year.required' => 'Academic year is required',
        ];
    }
}