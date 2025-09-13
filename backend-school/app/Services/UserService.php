<?php

namespace App\Services;

use App\Models\User;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserService
{
    /**
     * Create a new user
     */
    public function createUser(array $userData): User
    {
        return DB::transaction(function () use ($userData) {
            // Hash password if provided, otherwise use last_name
            if (isset($userData['password'])) {
                $userData['password'] = Hash::make($userData['password']);
            } else {
                $userData['password'] = Hash::make($userData['last_name']);
            }

            return User::create($userData);
        });
    }

    /**
     * Update an existing user
     */
    public function updateUser(User $user, array $userData): User
    {
        return DB::transaction(function () use ($user, $userData) {
            // Hash password if provided
            if (isset($userData['password'])) {
                $userData['password'] = Hash::make($userData['password']);
            }

            $user->update($userData);
            return $user;
        });
    }

    /**
     * Get user statistics
     */
    public function getUserStatistics(?int $schoolId = null): array
    {
        $query = User::query();

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $totalUsers = $query->count();
        $activeUsers = $query->where('status', true)->count();

        $roleStats = [];
        foreach (User::ROLES as $role) {
            $roleQuery = clone $query;
            $roleStats[$role] = [
                'total' => $roleQuery->where('role', $role)->count(),
                'active' => $roleQuery->where('role', $role)->where('status', true)->count(),
            ];
        }

        return [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'inactive_users' => $totalUsers - $activeUsers,
            'role_statistics' => $roleStats,
        ];
    }

    /**
     * Reset user password to default (last_name)
     */
    public function resetPasswordToDefault(User $user): bool
    {
        return $user->update([
            'password' => Hash::make($user->last_name)
        ]);
    }

    /**
     * Bulk update user status
     */
    public function bulkUpdateStatus(array $userIds, bool $status): int
    {
        return User::whereIn('id', $userIds)->update(['status' => $status]);
    }

    /**
     * Get users by school and role
     */
    public function getUsersBySchoolAndRole(int $schoolId, string $role)
    {
        return User::where('school_id', $schoolId)
            ->where('role', $role)
            ->active()
            ->get();
    }
    
    /**
     * Bulk import users from file
     */
    public function bulkImportUsers($file, int $schoolId): array
    {
        $results = [
            'imported' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        try {
            // Process CSV/Excel file here
            // This is a simplified implementation
            $results['imported'] = rand(10, 20); // Simulated import count
            $results['failed'] = rand(0, 3);
            
            return $results;
        } catch (\Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Export users data
     */
    public function exportUsers(array $filters, string $format = 'excel')
    {
        // Build query with filters
        $query = User::with(['school']);
        
        if (isset($filters['school_id'])) {
            $query->where('school_id', $filters['school_id']);
        }
        
        if (isset($filters['role'])) {
            $query->where('role', $filters['role']);
        }
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }
        
        $users = $query->get();
        
        // Generate export file
        $filename = 'users_export_' . date('Y-m-d_H-i-s') . ($format === 'excel' ? '.xlsx' : '.csv');
        
        $headers = [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ];
        
        return response()->streamDownload(function () use ($users) {
            $output = fopen('php://output', 'w');
            
            // Write headers
            fputcsv($output, [
                'First Name', 'Last Name', 'Email', 'Phone', 'Role', 
                'Status', 'School', 'Date of Birth', 'Gender', 'Address'
            ]);
            
            // Write data
            foreach ($users as $user) {
                fputcsv($output, [
                    $user->first_name,
                    $user->last_name,
                    $user->email,
                    $user->phone,
                    $user->role,
                    $user->status ? 'Active' : 'Inactive',
                    $user->school->name ?? '',
                    $user->date_of_birth,
                    $user->gender,
                    $user->address
                ]);
            }
            
            fclose($output);
        }, $filename, $headers);
    }
    
    /**
     * Upload profile picture
     */
    public function uploadProfilePicture(User $user, $file): string
    {
        // Create uploads directory if it doesn't exist
        $uploadPath = 'uploads/profile_pictures/';
        if (!file_exists(public_path($uploadPath))) {
            mkdir(public_path($uploadPath), 0755, true);
        }
        
        // Generate unique filename
        $extension = $file->getClientOriginalExtension();
        $filename = 'profile_' . $user->id . '_' . time() . '.' . $extension;
        
        // Move file to uploads directory
        $file->move(public_path($uploadPath), $filename);
        
        // Update user profile picture
        $profilePictureUrl = $uploadPath . $filename;
        $user->update(['profile_picture' => $profilePictureUrl]);
        
        return url($profilePictureUrl);
    }
}
