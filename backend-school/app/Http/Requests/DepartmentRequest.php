<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepartmentRequest extends FormRequest
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
        $departmentId = $this->route('department') ? $this->route('department')->id : null;

        return [
            'school_id' => 'required|exists:schools,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:departments,code,' . $departmentId . ',id,school_id,' . $this->school_id,
            'description' => 'nullable|string|max:1000',
            'head_id' => 'nullable|exists:employees,id',
            'budget' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'school_id.required' => 'School is required',
            'school_id.exists' => 'Selected school does not exist',
            'name.required' => 'Department name is required',
            'name.max' => 'Department name cannot exceed 255 characters',
            'code.required' => 'Department code is required',
            'code.unique' => 'This department code already exists in the selected school',
            'head_id.exists' => 'Selected department head does not exist',
            'budget.min' => 'Budget cannot be negative',
            'email.email' => 'Please provide a valid email address',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'school_id' => 'school',
            'head_id' => 'department head',
            'is_active' => 'active status',
        ];
    }
}