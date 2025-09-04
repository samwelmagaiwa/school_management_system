<?php

namespace App\Modules\Subject\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Modules\Subject\Models\Subject;

class SubjectRequest extends FormRequest
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
        $subjectId = $this->route('subject') ? $this->route('subject')->id : null;

        return [
            'school_id' => 'required|exists:schools,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:subjects,code,' . $subjectId . ',id,school_id,' . $this->school_id,
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:' . implode(',', [
                Subject::TYPE_CORE,
                Subject::TYPE_ELECTIVE,
                Subject::TYPE_OPTIONAL,
                Subject::TYPE_EXTRA_CURRICULAR
            ]),
            'credits' => 'required|integer|min:1|max:10',
            'is_practical' => 'boolean',
            'is_active' => 'boolean',
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
            'name.required' => 'Subject name is required',
            'name.max' => 'Subject name cannot exceed 255 characters',
            'code.required' => 'Subject code is required',
            'code.unique' => 'This subject code already exists in the selected school',
            'type.required' => 'Subject type is required',
            'type.in' => 'Subject type must be core, elective, optional, or extra_curricular',
            'credits.required' => 'Credits are required',
            'credits.min' => 'Credits must be at least 1',
            'credits.max' => 'Credits cannot exceed 10',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'school_id' => 'school',
            'is_practical' => 'practical subject',
            'is_active' => 'active status',
        ];
    }
}