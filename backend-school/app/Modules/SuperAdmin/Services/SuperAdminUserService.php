<?php

namespace App\Modules\SuperAdmin\Services;

use App\Modules\User\Models\User;
use App\Modules\School\Models\School;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Carbon\Carbon;
// use Maatwebsite\Excel\Facades\Excel;
// use App\Exports\UsersExport;
// use App\Imports\UsersImport;

class SuperAdminUserService
{
    /**
     * Create a new user
     */
    public function createUser(array $userData): User
    {
        // Set default password to last_name if not provided
        if (!isset($userData['password'])) {
            $userData['password'] = $userData['last_name'];
        }

        $userData['password'] = Hash::make($userData['password']);
        $userData['status'] = $userData['status'] ?? true;

        return User::create($userData);
    }

    /**
     * Update an existing user
     */
    public function updateUser(User $user, array $userData): User
    {
        // Handle password update
        if (isset($userData['password']) && !empty($userData['password'])) {
            $userData['password'] = Hash::make($userData['password']);
        } else {
            unset($userData['password']);
        }

        $user->update($userData);
        return $user;
    }

    /**
     * Get comprehensive user statistics
     */
    public function getComprehensiveStatistics(): array
    {
        $totalUsers = User::count();
        $activeUsers = User::where('status', true)->count();
        $inactiveUsers = User::where('status', false)->count();
        
        // Users by role
        $usersByRole = User::select('role', DB::raw('count(*) as count'))
            ->groupBy('role')
            ->pluck('count', 'role')
            ->toArray();

        // Users by school
        $usersBySchool = User::join('schools', 'users.school_id', '=', 'schools.id')
            ->select('schools.name', DB::raw('count(*) as count'))
            ->groupBy('schools.id', 'schools.name')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();

        // Recent registrations (last 30 days)
        $recentRegistrations = User::where('created_at', '>=', Carbon::now()->subDays(30))->count();

        // Email verification stats
        $emailVerified = User::whereNotNull('email_verified_at')->count();
        $emailUnverified = User::whereNull('email_verified_at')->count();

        // Login activity (last 30 days)
        $activeInLast30Days = User::where('last_login_at', '>=', Carbon::now()->subDays(30))->count();
        $neverLoggedIn = User::whereNull('last_login_at')->count();

        // Growth statistics
        $growthData = $this->getUserGrowthData();

        return [
            'overview' => [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'inactive_users' => $inactiveUsers,
                'recent_registrations' => $recentRegistrations,
                'email_verified' => $emailVerified,
                'email_unverified' => $emailUnverified,
                'active_in_last_30_days' => $activeInLast30Days,
                'never_logged_in' => $neverLoggedIn
            ],
            'by_role' => $usersByRole,
            'by_school' => $usersBySchool,
            'growth_data' => $growthData
        ];
    }

