<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models$1;

class EmployeeRequest extends FormRequest
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
        $employeeId = $this->route('employee') ? $this->route('employee')->id : null;

        return [
            'user_id' => 'required|exists:users,id',
            'school_id' => 'required|exists:schools,id',
            'employee_id' => 'required|string|max:50|unique:employees,employee_id,' . $employeeId,
            'department_id' => 'required|exists:departments,id',
            'position_id' => 'required|exists:positions,id',
            'manager_id' => 'nullable|exists:employees,id',
            'hire_date' => 'required|date',
            'termination_date' => 'nullable|date|after:hire_date',
            'employment_type' => 'required|in:' . implode(',', [
                Employee::TYPE_FULL_TIME,
                Employee::TYPE_PART_TIME,
                Employee::TYPE_CONTRACT,
                Employee::TYPE_TEMPORARY,
                Employee::TYPE_INTERN
            ]),
            'employment_status' => 'required|in:' . implode(',', [
                Employee::STATUS_ACTIVE,
                Employee::STATUS_INACTIVE,
                Employee::STATUS_TERMINATED,
                Employee::STATUS_ON_LEAVE,
                Employee::STATUS_SUSPENDED
            ]),
            'work_schedule' => 'nullable|string|max:100',
            'salary' => 'nullable|numeric|min:0',
            'hourly_rate' => 'nullable|numeric|min:0',
            'phone' => 'required|string|max:20',
            'emergency_contact_name' => 'required|string|max:255',
            'emergency_contact_phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:male,female,other',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            'national_id' => 'nullable|string|max:50',
            'tax_id' => 'nullable|string|max:50',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:100',
            'qualifications' => 'nullable|array',
            'qualifications.*' => 'string|max:255',
            'certifications' => 'nullable|array',
            'certifications.*' => 'string|max:255',
            'skills' => 'nullable|array',
            'skills.*' => 'string|max:100',
            'notes' => 'nullable|string|max:1000',
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
            'employee_id.required' => 'Employee ID is required',
            'employee_id.unique' => 'This employee ID is already taken',
            'department_id.required' => 'Department is required',
            'department_id.exists' => 'Selected department does not exist',
            'position_id.required' => 'Position is required',
            'position_id.exists' => 'Selected position does not exist',
            'manager_id.exists' => 'Selected manager does not exist',
            'hire_date.required' => 'Hire date is required',
            'termination_date.after' => 'Termination date must be after hire date',
            'employment_type.required' => 'Employment type is required',
            'employment_type.in' => 'Invalid employment type',
            'employment_status.required' => 'Employment status is required',
            'employment_status.in' => 'Invalid employment status',
            'salary.min' => 'Salary cannot be negative',
            'hourly_rate.min' => 'Hourly rate cannot be negative',
            'phone.required' => 'Phone number is required',
            'emergency_contact_name.required' => 'Emergency contact name is required',
            'emergency_contact_phone.required' => 'Emergency contact phone is required',
            'address.required' => 'Address is required',
            'date_of_birth.required' => 'Date of birth is required',
            'date_of_birth.before' => 'Date of birth must be before today',
            'gender.required' => 'Gender is required',
            'gender.in' => 'Gender must be male, female, or other',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate that manager is not the same as employee
            if ($this->manager_id && $this->route('employee')) {
                if ($this->manager_id == $this->route('employee')->id) {
                    $validator->errors()->add('manager_id', 'Employee cannot be their own manager.');
                }
            }

            // Validate salary or hourly rate based on employment type
            if (in_array($this->employment_type, [Employee::TYPE_FULL_TIME, Employee::TYPE_PART_TIME])) {
                if (!$this->salary && !$this->hourly_rate) {
                    $validator->errors()->add('salary', 'Either salary or hourly rate is required for this employment type.');
                }
            }
        });
    }
}