<?php

namespace App\Http\Requests;

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
            // Basic Information
            'name' => 'required|string|max:255|unique:schools,name',
            'code' => 'nullable|string|max:20|unique:schools,code',
            'school_type' => 'required|in:primary,secondary,all',
            'email' => 'required|email|max:255|unique:schools,email',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            
            // Address
            'address' => 'nullable|string|max:500',
            
            // Principal Information
            'principal_name' => 'nullable|string|max:255',
            'principal_email' => 'nullable|email|max:255',
            'principal_phone' => 'nullable|string|max:20',
            
            // Additional Information
            'established_year_date' => 'nullable|date|before_or_equal:now',
            'established_year' => 'nullable|integer|min:1800|max:' . date('Y'),
            'board_affiliation' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:255',
            
            // Description
            'description' => 'nullable|string|max:1000',
            
            // Status
            'is_active' => 'boolean'
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
            'school_type.required' => 'School type is required',
            'school_type.in' => 'School type must be one of: primary, secondary, all',
            'email.required' => 'Email address is required',
            'email.email' => 'Please provide a valid email address',
            'email.unique' => 'A school with this email already exists',
            'website.url' => 'Please provide a valid website URL',
            'address.max' => 'Address cannot exceed 500 characters',
            'principal_email.email' => 'Please provide a valid principal email address',
            'established_year_date.date' => 'Please provide a valid establishment date',
            'established_year_date.before_or_equal' => 'Establishment date cannot be in the future',
            'established_year.integer' => 'Established year must be a valid year',
            'established_year.min' => 'Established year cannot be before 1800',
            'established_year.max' => 'Established year cannot be in the future',
            'description.max' => 'Description cannot exceed 1000 characters'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $data = [];
        
        // Set default is_active if not provided
        if (!$this->has('is_active')) {
            $data['is_active'] = true;
        }
        
        // Ensure boolean fields are properly cast
        if ($this->has('is_active')) {
            $data['is_active'] = filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN);
        }
        
        // Handle established_year date conversion
        if ($this->has('established_year') && $this->established_year) {
            try {
                // If it's a date string, extract the year
                $date = \Carbon\Carbon::parse($this->established_year);
                $data['established_year_date'] = $this->established_year; // Keep original date for validation
                $data['established_year'] = $date->year; // Extract year for storage
            } catch (\Exception $e) {
                // If parsing fails, keep original value for validation error
            }
        }
        
        // Convert empty strings to null for optional fields
        $optionalFields = [
            'code', 'phone', 'website', 'address', 'principal_name', 
            'principal_email', 'principal_phone',
            'board_affiliation', 'registration_number', 'description'
        ];
        
        foreach ($optionalFields as $field) {
            if ($this->has($field) && $this->$field === '') {
                $data[$field] = null;
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
            'school_type' => 'school type',
            'principal_name' => 'principal name',
            'principal_email' => 'principal email',
            'principal_phone' => 'principal phone',
            'established_year' => 'established year',
            'board_affiliation' => 'board affiliation',
            'registration_number' => 'registration number',
            'is_active' => 'status'
        ];
    }
}