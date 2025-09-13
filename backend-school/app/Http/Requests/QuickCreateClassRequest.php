<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuickCreateClassRequest extends FormRequest
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
        $schoolId = auth()->user()->isSuperAdmin() ? $this->school_id : auth()->user()->school_id;

        return [
            // Required fields
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('classes', 'name')->where(function ($query) use ($schoolId) {
                    return $query->where('school_id', $schoolId)
                                 ->where('grade', $this->grade)
                                 ->where('section', $this->section ?? 'A');
                })
            ],
            'grade' => 'required|integer|min:1|max:12',
            'school_id' => [
                'required_if:role,SuperAdmin',
                'exists:schools,id'
            ],
            
            // Optional fields with validation
            'code' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('classes', 'code')->where(function ($query) use ($schoolId) {
                    return $query->where('school_id', $schoolId);
                })
            ],
            'section' => 'nullable|string|max:10|in:A,B,C,D,E',
            'capacity' => 'nullable|integer|min:1|max:100',
            'class_teacher_id' => [
                'nullable',
                'exists:teachers,id',
                function ($attribute, $value, $fail) use ($schoolId) {
                    if ($value && $schoolId) {
                        $teacher = \App\Modules\Teacher\Models\Teacher::find($value);
                        if ($teacher && $teacher->school_id != $schoolId) {
                            $fail('The selected teacher does not belong to the specified school.');
                        }
                        
                        // Check if teacher is already assigned to another class
                        $alreadyAssigned = \App\Modules\Class\Models\SchoolClass::where('class_teacher_id', $value)
                            ->where('status', true)
                            ->exists();
                        if ($alreadyAssigned) {
                            $fail('This teacher is already assigned to another class.');
                        }
                    }
                }
            ],
            'subject_ids' => 'nullable|array',
            'subject_ids.*' => [
                'exists:subjects,id',
                function ($attribute, $value, $fail) use ($schoolId) {
                    if ($schoolId) {
                        $subject = \App\Modules\Subject\Models\Subject::find($value);
                        if ($subject && $subject->school_id != $schoolId) {
                            $fail('The selected subject does not belong to the specified school.');
                        }
                    }
                }
            ],
            'room_number' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'academic_year' => 'nullable|string|max:10|regex:/^\d{4}-\d{4}$/',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Class name is required.',
            'name.unique' => 'A class with this name already exists for the same grade and section.',
            'grade.required' => 'Grade is required.',
            'grade.integer' => 'Grade must be a valid number.',
            'grade.min' => 'Grade must be at least 1.',
            'grade.max' => 'Grade cannot exceed 12.',
            'school_id.required_if' => 'School selection is required.',
            'school_id.exists' => 'Selected school does not exist.',
            'code.unique' => 'This class code already exists.',
            'section.in' => 'Section must be one of: A, B, C, D, E.',
            'capacity.integer' => 'Capacity must be a valid number.',
            'capacity.min' => 'Capacity must be at least 1.',
            'capacity.max' => 'Capacity cannot exceed 100.',
            'class_teacher_id.exists' => 'Selected teacher does not exist.',
            'subject_ids.array' => 'Subjects must be provided as a list.',
            'subject_ids.*.exists' => 'One or more selected subjects do not exist.',
            'room_number.max' => 'Room number cannot exceed 20 characters.',
            'description.max' => 'Description cannot exceed 500 characters.',
            'academic_year.regex' => 'Academic year must be in format YYYY-YYYY (e.g., 2024-2025).',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set school_id for non-SuperAdmin users
        if (!auth()->user()->isSuperAdmin()) {
            $this->merge([
                'school_id' => auth()->user()->school_id
            ]);
        }

        // Set default values
        $this->merge([
            'section' => $this->section ?? 'A',
            'capacity' => $this->capacity ?? 40,
            'academic_year' => $this->academic_year ?? date('Y') . '-' . (date('Y') + 1),
        ]);

        // Ensure subject_ids is an array if provided
        if ($this->subject_ids && !is_array($this->subject_ids)) {
            $this->merge(['subject_ids' => [$this->subject_ids]]);
        }
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function attributes(): array
    {
        return [
            'name' => 'class name',
            'grade' => 'grade',
            'school_id' => 'school',
            'code' => 'class code',
            'section' => 'section',
            'capacity' => 'capacity',
            'class_teacher_id' => 'class teacher',
            'subject_ids' => 'subjects',
            'room_number' => 'room number',
            'description' => 'description',
            'academic_year' => 'academic year',
        ];
    }
}