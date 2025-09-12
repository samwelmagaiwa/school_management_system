<?php

namespace App\Modules\SuperAdmin\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class SuperAdminUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'age' => $this->date_of_birth ? $this->date_of_birth->age : null,
            'gender' => $this->gender,
            'role' => $this->role,
            'status' => $this->status,
            'status_label' => $this->status ? 'Active' : 'Inactive',
            'email_verified_at' => $this->email_verified_at?->format('Y-m-d H:i:s'),
            'email_verified' => !is_null($this->email_verified_at),
            'last_login_at' => $this->last_login_at?->format('Y-m-d H:i:s'),
            'last_login_human' => $this->last_login_at?->diffForHumans(),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'created_at_human' => $this->created_at->diffForHumans(),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'profile_picture' => $this->profile_picture ? Storage::disk('public')->url($this->profile_picture) : null,
            
            // School information
            'school' => $this->whenLoaded('school', function () {
                return [
                    'id' => $this->school->id,
                    'name' => $this->school->name,
                    'code' => $this->school->code,
                    'address' => $this->school->address,
                    'phone' => $this->school->phone,
                    'email' => $this->school->email,
                    'status' => $this->school->status,
                ];
            }),

            // Related information based on role
            'student' => $this->whenLoaded('student', function () {
                return [
                    'id' => $this->student->id,
                    'student_id' => $this->student->student_id,
                    'admission_date' => $this->student->admission_date?->format('Y-m-d'),
                    'class_id' => $this->student->class_id,
                    'section' => $this->student->section,
                    'roll_number' => $this->student->roll_number,
                    'status' => $this->student->status,
                ];
            }),

            'teacher' => $this->whenLoaded('teacher', function () {
                return [
                    'id' => $this->teacher->id,
                    'employee_id' => $this->teacher->employee_id,
                    'joining_date' => $this->teacher->joining_date?->format('Y-m-d'),
                    'qualification' => $this->teacher->qualification,
                    'experience' => $this->teacher->experience,
                    'specialization' => $this->teacher->specialization,
                    'status' => $this->teacher->status,
                ];
            }),

            'employee' => $this->whenLoaded('employee', function () {
                return [
                    'id' => $this->employee->id,
                    'employee_id' => $this->employee->employee_id,
                    'department' => $this->employee->department,
                    'designation' => $this->employee->designation,
                    'joining_date' => $this->employee->joining_date?->format('Y-m-d'),
                    'salary' => $this->employee->salary,
                    'status' => $this->employee->status,
                ];
            }),

            // Permission and access information
            'permissions' => [
                'can_edit' => true, // SuperAdmin can edit all users
                'can_delete' => !$this->isSuperAdmin(), // Cannot delete other SuperAdmin users
                'can_impersonate' => !$this->isSuperAdmin(), // Cannot impersonate SuperAdmin users
                'can_reset_password' => !$this->isSuperAdmin(), // Cannot reset SuperAdmin passwords
                'can_change_status' => !$this->isSuperAdmin(), // Cannot change SuperAdmin status
            ],

            // Additional metadata
            'metadata' => [
                'is_superadmin' => $this->isSuperAdmin(),
                'is_admin' => $this->isAdmin(),
                'is_teacher' => $this->isTeacher(),
                'is_student' => $this->isStudent(),
                'is_parent' => $this->isParent(),
                'is_accountant' => $this->isAccountant(),
                'is_hr' => $this->isHR(),
                'days_since_registration' => $this->created_at->diffInDays(now()),
                'days_since_last_login' => $this->last_login_at ? $this->last_login_at->diffInDays(now()) : null,
                'account_age_category' => $this->getAccountAgeCategory(),
                'activity_status' => $this->getActivityStatus(),
            ],
        ];
    }

    /**
     * Get account age category
     */
    private function getAccountAgeCategory(): string
    {
        $days = $this->created_at->diffInDays(now());
        
        if ($days <= 7) {
            return 'new';
        } elseif ($days <= 30) {
            return 'recent';
        } elseif ($days <= 365) {
            return 'established';
        } else {
            return 'veteran';
        }
    }

    /**
     * Get activity status based on last login
     */
    private function getActivityStatus(): string
    {
        if (!$this->last_login_at) {
            return 'never_logged_in';
        }

        $daysSinceLogin = $this->last_login_at->diffInDays(now());
        
        if ($daysSinceLogin <= 1) {
            return 'very_active';
        } elseif ($daysSinceLogin <= 7) {
            return 'active';
        } elseif ($daysSinceLogin <= 30) {
            return 'moderate';
        } else {
            return 'inactive';
        }
    }
}