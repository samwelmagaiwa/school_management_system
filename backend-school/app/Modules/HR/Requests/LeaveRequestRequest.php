<?php

namespace App\Modules\HR\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Modules\HR\Models\LeaveRequest;

class LeaveRequestRequest extends FormRequest
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
        return [
            'employee_id' => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:1000',
            'emergency_contact' => 'nullable|string|max:255',
            'handover_notes' => 'nullable|string|max:1000',
            'is_half_day' => 'boolean',
            'half_day_period' => 'required_if:is_half_day,true|in:' . LeaveRequest::HALF_DAY_MORNING . ',' . LeaveRequest::HALF_DAY_AFTERNOON,
            'documents' => 'nullable|array',
            'documents.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'Employee is required',
            'employee_id.exists' => 'Selected employee does not exist',
            'leave_type_id.required' => 'Leave type is required',
            'leave_type_id.exists' => 'Selected leave type does not exist',
            'start_date.required' => 'Start date is required',
            'start_date.after_or_equal' => 'Start date cannot be in the past',
            'end_date.required' => 'End date is required',
            'end_date.after_or_equal' => 'End date must be on or after start date',
            'reason.required' => 'Reason for leave is required',
            'reason.max' => 'Reason cannot exceed 1000 characters',
            'half_day_period.required_if' => 'Half day period is required for half day leave',
            'half_day_period.in' => 'Half day period must be morning or afternoon',
            'documents.*.file' => 'Each document must be a valid file',
            'documents.*.mimes' => 'Documents must be PDF, DOC, DOCX, JPG, JPEG, or PNG files',
            'documents.*.max' => 'Each document must not exceed 2MB',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        $this->merge([
            'is_half_day' => $this->is_half_day ?? false,
        ]);

        // Calculate days requested
        if ($this->start_date && $this->end_date) {
            if ($this->is_half_day) {
                $this->merge(['days_requested' => 0.5]);
            } else {
                $start = \Carbon\Carbon::parse($this->start_date);
                $end = \Carbon\Carbon::parse($this->end_date);
                $days = $start->diffInDays($end) + 1;
                
                // Calculate working days (excluding weekends)
                $workingDays = 0;
                $current = $start->copy();
                while ($current <= $end) {
                    if ($current->isWeekday()) {
                        $workingDays++;
                    }
                    $current->addDay();
                }
                
                $this->merge(['days_requested' => $workingDays]);
            }
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate half day logic
            if ($this->is_half_day) {
                if ($this->start_date !== $this->end_date) {
                    $validator->errors()->add('end_date', 'Half day leave must have the same start and end date.');
                }
            }

            // Check for overlapping leave requests
            if ($this->employee_id && $this->start_date && $this->end_date) {
                $existingLeave = \App\Modules\HR\Models\LeaveRequest::where('employee_id', $this->employee_id)
                    ->where('status', '!=', 'rejected')
                    ->where('status', '!=', 'cancelled')
                    ->where(function ($query) {
                        $query->whereBetween('start_date', [$this->start_date, $this->end_date])
                              ->orWhereBetween('end_date', [$this->start_date, $this->end_date])
                              ->orWhere(function ($q) {
                                  $q->where('start_date', '<=', $this->start_date)
                                    ->where('end_date', '>=', $this->end_date);
                              });
                    });

                // Exclude current leave request if updating
                if ($this->route('leaveRequest')) {
                    $existingLeave->where('id', '!=', $this->route('leaveRequest')->id);
                }

                if ($existingLeave->exists()) {
                    $validator->errors()->add('start_date', 'There is already a leave request for this period.');
                }
            }
        });
    }
}