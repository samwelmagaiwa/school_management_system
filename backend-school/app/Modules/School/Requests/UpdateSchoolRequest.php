<?php

namespace App\Modules\School\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSchoolRequest extends FormRequest
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
        $schoolId = $this->route('school')?->id;
        
        return [
            'name' => 'sometimes|required|string|max:255|unique:schools,name,' . $schoolId,
            'code' => 'sometimes|nullable|string|max:20|unique:schools,code,' . $schoolId,
            'type' => 'sometimes|required|in:public,private,charter,international',
            'email' => 'sometimes|required|email|max:255|unique:schools,email,' . $schoolId,
            'phone' => 'sometimes|required|string|max:20',
            'website' => 'sometimes|nullable|url|max:255',
            'logo' => 'sometimes|nullable|string|max:500',
            
            // Address fields
            'address' => 'sometimes|required|string|max:500',
            'city' => 'sometimes|required|string|max:100',
            'state' => 'sometimes|required|string|max:100',
            'postal_code' => 'sometimes|required|string|max:20',
            'country' => 'sometimes|required|string|max:100',
            
            // Academic settings
            'academic_year_start' => 'sometimes|nullable|date',
            'academic_year_end' => 'sometimes|nullable|date|after:academic_year_start',
            'working_days' => 'sometimes|nullable|array',
            'working_days.*' => 'string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'session_start_time' => 'sometimes|nullable|date_format:H:i',
            'session_end_time' => 'sometimes|nullable|date_format:H:i|after:session_start_time',
            
            // Feature flags
            'library_enabled' => 'sometimes|boolean',
            'transport_enabled' => 'sometimes|boolean',
            'fee_management_enabled' => 'sometimes|boolean',
            'exam_management_enabled' => 'sometimes|boolean',
            'attendance_tracking_enabled' => 'sometimes|boolean',
            
            // Status
            'status' => 'sometimes|boolean',
            'description' => 'sometimes|nullable|string|max:1000'
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
        
        // Ensure boolean fields are properly cast
        $booleanFields = [
            'status',
            'library_enabled',
            'transport_enabled', 
            'fee_management_enabled',
            'exam_management_enabled',
            'attendance_tracking_enabled'
        ];
        
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