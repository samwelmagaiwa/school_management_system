<?php

namespace App\Modules\School\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSchoolRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:schools,name',
            'code' => 'nullable|string|max:20|unique:schools,code',
            'type' => 'required|in:public,private,charter,international',
            'email' => 'required|email|max:255|unique:schools,email',
            'phone' => 'required|string|max:20',
            'website' => 'nullable|url|max:255',
            'logo' => 'nullable|string|max:500',
            
            // Address fields
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            
            // Academic settings
            'academic_year_start' => 'nullable|date',
            'academic_year_end' => 'nullable|date|after:academic_year_start',
            'working_days' => 'nullable|array',
            'working_days.*' => 'string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'session_start_time' => 'nullable|date_format:H:i',
            'session_end_time' => 'nullable|date_format:H:i|after:session_start_time',
            
            // Feature flags
            'library_enabled' => 'boolean',
            'transport_enabled' => 'boolean',
            'fee_management_enabled' => 'boolean',
            'exam_management_enabled' => 'boolean',
            'attendance_tracking_enabled' => 'boolean',
            
            // Status
            'status' => 'boolean',
            'description' => 'nullable|string|max:1000'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'School name is required',
            'name.unique' => 'A school with this name already exists',
            'name.max' => 'School name cannot exceed 255 characters',
            'code.unique' => 'A school with this code already exists',
            'code.max' => 'School code cannot exceed 20 characters',
            'type.required' => 'School type is required',
            'type.in' => 'School type must be one of: public, private, charter, international',
            'email.required' => 'Email address is required',
            'email.email' => 'Please provide a valid email address',
            'email.unique' => 'A school with this email already exists',
            'phone.required' => 'Phone number is required',
            'website.url' => 'Please provide a valid website URL',
            'address.required' => 'Address is required',
            'address.max' => 'Address cannot exceed 500 characters',
            'city.required' => 'City is required',
            'city.max' => 'City name cannot exceed 100 characters',
            'state.required' => 'State is required',
            'state.max' => 'State name cannot exceed 100 characters',
            'postal_code.required' => 'Postal code is required',
            'country.required' => 'Country is required',
            'academic_year_end.after' => 'Academic year end date must be after start date',
            'working_days.array' => 'Working days must be an array',
            'working_days.*.in' => 'Invalid working day specified',
            'session_start_time.date_format' => 'Session start time must be in HH:MM format',
            'session_end_time.date_format' => 'Session end time must be in HH:MM format',
            'session_end_time.after' => 'Session end time must be after start time'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $data = [];
        
        // Set default status if not provided
        if (!$this->has('status')) {
            $data['status'] = true;
        }
        
        // Set default feature flags if not provided
        $features = [
            'library_enabled',
            'transport_enabled', 
            'fee_management_enabled',
            'exam_management_enabled',
            'attendance_tracking_enabled'
        ];
        
        foreach ($features as $feature) {
            if (!$this->has($feature)) {
                $data[$feature] = true;
            }
        }
        
        // Set default working days if not provided
        if (!$this->has('working_days')) {
            $data['working_days'] = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        }
        
        // Ensure boolean fields are properly cast
        $booleanFields = ['status'] + $features;
        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $data[$field] = filter_var($this->$field, FILTER_VALIDATE_BOOLEAN);
            }
        }
        
        if (!empty($data)) {
            $this->merge($data);
        }
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'academic_year_start' => 'academic year start date',
            'academic_year_end' => 'academic year end date',
            'working_days' => 'working days',
            'session_start_time' => 'session start time',
            'session_end_time' => 'session end time',
            'postal_code' => 'postal code',
            'library_enabled' => 'library feature',
            'transport_enabled' => 'transport feature',
            'fee_management_enabled' => 'fee management feature',
            'exam_management_enabled' => 'exam management feature',
            'attendance_tracking_enabled' => 'attendance tracking feature'
        ];
    }
}