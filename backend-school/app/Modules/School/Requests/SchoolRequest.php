<?php

namespace App\Modules\School\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SchoolRequest extends FormRequest
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
        $schoolId = $this->route('school') ? $this->route('school')->id : null;

        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:schools,code,' . $schoolId,
            'address' => 'required|string|max:500',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255|unique:schools,email,' . $schoolId,
            'website' => 'nullable|url|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'established_year' => 'required|integer|min:1800|max:' . date('Y'),
            'principal_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'School name is required',
            'code.required' => 'School code is required',
            'code.unique' => 'This school code is already taken',
            'address.required' => 'School address is required',
            'phone.required' => 'Phone number is required',
            'email.required' => 'Email address is required',
            'email.unique' => 'This email is already registered',
            'established_year.required' => 'Established year is required',
            'established_year.min' => 'Established year must be after 1800',
            'established_year.max' => 'Established year cannot be in the future',
            'principal_name.required' => 'Principal name is required',
            'logo.image' => 'Logo must be an image file',
            'logo.mimes' => 'Logo must be a jpeg, png, jpg, or gif file',
            'logo.max' => 'Logo size must not exceed 2MB',
        ];
    }
}