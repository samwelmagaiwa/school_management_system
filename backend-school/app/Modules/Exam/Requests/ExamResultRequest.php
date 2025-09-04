<?php

namespace App\Modules\Exam\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExamResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'results' => 'required|array|min:1',
            'results.*.student_id' => 'required|exists:students,id',
            'results.*.marks_obtained' => 'required|numeric|min:0',
            'results.*.remarks' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'results.required' => 'At least one result is required.',
            'results.*.student_id.required' => 'Student ID is required for each result.',
            'results.*.student_id.exists' => 'Selected student does not exist.',
            'results.*.marks_obtained.required' => 'Marks obtained is required for each result.',
            'results.*.marks_obtained.numeric' => 'Marks obtained must be a number.',
            'results.*.marks_obtained.min' => 'Marks obtained cannot be negative.',
            'results.*.remarks.max' => 'Remarks cannot exceed 500 characters.',
        ];
    }
}