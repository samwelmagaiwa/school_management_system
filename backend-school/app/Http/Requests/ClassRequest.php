<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClassRequest extends FormRequest
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
        $classId = $this->route('class')?->id;
        
        return [
            'school_id' => 'required|exists:schools,id',
            'name' => 'required|string|max:100',
            'section' => 'nullable|string|max:10',
            'class_code' => 'nullable|string|max:20',
            'grade' => 'required|string|max:20',
            'grade_level' => 'nullable|integer|min:1|max:12',
            'class_teacher_id' => 'nullable|exists:teachers,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'academic_year' => 'required|string|max:20',
            'capacity' => 'required|integer|min:1|max:200',
            'current_strength' => 'nullable|integer|min:0',
            'room_number' => 'nullable|string|max:20',
            'building' => 'nullable|string|max:50',
            'floor' => 'nullable|string|max:20',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'working_days' => 'nullable|array',
            'working_days.*' => 'string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'stream' => 'nullable|string|in:science,commerce,arts,general',
            'description' => 'nullable|string|max:500',
            'subjects' => 'nullable|array',
            'subjects.*' => 'exists:subjects,id',
            'subject_ids' => 'nullable|array',
            'subject_ids.*' => 'exists:subjects,id',
            'is_active' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
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
            'name.required' => 'Class name is required',
            'name.max' => 'Class name cannot exceed 100 characters',
            'grade.required' => 'Grade is required',
            'grade_level.integer' => 'Grade level must be a number',
            'grade_level.min' => 'Grade level must be at least 1',
            'grade_level.max' => 'Grade level cannot exceed 12',
            'class_teacher_id.exists' => 'Selected class teacher does not exist',
            'academic_year_id.exists' => 'Selected academic year does not exist',
            'capacity.required' => 'Class capacity is required',
            'capacity.min' => 'Class capacity must be at least 1',
            'capacity.max' => 'Class capacity cannot exceed 200',
            'current_strength.min' => 'Current strength cannot be negative',
            'academic_year.required' => 'Academic year is required',
            'start_time.date_format' => 'Start time must be in HH:MM format',
            'end_time.date_format' => 'End time must be in HH:MM format',
            'end_time.after' => 'End time must be after start time',
            'working_days.array' => 'Working days must be an array',
            'working_days.*.in' => 'Invalid working day specified',
            'stream.in' => 'Invalid stream selected',
            'subjects.array' => 'Subjects must be an array',
            'subjects.*.exists' => 'One or more subjects do not exist',
            'subject_ids.array' => 'Subject IDs must be an array',
            'subject_ids.*.exists' => 'One or more subject IDs do not exist',
            'start_date.date' => 'Start date must be a valid date',
            'end_date.date' => 'End date must be a valid date',
            'end_date.after_or_equal' => 'End date must be after or equal to start date',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $data = [];
        
        // Ensure capacity is an integer
        if ($this->has('capacity')) {
            $data['capacity'] = (int) $this->capacity;
        }
        
        // Ensure current_strength is an integer
        if ($this->has('current_strength')) {
            $data['current_strength'] = (int) $this->current_strength;
        }
        
        // Convert grade to grade_level if numeric
        if ($this->has('grade') && is_numeric($this->grade)) {
            $data['grade_level'] = (int) $this->grade;
        }
        
        // Handle subject_ids from subjects field
        if ($this->has('subjects') && is_array($this->subjects)) {
            $data['subject_ids'] = $this->subjects;
        }
        
        // Ensure boolean fields are properly cast
        if ($this->has('is_active')) {
            $data['is_active'] = filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN);
        }
        
        if (!empty($data)) {
            $this->merge($data);
        }
    }
}