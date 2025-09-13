<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models$1;

class StoreStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Student::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Either user_id or user_data is required
            'user_id' => 'nullable|exists:users,id|required_without:user_data',
            'school_id' => 'required|exists:schools,id',
            'student_id' => 'required|string|max:50|unique:students,student_id',
            'admission_number' => 'nullable|string|max:50|unique:students,admission_number',
            'admission_type' => 'required|in:new,transfer,readmission',
            'class_id' => 'nullable|exists:classes,id',
            'section' => 'nullable|string|max:10',
            'roll_number' => 'nullable|integer|min:1',
            'admission_date' => 'required|date|before_or_equal:today',
            
            // Personal Information
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:male,female,other',
            'blood_group' => 'nullable|string|max:5',
            'nationality' => 'required|string|max:50',
            'religion' => 'nullable|string|max:50',
            'caste' => 'nullable|string|max:50',
            'category' => 'required|in:General,OBC,SC,ST',
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:10',
            'phone' => 'nullable|string|max:20',
            
            // Parent/Guardian Information
            'father_name' => 'required|string|max:255',
            'father_occupation' => 'nullable|string|max:255',
            'father_phone' => 'nullable|string|max:20',
            'father_email' => 'nullable|email|max:255',
            'mother_name' => 'required|string|max:255',
            'mother_occupation' => 'nullable|string|max:255',
            'mother_phone' => 'nullable|string|max:20',
            'mother_email' => 'nullable|email|max:255',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_relation' => 'nullable|string|max:50',
            'guardian_phone' => 'nullable|string|max:20',
            'guardian_email' => 'nullable|email|max:255',
            
            // Emergency Contact
            'emergency_contact_name' => 'required|string|max:255',
            'emergency_contact_phone' => 'required|string|max:20',
            'emergency_contact_relation' => 'required|string|max:50',
            
            // Academic Information
            'previous_school' => 'nullable|string|max:255',
            'previous_class' => 'nullable|string|max:50',
            'previous_percentage' => 'nullable|numeric|min:0|max:100',
            'medical_conditions' => 'nullable|string',
            'allergies' => 'nullable|string',
            'special_needs' => 'nullable|string',
            
            // Transport Information
            'uses_transport' => 'boolean',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'pickup_point' => 'nullable|string|max:255',
            'drop_point' => 'nullable|string|max:255',
            
            // Status
            'status' => 'nullable|in:active,inactive,transferred,graduated,dropped',
            'status_date' => 'nullable|date',
            'status_reason' => 'nullable|string',
            
            // User data for creating user account
            'user_data' => 'nullable|array|required_without:user_id',
            'user_data.first_name' => 'required_with:user_data|string|max:255',
            'user_data.last_name' => 'required_with:user_data|string|max:255',
            'user_data.email' => 'required_with:user_data|email|unique:users,email',
            'user_data.password' => 'nullable|string|min:8'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'school_id.required' => 'School is required',
            'student_id.required' => 'Student ID is required',
            'student_id.unique' => 'Student ID already exists',
            'admission_number.unique' => 'Admission number already exists',
            'admission_date.before_or_equal' => 'Admission date cannot be in the future',
            'admission_type.required' => 'Admission type is required',
            'date_of_birth.required' => 'Date of birth is required',
            'date_of_birth.before' => 'Date of birth must be in the past',
            'gender.required' => 'Gender is required',
            'nationality.required' => 'Nationality is required',
            'category.required' => 'Category is required',
            'address.required' => 'Address is required',
            'city.required' => 'City is required',
            'state.required' => 'State is required',
            'postal_code.required' => 'Postal code is required',
            'father_name.required' => 'Father name is required',
            'mother_name.required' => 'Mother name is required',
            'emergency_contact_name.required' => 'Emergency contact name is required',
            'emergency_contact_phone.required' => 'Emergency contact phone is required',
            'emergency_contact_relation.required' => 'Emergency contact relation is required',
            'user_data.first_name.required_with' => 'First name is required when creating user account',
            'user_data.last_name.required_with' => 'Last name is required when creating user account',
            'user_data.email.required_with' => 'Email is required when creating user account',
            'user_data.email.unique' => 'Email already exists',
            'user_id.required_without' => 'User ID is required when not providing user data',
            'user_data.required_without' => 'User data is required when not providing user ID',
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
            $data['uses_transport'] = $this->boolean('uses_transport', false);
        }
        
        // Set default status if not provided
        if (!$this->has('status')) {
            $data['status'] = 'active';
        }
        
        // Set default admission type if not provided
        if (!$this->has('admission_type')) {
            $data['admission_type'] = 'new';
        }
        
        // Set default nationality if not provided
        if (!$this->has('nationality')) {
            $data['nationality'] = 'Indian';
        }
        
        // Set school_id for non-SuperAdmin users if not provided
        if (!$this->user()->isSuperAdmin() && !$this->has('school_id')) {
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
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('roll_number', 'Roll number already exists in this class and section');
                }
            }
        });
    }
}