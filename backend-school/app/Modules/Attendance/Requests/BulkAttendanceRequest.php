<?php

namespace App\Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'school_id' => 'required|exists:schools,id',
            'class_id' => 'required|exists:classes,id',
            'teacher_id' => 'required|exists:teachers,id',
            'academic_year_id' => 'required|integer',
            'attendance_date' => 'required|date|before_or_equal:today',
            'subject_id' => 'nullable|exists:subjects,id',
            'period_number' => 'nullable|integer|min:1|max:10',
            'period_start_time' => 'nullable|date_format:H:i',
            'period_end_time' => 'nullable|date_format:H:i|after:period_start_time',
            'entry_method' => 'nullable|in:manual,biometric,rfid,mobile_app,bulk_import',
            
            'attendance_records' => 'required|array|min:1',
            'attendance_records.*.student_id' => 'required|exists:students,id',
            'attendance_records.*.status' => 'required|in:present,absent,late,half_day,sick,excused',
            'attendance_records.*.check_in_time' => 'nullable|date_format:H:i',
            'attendance_records.*.check_out_time' => 'nullable|date_format:H:i|after:attendance_records.*.check_in_time',
            'attendance_records.*.late_minutes' => 'nullable|integer|min:0|max:480',
            'attendance_records.*.remarks' => 'nullable|string|max:500',
            'attendance_records.*.absence_reason' => 'nullable|string|max:500',
            'attendance_records.*.is_excused' => 'boolean',
            'attendance_records.*.excuse_reason' => 'nullable|string|max:500'
        ];
    }

    public function messages(): array
    {
        return [
            'school_id.required' => 'School is required.',
            'school_id.exists' => 'Selected school does not exist.',
            'class_id.required' => 'Class is required.',
            'class_id.exists' => 'Selected class does not exist.',
            'teacher_id.required' => 'Teacher is required.',
            'teacher_id.exists' => 'Selected teacher does not exist.',
            'attendance_date.required' => 'Attendance date is required.',
            'attendance_date.date' => 'Attendance date must be a valid date.',
            'attendance_date.before_or_equal' => 'Attendance date cannot be in the future.',
            'subject_id.exists' => 'Selected subject does not exist.',
            'period_start_time.date_format' => 'Period start time must be in HH:MM format.',
            'period_end_time.date_format' => 'Period end time must be in HH:MM format.',
            'period_end_time.after' => 'Period end time must be after start time.',
            'entry_method.in' => 'Invalid entry method selected.',
            
            'attendance_records.required' => 'Attendance records are required.',
            'attendance_records.array' => 'Attendance records must be an array.',
            'attendance_records.min' => 'At least one attendance record is required.',
            'attendance_records.*.student_id.required' => 'Student ID is required for each record.',
            'attendance_records.*.student_id.exists' => 'One or more selected students do not exist.',
            'attendance_records.*.status.required' => 'Status is required for each record.',
            'attendance_records.*.status.in' => 'Invalid status selected for one or more records.',
            'attendance_records.*.check_in_time.date_format' => 'Check-in time must be in HH:MM format.',
            'attendance_records.*.check_out_time.date_format' => 'Check-out time must be in HH:MM format.',
            'attendance_records.*.check_out_time.after' => 'Check-out time must be after check-in time.',
            'attendance_records.*.late_minutes.integer' => 'Late minutes must be a number.',
            'attendance_records.*.late_minutes.min' => 'Late minutes cannot be negative.',
            'attendance_records.*.late_minutes.max' => 'Late minutes cannot exceed 8 hours (480 minutes).',
            'attendance_records.*.remarks.max' => 'Remarks cannot exceed 500 characters.',
            'attendance_records.*.absence_reason.max' => 'Absence reason cannot exceed 500 characters.',
            'attendance_records.*.excuse_reason.max' => 'Excuse reason cannot exceed 500 characters.'
        ];
    }

    protected function prepareForValidation()
    {
        // Set default values
        if (!$this->has('entry_method')) {
            $this->merge(['entry_method' => 'bulk_import']);
        }

        // Process attendance records
        if ($this->has('attendance_records')) {
            $records = $this->attendance_records;
            
            foreach ($records as $index => $record) {
                // Set default is_excused if not provided
                if (!isset($record['is_excused'])) {
                    $records[$index]['is_excused'] = false;
                }

                // Calculate late minutes if applicable
                if (isset($record['status']) && $record['status'] === 'late' && 
                    isset($record['check_in_time']) && $this->period_start_time) {
                    
                    try {
                        $startTime = \Carbon\Carbon::createFromFormat('H:i', $this->period_start_time);
                        $checkInTime = \Carbon\Carbon::createFromFormat('H:i', $record['check_in_time']);
                        
                        if ($checkInTime->gt($startTime)) {
                            $records[$index]['late_minutes'] = $checkInTime->diffInMinutes($startTime);
                        }
                    } catch (\Exception $e) {
                        // Invalid time format, let validation handle it
                    }
                }
            }
            
            $this->merge(['attendance_records' => $records]);
        }
    }
}