<?php

namespace App\Modules\Fee\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Modules\Fee\Models\Fee;

class StoreFeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return in_array(auth()->user()->role, ['SuperAdmin', 'Admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|numeric|min:0',
            'type' => 'required|in:' . implode(',', Fee::TYPES),
            'frequency' => 'required|in:' . implode(',', Fee::FREQUENCIES),
            'school_id' => 'required_if:role,SuperAdmin|exists:schools,id',
            'class_id' => 'nullable|exists:school_classes,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'student_id' => 'nullable|exists:students,id',
            'due_date' => 'required|date|after_or_equal:today',
            'late_fee' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Fee name is required',
            'amount.required' => 'Fee amount is required',
            'amount.min' => 'Fee amount must be greater than or equal to 0',
            'type.required' => 'Fee type is required',
            'frequency.required' => 'Fee frequency is required',
            'due_date.required' => 'Due date is required',
            'due_date.after_or_equal' => 'Due date must be today or later',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        if (!$this->has('late_fee')) {
            $this->merge(['late_fee' => 0]);
        }
        
        if (!$this->has('discount')) {
            $this->merge(['discount' => 0]);
        }

        // Set school_id for non-SuperAdmin users
        if (!auth()->user()->isSuperAdmin()) {
            $this->merge(['school_id' => auth()->user()->school_id]);
        }
    }
}