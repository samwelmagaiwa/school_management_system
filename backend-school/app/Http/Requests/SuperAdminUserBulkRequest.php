<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SuperAdminUserBulkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'action' => 'required|in:activate,deactivate,reset_password,change_school,delete',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|integer|exists:users,id',
            'data' => 'sometimes|array',
            'data.school_id' => 'required_if:action,change_school|exists:schools,id'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Bulk action is required.',
            'action.in' => 'Invalid bulk action selected.',
            'user_ids.required' => 'At least one user must be selected.',
            'user_ids.array' => 'User selection must be an array.',
            'user_ids.min' => 'At least one user must be selected.',
            'user_ids.*.required' => 'User ID is required.',
            'user_ids.*.integer' => 'User ID must be a valid integer.',
            'user_ids.*.exists' => 'One or more selected users do not exist.',
            'data.school_id.required_if' => 'School selection is required for changing school.',
            'data.school_id.exists' => 'Selected school does not exist.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'user_ids' => 'selected users',
            'data.school_id' => 'school'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check if any SuperAdmin users are selected for certain actions
            if (in_array($this->action, ['deactivate', 'delete', 'change_school'])) {
                $superAdminUsers = \App\Modules\User\Models\User::whereIn('id', $this->user_ids)
                    ->where('role', 'SuperAdmin')
                    ->count();

                if ($superAdminUsers > 0) {
                    $validator->errors()->add('user_ids', 'Cannot perform this action on SuperAdmin users.');
                }
            }

            // Validate that user is not trying to perform action on themselves for certain operations
            if (in_array($this->action, ['deactivate', 'delete']) && in_array(auth()->id(), $this->user_ids)) {
                $validator->errors()->add('user_ids', 'Cannot perform this action on your own account.');
            }
        });
    }
}