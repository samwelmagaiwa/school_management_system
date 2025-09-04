<?php

namespace App\Modules\IDCard\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Modules\IDCard\Models\IDCard;

class GenerateIDRequest extends FormRequest
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
            'school_id' => 'required|exists:schools,id',
            'type' => 'required|in:' . IDCard::TYPE_STUDENT . ',' . IDCard::TYPE_TEACHER,
            'student_id' => 'required_if:type,' . IDCard::TYPE_STUDENT . '|exists:students,id',
            'teacher_id' => 'required_if:type,' . IDCard::TYPE_TEACHER . '|exists:teachers,id',
            'template' => 'nullable|string|in:default,modern,classic',
            'expiry_years' => 'nullable|integer|min:1|max:10',
            'include_qr_code' => 'boolean',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
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
            'type.required' => 'ID card type is required',
            'type.in' => 'ID card type must be student or teacher',
            'student_id.required_if' => 'Student is required when type is student',
            'student_id.exists' => 'Selected student does not exist',
            'teacher_id.required_if' => 'Teacher is required when type is teacher',
            'teacher_id.exists' => 'Selected teacher does not exist',
            'template.in' => 'Template must be default, modern, or classic',
            'expiry_years.min' => 'Expiry years must be at least 1',
            'expiry_years.max' => 'Expiry years cannot exceed 10',
            'photo.image' => 'Photo must be an image file',
            'photo.mimes' => 'Photo must be a jpeg, png, or jpg file',
            'photo.max' => 'Photo size must not exceed 2MB',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        $this->merge([
            'template' => $this->template ?? 'default',
            'expiry_years' => $this->expiry_years ?? 1,
            'include_qr_code' => $this->include_qr_code ?? true,
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check if the student/teacher already has an active ID card
            if ($this->type === IDCard::TYPE_STUDENT && $this->student_id) {
                $existingCard = IDCard::where('student_id', $this->student_id)
                    ->where('is_active', true)
                    ->first();
                
                if ($existingCard) {
                    $validator->errors()->add('student_id', 'This student already has an active ID card.');
                }
            }

            if ($this->type === IDCard::TYPE_TEACHER && $this->teacher_id) {
                $existingCard = IDCard::where('teacher_id', $this->teacher_id)
                    ->where('is_active', true)
                    ->first();
                
                if ($existingCard) {
                    $validator->errors()->add('teacher_id', 'This teacher already has an active ID card.');
                }
            }
        });
    }
}