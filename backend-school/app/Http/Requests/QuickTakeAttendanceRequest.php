<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuickTakeAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return in_array(auth()->user()->role, ['SuperAdmin', 'Admin', 'Teacher']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Required fields
            'class_id' => [
                'required',
                'exists:classes,id',
                function ($attribute, $value, $fail) {
                    $user = auth()->user();
                    $class = \App\Modules\Class\Models\SchoolClass::find($value);
                    
                    if (!$class) {
                        $fail('The selected class does not exist.');
                        return;
                    }
                    
                    // Check school access
                    if (!$user->isSuperAdmin() && $user->school_id != $class->school_id) {
                        $fail('You do not have access to this class.');
                        return;
                    }
                    
                    // Check teacher access
                    if ($user->isTeacher()) {
                        $hasAccess = $class->class_teacher_id == ($user->teacher->id ?? null) ||
                                    $class->subjects()->whereHas('teachers', function($q) use ($user) {
                                        $q->where('teachers.id', $user->teacher->id ?? null);
                                    })->exists();
                        
                        if (!$hasAccess) {
                            $fail('You do not have permission to take attendance for this class.');
                        }
                    }
                }
            ],
            'date' => 'required|date|before_or_equal:today',
            'period' => 'required|in:morning,afternoon,full_day',
            'attendance' => 'required|array|min:1',
            'attendance.*.student_id' => [
                'required',
                'exists:students,id',
                function ($attribute, $value, $fail) {
                    $classId = $this->input('class_id');
                    $student = \App\Modules\Student\Models\Student::find($value);
                    
                    if ($student && $student->class_id != $classId) {
                        $fail('Student does not belong to the selected class.');
                    }
                }
            ],
            'attendance.*.status' => 'required|in:present,absent,late',
            'attendance.*.remarks' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'class_id.required' => 'Class selection is required.',
            'class_id.exists' => 'Selected class does not exist.',
            'date.required' => 'Date is required.',
            'date.date' => 'Please enter a valid date.',
            'date.before_or_equal' => 'Attendance date cannot be in the future.',
            'period.required' => 'Period selection is required.',
            'period.in' => 'Period must be morning, afternoon, or full_day.',
            'attendance.required' => 'Attendance data is required.',
            'attendance.array' => 'Attendance data must be provided as a list.',
            'attendance.min' => 'At least one student attendance record is required.',
            'attendance.*.student_id.required' => 'Student ID is required for each attendance record.',
            'attendance.*.student_id.exists' => 'One or more selected students do not exist.',
            'attendance.*.status.required' => 'Attendance status is required for each student.',
            'attendance.*.status.in' => 'Attendance status must be present, absent, or late.',
            'attendance.*.remarks.max' => 'Remarks cannot exceed 255 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        $this->merge([
            'period' => $this->period ?? 'full_day',
            'date' => $this->date ?? now()->toDateString(),
        ]);

        // Ensure attendance is an array
        if ($this->attendance && !is_array($this->attendance)) {
            $this->merge(['attendance' => [$this->attendance]]);
        }

        // Clean up attendance data
        if ($this->attendance && is_array($this->attendance)) {
            $cleanedAttendance = [];
            foreach ($this->attendance as $record) {
                if (is_array($record) && isset($record['student_id']) && isset($record['status'])) {
                    $cleanedAttendance[] = [
                        'student_id' => (int) $record['student_id'],
                        'status' => strtolower(trim($record['status'])),
                        'remarks' => isset($record['remarks']) ? trim($record['remarks']) : null
                    ];
                }
            }
            $this->merge(['attendance' => $cleanedAttendance]);
        }
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function attributes(): array
    {
        return [
            'class_id' => 'class',
            'date' => 'date',
            'period' => 'period',
            'attendance' => 'attendance data',
            'attendance.*.student_id' => 'student',
            'attendance.*.status' => 'attendance status',
            'attendance.*.remarks' => 'remarks',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check for duplicate student IDs in attendance array
            if ($this->attendance && is_array($this->attendance)) {
                $studentIds = array_column($this->attendance, 'student_id');
                $duplicates = array_diff_assoc($studentIds, array_unique($studentIds));
                
                if (!empty($duplicates)) {
                    $validator->errors()->add('attendance', 'Duplicate student entries found in attendance data.');
                }
            }

            // Validate that all students belong to the selected class
            if ($this->class_id && $this->attendance && is_array($this->attendance)) {
                $classStudentIds = \App\Modules\Student\Models\Student::where('class_id', $this->class_id)
                    ->where('status', true)
                    ->pluck('id')
                    ->toArray();
                
                $attendanceStudentIds = array_column($this->attendance, 'student_id');
                $invalidStudents = array_diff($attendanceStudentIds, $classStudentIds);
                
                if (!empty($invalidStudents)) {
                    $validator->errors()->add('attendance', 'Some students do not belong to the selected class or are inactive.');
                }
            }

            // Check if attendance already exists (optional warning)
            if ($this->class_id && $this->date && $this->period) {
                $exists = \App\Modules\Attendance\Models\Attendance::where('class_id', $this->class_id)
                    ->where('date', $this->date)
                    ->where('period', $this->period)
                    ->exists();
                
                if ($exists) {
                    // This is just a warning, not an error
                    $this->merge(['_attendance_exists_warning' => true]);
                }
            }
        });
    }
}