<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models$1;

class AttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'school_id' => 'required|exists:schools,id',
            'class_id' => 'required|exists:classes,id',
            'student_id' => 'required|exists:students,id',
            'teacher_id' => 'required|exists:teachers,id',
            'academic_year_id' => 'required|integer',
            'attendance_date' => 'required|date|before_or_equal:today',
            'status' => 'required|in:present,absent,late,half_day,sick,excused',
            'period_number' => 'nullable|integer|min:1|max:10',
            'period_start_time' => 'nullable|date_format:H:i',
            'period_end_time' => 'nullable|date_format:H:i|after:period_start_time',
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i|after:check_in_time',
            'late_minutes' => 'nullable|integer|min:0|max:480',
            'remarks' => 'nullable|string|max:500',
            'absence_reason' => 'nullable|string|max:500',
            'is_excused' => 'boolean',
            'excuse_reason' => 'nullable|string|max:500',
            'entry_method' => 'nullable|in:manual,biometric,rfid,mobile_app,bulk_import',
            'subject_id' => 'nullable|exists:subjects,id'
        ];

        // Additional validation for update requests
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['attendance_date'] = 'required|date'; // Allow future dates for updates
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'school_id.required' => 'School is required.',
            'school_id.exists' => 'Selected school does not exist.',
            'class_id.required' => 'Class is required.',
            'class_id.exists' => 'Selected class does not exist.',
            'student_id.required' => 'Student is required.',
            'student_id.exists' => 'Selected student does not exist.',
            'teacher_id.required' => 'Teacher is required.',
            'teacher_id.exists' => 'Selected teacher does not exist.',
            'attendance_date.required' => 'Attendance date is required.',
            'attendance_date.date' => 'Attendance date must be a valid date.',
            'attendance_date.before_or_equal' => 'Attendance date cannot be in the future.',
            'status.required' => 'Attendance status is required.',
            'status.in' => 'Invalid attendance status selected.',
            'period_start_time.date_format' => 'Period start time must be in HH:MM format.',
            'period_end_time.date_format' => 'Period end time must be in HH:MM format.',
            'period_end_time.after' => 'Period end time must be after start time.',
            'check_in_time.date_format' => 'Check-in time must be in HH:MM format.',
            'check_out_time.date_format' => 'Check-out time must be in HH:MM format.',
            'check_out_time.after' => 'Check-out time must be after check-in time.',
            'late_minutes.integer' => 'Late minutes must be a number.',
            'late_minutes.min' => 'Late minutes cannot be negative.',
            'late_minutes.max' => 'Late minutes cannot exceed 8 hours (480 minutes).',
            'remarks.max' => 'Remarks cannot exceed 500 characters.',
            'absence_reason.max' => 'Absence reason cannot exceed 500 characters.',
            'excuse_reason.max' => 'Excuse reason cannot exceed 500 characters.',
            'entry_method.in' => 'Invalid entry method selected.',
            'subject_id.exists' => 'Selected subject does not exist.'
        ];
    }

    protected function prepareForValidation()
    {
        // Set default values
        if (!$this->has('entry_method')) {
            $this->merge(['entry_method' => Attendance::ENTRY_METHOD_MANUAL]);
        }

        if (!$this->has('is_excused')) {
            $this->merge(['is_excused' => false]);
        }

        // Calculate late minutes if check-in time is provided and status is late
        if ($this->status === 'late' && $this->check_in_time && $this->period_start_time) {
            $startTime = \Carbon\Carbon::createFromFormat('H:i', $this->period_start_time);
            $checkInTime = \Carbon\Carbon::createFromFormat('H:i', $this->check_in_time);
            
            if ($checkInTime->gt($startTime)) {
                $this->merge(['late_minutes' => $checkInTime->diffInMinutes($startTime)]);
            }
        }
    }
}