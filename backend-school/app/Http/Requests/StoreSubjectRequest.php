<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models$1;

class StoreSubjectRequest extends FormRequest
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
            'code' => 'required|string|max:20|unique:subjects,code',
            'description' => 'nullable|string|max:1000',
            'school_id' => 'required_if:role,SuperAdmin|exists:schools,id',
            'class_id' => 'nullable|exists:school_classes,id',
            'teacher_id' => 'nullable|exists:users,id',
            'credits' => 'nullable|integer|min:1|max:10',
            'type' => 'required|in:' . implode(',', Subject::TYPES),
            'status' => 'boolean',
            'syllabus' => 'nullable|string',
            'books_required' => 'nullable|array',
            'books_required.*' => 'string|max:255',
            'assessment_criteria' => 'nullable|array',
            'assessment_criteria.*.type' => 'required_with:assessment_criteria|string',
            'assessment_criteria.*.percentage' => 'required_with:assessment_criteria|numeric|min:0|max:100',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Subject name is required',
            'code.required' => 'Subject code is required',
            'code.unique' => 'Subject code already exists',
            'school_id.required_if' => 'School is required for SuperAdmin',
            'type.required' => 'Subject type is required',
            'type.in' => 'Invalid subject type selected',
            'teacher_id.exists' => 'Selected teacher does not exist',
            'class_id.exists' => 'Selected class does not exist',
            'credits.min' => 'Credits must be at least 1',
            'credits.max' => 'Credits cannot exceed 10',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        if (!$this->has('status')) {
            $this->merge(['status' => true]);
        }

        // Set school_id for non-SuperAdmin users
        if (!auth()->user()->isSuperAdmin()) {
            $this->merge(['school_id' => auth()->user()->school_id]);
        }
    }
}