<?php

namespace App\Modules\SuperAdmin\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Modules\SuperAdmin\Models\Role;

class TenantPermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'tenant_id' => 'required|exists:tenants,id',
            'role_slug' => [
                'required',
                'string',
                Rule::exists('roles', 'slug')
            ],
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'module_access' => 'nullable|array',
            'module_access.*' => [
                'string',
                Rule::in(array_keys(Role::getAvailableModules()))
            ],
            'custom_permissions' => 'nullable|array',
            'custom_permissions.*' => 'string',
            'is_active' => 'nullable|boolean'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'tenant_id.required' => 'Tenant ID is required',
            'tenant_id.exists' => 'Selected tenant does not exist',
            'role_slug.required' => 'Role slug is required',
            'role_slug.exists' => 'Selected role does not exist',
            'permissions.array' => 'Permissions must be an array',
            'module_access.array' => 'Module access must be an array',
            'module_access.*.in' => 'Invalid module specified',
            'custom_permissions.array' => 'Custom permissions must be an array'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure arrays are properly formatted
        if ($this->has('permissions') && !is_array($this->permissions)) {
            $this->merge([
                'permissions' => []
            ]);
        }
        
        if ($this->has('module_access') && !is_array($this->module_access)) {
            $this->merge([
                'module_access' => []
            ]);
        }
        
        if ($this->has('custom_permissions') && !is_array($this->custom_permissions)) {
            $this->merge([
                'custom_permissions' => []
            ]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate permissions exist in the system
            if ($this->has('permissions') && is_array($this->permissions)) {
                $allPermissions = array_keys(Role::getAllPermissions());
                $allPermissions[] = '*'; // Allow wildcard permission
                
                foreach ($this->permissions as $permission) {
                    if (!in_array($permission, $allPermissions)) {
                        $validator->errors()->add('permissions', "Invalid permission: {$permission}");
                    }
                }
            }
            
            // Validate custom permissions format
            if ($this->has('custom_permissions') && is_array($this->custom_permissions)) {
                foreach ($this->custom_permissions as $permission) {
                    if (!preg_match('/^[a-z0-9_.-]+$/i', $permission)) {
                        $validator->errors()->add('custom_permissions', "Invalid custom permission format: {$permission}");
                    }
                }
            }
            
            // Check if tenant exists and is active
            if ($this->has('tenant_id')) {
                $tenant = \App\Modules\SuperAdmin\Models\Tenant::find($this->tenant_id);
                if ($tenant && !$tenant->is_active) {
                    $validator->errors()->add('tenant_id', 'Cannot modify permissions for inactive tenant');
                }
            }
        });
    }
}