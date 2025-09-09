<?php

namespace App\Modules\SuperAdmin\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Modules\SuperAdmin\Models\Role;

class RoleRequest extends FormRequest
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
        $roleId = $this->route('role')?->id;
        
        return [
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_-]+$/i',
                Rule::unique('roles', 'slug')->ignore($roleId)
            ],
            'description' => 'nullable|string|max:1000',
            'tenant_id' => 'nullable|exists:tenants,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'module_access' => 'nullable|array',
            'module_access.*' => [
                'string',
                Rule::in(array_keys(Role::getAvailableModules()))
            ],
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Role name is required',
            'slug.required' => 'Role slug is required',
            'slug.unique' => 'This role slug already exists',
            'slug.regex' => 'Role slug can only contain letters, numbers, underscores and hyphens',
            'tenant_id.exists' => 'Selected tenant does not exist',
            'permissions.array' => 'Permissions must be an array',
            'module_access.array' => 'Module access must be an array',
            'module_access.*.in' => 'Invalid module specified'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Auto-generate slug from name if not provided
        if (!$this->has('slug') && $this->has('name')) {
            $this->merge([
                'slug' => \Illuminate\Support\Str::slug($this->name, '_')
            ]);
        }
        
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
            
            // Validate that system roles cannot be modified by non-superadmins
            if ($this->route('role') && $this->route('role')->is_system) {
                if (!auth()->user()?->isSuperAdmin()) {
                    $validator->errors()->add('role', 'System roles can only be modified by SuperAdmins');
                }
            }
        });
    }
}