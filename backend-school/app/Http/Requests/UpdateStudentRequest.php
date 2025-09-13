<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models$1;

class UpdateStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('student'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $student = $this->route('student');

        return [
            // For updates, most fields become optional
            'user_id' => 'sometimes|exists:users,id',
            'school_id' => 'sometimes|exists:schools,id',
            'student_id' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('students', 'student_id')->ignore($student->id)
            ],
            'admission_number' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('students', 'admission_number')->ignore($student->id)
            ],
            'admission_type' => 'sometimes|in:new,transfer,readmission',
            'class_id' => 'sometimes|exists:classes,id',
            'section' => 'sometimes|string|max:10',
            'roll_number' => 'sometimes|integer|min:1',
            'admission_date' => 'sometimes|date|before_or_equal:today',
            
            // Personal Information
            'date_of_birth' => 'sometimes|date|before:today',
            'gender' => 'sometimes|in:male,female,other',
            'blood_group' => 'sometimes|string|max:5',
            'nationality' => 'sometimes|string|max:50',
            'religion' => 'sometimes|string|max:50',
            'caste' => 'sometimes|string|max:50',
            'category' => 'sometimes|in:General,OBC,SC,ST',
            'address' => 'sometimes|string',
            'city' => 'sometimes|string|max:100',
            'state' => 'sometimes|string|max:100',
            'postal_code' => 'sometimes|string|max:10',
            'phone' => 'sometimes|string|max:20',
            
            // Parent/Guardian Information
            'father_name' => 'sometimes|string|max:255',
            'father_occupation' => 'sometimes|string|max:255',
            'father_phone' => 'sometimes|string|max:20',
            'father_email' => 'sometimes|email|max:255',
            'mother_name' => 'sometimes|string|max:255',
            'mother_occupation' => 'sometimes|string|max:255',
            'mother_phone' => 'sometimes|string|max:20',
            'mother_email' => 'sometimes|email|max:255',
            'guardian_name' => 'sometimes|string|max:255',
            'guardian_relation' => 'sometimes|string|max:50',
            'guardian_phone' => 'sometimes|string|max:20',
            'guardian_email' => 'sometimes|email|max:255',
            
            // Emergency Contact
            'emergency_contact_name' => 'sometimes|string|max:255',
            'emergency_contact_phone' => 'sometimes|string|max:20',
            'emergency_contact_relation' => 'sometimes|string|max:50',
            
            // Academic Information
            'previous_school' => 'sometimes|string|max:255',
            'previous_class' => 'sometimes|string|max:50',
            'previous_percentage' => 'sometimes|numeric|min:0|max:100',
            'medical_conditions' => 'sometimes|string',
            'allergies' => 'sometimes|string',
            'special_needs' => 'sometimes|string',
            
            // Transport Information
            'uses_transport' => 'sometimes|boolean',
            'vehicle_id' => 'sometimes|exists:vehicles,id',
            'pickup_point' => 'sometimes|string|max:255',
            'drop_point' => 'sometimes|string|max:255',
            
            // Status
            'status' => 'sometimes|in:active,inactive,transferred,graduated,dropped',
            'status_date' => 'sometimes|date',
            'status_reason' => 'sometimes|string',
            
            // User data for updating user account
            'user_data' => 'sometimes|array',
            'user_data.first_name' => 'sometimes|string|max:255',
            'user_data.last_name' => 'sometimes|string|max:255',
            'user_data.email' => [
                'sometimes',
                'email',
                Rule::unique('users', 'email')->ignore($student->user_id ?? null)
            ],
            'user_data.password' => 'sometimes|string|min:8'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'user_id.exists' => 'Selected user does not exist',
            'school_id.exists' => 'Selected school does not exist',
            'student_id.unique' => 'Student ID already exists',
            'admission_number.unique' => 'Admission number already exists',
            'admission_date.before_or_equal' => 'Admission date cannot be in the future',
            'date_of_birth.before' => 'Date of birth must be in the past',
            'user_data.email.unique' => 'Email already exists',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $data = [];
        
        // Handle boolean fields
        if ($this->has('uses_transport')) {
            $data['uses_transport'] = $this->boolean('uses_transport');
        }

        // Prevent non-SuperAdmin users from changing school_id
        if (!$this->user()->isSuperAdmin() && $this->has('school_id')) {
            $data['school_id'] = $this->user()->school_id;
        }
        
        if (!empty($data)) {
            $this->merge($data);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $student = $this->route('student');

            // Validate that the user belongs to the same school (for non-SuperAdmin)
            if (!$this->user()->isSuperAdmin()) {
                $user = \App\Modules\User\Models\User::find($this->user_id);
                if ($user && $user->school_id !== $this->user()->school_id) {
                    $validator->errors()->add('user_id', 'User must belong to your school');
                }
            }

            // Validate that roll number is unique within class and section
            if ($this->filled(['class_id', 'section', 'roll_number'])) {
                $exists = Student::where('class_id', $this->class_id)
                    ->where('section', $this->section)
                    ->where('roll_number', $this->roll_number)
                    ->where('id', '!=', $student->id)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('roll_number', 'Roll number already exists in this class and section');
                }
            }
        });
    }
}