    /**
     * Get user analytics data
     */
    public function getUserAnalytics(string $period): array
    {
        $startDate = $this->getStartDateForPeriod($period);
        
        // Registration trends
        $registrationTrends = User::where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();

        // Login activity trends
        $loginTrends = User::where('last_login_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(last_login_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();

        // Role distribution over time
        $roleDistribution = User::where('created_at', '>=', $startDate)
            ->select('role', DB::raw('COUNT(*) as count'))
            ->groupBy('role')
            ->get()
            ->toArray();

        // School activity
        $schoolActivity = User::join('schools', 'users.school_id', '=', 'schools.id')
            ->where('users.created_at', '>=', $startDate)
            ->select('schools.name', DB::raw('COUNT(*) as count'))
            ->groupBy('schools.id', 'schools.name')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();

        return [
            'period' => $period,
            'start_date' => $startDate->toDateString(),
            'registration_trends' => $registrationTrends,
            'login_trends' => $loginTrends,
            'role_distribution' => $roleDistribution,
            'school_activity' => $schoolActivity
        ];
    }

    /**
     * Reset user password to default (last_name)
     */
    public function resetPasswordToDefault(User $user): void
    {
        $user->update([
            'password' => Hash::make($user->last_name)
        ]);
    }

    /**
     * Perform bulk actions on users
     */
    public function performBulkAction(string $action, array $userIds, array $data = []): array
    {
        $users = User::whereIn('id', $userIds)->get();
        $results = [
            'success_count' => 0,
            'failed_count' => 0,
            'errors' => []
        ];

        foreach ($users as $user) {
            try {
                switch ($action) {
                    case 'activate':
                        if (!$user->isSuperAdmin()) {
                            $user->update(['status' => true]);
                            $results['success_count']++;
                        } else {
                            $results['errors'][] = "Cannot modify SuperAdmin user: {$user->email}";
                            $results['failed_count']++;
                        }
                        break;

                    case 'deactivate':
                        if (!$user->isSuperAdmin()) {
                            $user->update(['status' => false]);
                            $results['success_count']++;
                        } else {
                            $results['errors'][] = "Cannot modify SuperAdmin user: {$user->email}";
                            $results['failed_count']++;
                        }
                        break;

                    case 'reset_password':
                        if (!$user->isSuperAdmin()) {
                            $this->resetPasswordToDefault($user);
                            $results['success_count']++;
                        } else {
                            $results['errors'][] = "Cannot reset SuperAdmin password: {$user->email}";
                            $results['failed_count']++;
                        }
                        break;

                    case 'change_school':
                        if (!$user->isSuperAdmin() && isset($data['school_id'])) {
                            $user->update(['school_id' => $data['school_id']]);
                            $results['success_count']++;
                        } else {
                            $results['errors'][] = "Cannot change school for user: {$user->email}";
                            $results['failed_count']++;
                        }
                        break;

                    case 'delete':
                        if (!$user->isSuperAdmin()) {
                            $user->delete();
                            $results['success_count']++;
                        } else {
                            $results['errors'][] = "Cannot delete SuperAdmin user: {$user->email}";
                            $results['failed_count']++;
                        }
                        break;

                    default:
                        $results['errors'][] = "Unknown action: {$action}";
                        $results['failed_count']++;
                }
            } catch (\Exception $e) {
                $results['errors'][] = "Error processing user {$user->email}: " . $e->getMessage();
                $results['failed_count']++;
            }
        }

        return $results;
    }

    /**
     * Export users data
     */
    public function exportUsers(array $filters, string $format = 'excel')
    {
        $query = User::with(['school']);

        // Apply filters
        if (isset($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (isset($filters['school_id'])) {
            $query->where('school_id', $filters['school_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['date_range'])) {
            $dateRange = $filters['date_range'];
            if (isset($dateRange['start']) && isset($dateRange['end'])) {
                $query->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
            }
        }

        $users = $query->get();
        $filename = 'users_export_' . date('Y-m-d_H-i-s');

        if ($format === 'csv') {
            return $this->exportToCSV($users, $filename . '.csv');
        } else {
            // For now, return CSV for both formats until Excel package is installed
            return $this->exportToCSV($users, $filename . '.csv');
        }
    }

    /**
     * Export users to CSV format
     */
    private function exportToCSV($users, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($users) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($file, [
                'ID',
                'First Name',
                'Last Name',
                'Email',
                'Phone',
                'Role',
                'School',
                'Status',
                'Email Verified',
                'Last Login',
                'Created At',
                'Updated At'
            ]);

            // Add user data
            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->first_name,
                    $user->last_name,
                    $user->email,
                    $user->phone,
                    $user->role,
                    $user->school ? $user->school->name : 'No School',
                    $user->status ? 'Active' : 'Inactive',
                    $user->email_verified_at ? 'Yes' : 'No',
                    $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i:s') : 'Never',
                    $user->created_at->format('Y-m-d H:i:s'),
                    $user->updated_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import users from file
     */
    public function bulkImportUsers(UploadedFile $file, int $schoolId): array
    {
        // For now, return a placeholder response until Excel package is installed
        // TODO: Implement CSV import functionality or install Laravel Excel package
        
        return [
            'imported' => 0,
            'failed' => 0,
            'errors' => ['Import functionality requires Laravel Excel package to be installed']
        ];
    }

    /**
     * Upload profile picture for user
     */
    public function uploadProfilePicture(User $user, UploadedFile $file): string
    {
        // Delete old profile picture if exists
        if ($user->profile_picture) {
            Storage::disk('public')->delete($user->profile_picture);
        }

        // Store new profile picture
        $path = $file->store('profile_pictures', 'public');
        
        // Update user record
        $user->update(['profile_picture' => $path]);

        return Storage::disk('public')->url($path);
    }

    /**
     * Get user activity logs
     */
    public function getUserActivityLogs(User $user, array $filters = []): array
    {
        // This would integrate with your activity logging system
        // For now, returning a placeholder structure
        return [
            'logs' => [],
            'total' => 0,
            'pagination' => [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 15,
                'total' => 0
            ]
        ];
    }

    /**
     * Send password reset email to user
     */
    public function sendPasswordResetEmail(User $user): void
    {
        // Generate password reset token
        $token = \Str::random(60);
        
        // Store token in database (you might need to create a password_resets table)
        DB::table('password_resets')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($token),
                'created_at' => Carbon::now()
            ]
        );

        // Send email (implement your email template)
        // Mail::to($user->email)->send(new PasswordResetMail($user, $token));
    }

    /**
     * Create impersonation token for user
     */
    public function createImpersonationToken(User $user): string
    {
        // Create a special token for impersonation
        $token = $user->createToken('impersonation', ['impersonate'])->plainTextToken;
        
        return $token;
    }

    /**
     * Get user growth data for charts
     */
    private function getUserGrowthData(): array
    {
        $months = [];
        $data = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months[] = $date->format('M Y');
            
            $count = User::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
            
            $data[] = $count;
        }

        return [
            'labels' => $months,
            'data' => $data
        ];
    }

    /**
     * Get start date for analytics period
     */
    private function getStartDateForPeriod(string $period): Carbon
    {
        switch ($period) {
            case '7_days':
                return Carbon::now()->subDays(7);
            case '30_days':
                return Carbon::now()->subDays(30);
            case '90_days':
                return Carbon::now()->subDays(90);
            case '1_year':
                return Carbon::now()->subYear();
            default:
                return Carbon::now()->subDays(30);
        }
    }
}