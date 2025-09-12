<?php

namespace App\Modules\SuperAdmin\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SuperAdminUserCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => SuperAdminUserResource::collection($this->collection),
            'summary' => [
                'total_users' => $this->collection->count(),
                'active_users' => $this->collection->where('status', true)->count(),
                'inactive_users' => $this->collection->where('status', false)->count(),
                'verified_users' => $this->collection->whereNotNull('email_verified_at')->count(),
                'unverified_users' => $this->collection->whereNull('email_verified_at')->count(),
                'roles_distribution' => $this->getRolesDistribution(),
                'schools_distribution' => $this->getSchoolsDistribution(),
                'activity_summary' => $this->getActivitySummary(),
            ]
        ];
    }

    /**
     * Get roles distribution
     */
    private function getRolesDistribution(): array
    {
        return $this->collection->groupBy('role')->map(function ($users, $role) {
            return [
                'role' => $role,
                'count' => $users->count(),
                'percentage' => round(($users->count() / $this->collection->count()) * 100, 1)
            ];
        })->values()->toArray();
    }

    /**
     * Get schools distribution
     */
    private function getSchoolsDistribution(): array
    {
        return $this->collection->groupBy('school.name')->map(function ($users, $schoolName) {
            return [
                'school' => $schoolName ?: 'No School',
                'count' => $users->count(),
                'percentage' => round(($users->count() / $this->collection->count()) * 100, 1)
            ];
        })->values()->take(10)->toArray(); // Top 10 schools
    }

    /**
     * Get activity summary
     */
    private function getActivitySummary(): array
    {
        $now = now();
        
        return [
            'never_logged_in' => $this->collection->whereNull('last_login_at')->count(),
            'logged_in_today' => $this->collection->filter(function ($user) use ($now) {
                return $user->last_login_at && $user->last_login_at->isToday();
            })->count(),
            'logged_in_this_week' => $this->collection->filter(function ($user) use ($now) {
                return $user->last_login_at && $user->last_login_at->isAfter($now->subWeek());
            })->count(),
            'logged_in_this_month' => $this->collection->filter(function ($user) use ($now) {
                return $user->last_login_at && $user->last_login_at->isAfter($now->subMonth());
            })->count(),
            'inactive_30_days' => $this->collection->filter(function ($user) use ($now) {
                return $user->last_login_at && $user->last_login_at->isBefore($now->subDays(30));
            })->count(),
        ];
    }

    /**
     * Get additional meta information
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'generated_at' => now()->toISOString(),
                'generated_by' => auth()->user()->full_name,
                'filters_applied' => $request->only(['role', 'school_id', 'search', 'status', 'date_range']),
                'export_available' => true,
                'bulk_actions_available' => [
                    'activate',
                    'deactivate', 
                    'reset_password',
                    'change_school',
                    'delete'
                ]
            ]
        ];
    }
}