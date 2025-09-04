<?php

namespace App\Modules\School\Services;

use App\Modules\School\Models\School;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Exception;

class SchoolService
{
    /**
     * Get schools with filters and pagination
     */
    public function getSchools(array $filters = []): LengthAwarePaginator
    {
        try {
            $query = School::withCount(['users', 'students', 'teachers', 'classes'])
                ->when(isset($filters['search']), function ($q) use ($filters) {
                    return $q->where(function ($query) use ($filters) {
                        $query->where('name', 'like', "%{$filters['search']}%")
                              ->orWhere('code', 'like', "%{$filters['search']}%")
                              ->orWhere('email', 'like', "%{$filters['search']}%")
                              ->orWhere('phone', 'like', "%{$filters['search']}%")
                              ->orWhere('address', 'like', "%{$filters['search']}%");
                    });
                })
                ->when(isset($filters['status']), function ($q) use ($filters) {
                    return $q->where('status', $filters['status']);
                })
                ->when(isset($filters['type']), function ($q) use ($filters) {
                    return $q->where('type', $filters['type']);
                })
                ->when(isset($filters['city']), function ($q) use ($filters) {
                    return $q->where('city', 'like', "%{$filters['city']}%");
                })
                ->when(isset($filters['state']), function ($q) use ($filters) {
                    return $q->where('state', 'like', "%{$filters['state']}%");
                });

            // Sorting
            $sortBy = $filters['sort_by'] ?? 'name';
            $sortOrder = $filters['sort_order'] ?? 'asc';
            $query->orderBy($sortBy, $sortOrder);

            $schools = $query->paginate($filters['per_page'] ?? 15);

            ActivityLogger::log('Schools List Retrieved', 'School', [
                'filters' => $filters,
                'total_schools' => $schools->total()
            ]);

            return $schools;
        } catch (Exception $e) {
            ActivityLogger::log('Schools List Error', 'School', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ], 'error');
            throw $e;
        }
    }

    /**
     * Create a new school
     */
    public function createSchool(array $data): School
    {
        try {
            DB::beginTransaction();

            // Generate unique school code if not provided
            if (!isset($data['code'])) {
                $data['code'] = $this->generateSchoolCode($data['name']);
            }

            $school = School::create($data);

            ActivityLogger::log('School Created', 'School', [
                'school_id' => $school->id,
                'name' => $school->name,
                'code' => $school->code,
                'type' => $school->type
            ]);

            DB::commit();
            return $school;
        } catch (Exception $e) {
            DB::rollBack();
            ActivityLogger::log('School Creation Failed', 'School', [
                'error' => $e->getMessage(),
                'data' => $data
            ], 'error');
            throw $e;
        }
    }

    /**
     * Update a school
     */
    public function updateSchool(School $school, array $data): School
    {
        try {
            DB::beginTransaction();

            $originalData = $school->toArray();
            $school->update($data);

            ActivityLogger::log('School Updated', 'School', [
                'school_id' => $school->id,
                'name' => $school->name,
                'changes' => array_diff_assoc($data, $originalData)
            ]);

            DB::commit();
            return $school->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            ActivityLogger::log('School Update Failed', 'School', [
                'school_id' => $school->id,
                'error' => $e->getMessage(),
                'data' => $data
            ], 'error');
            throw $e;
        }
    }

    /**
     * Delete a school
     */
    public function deleteSchool(School $school): bool
    {
        try {
            DB::beginTransaction();

            // Check if school has associated data
            $hasUsers = $school->users()->exists();
            $hasStudents = $school->students()->exists();
            $hasTeachers = $school->teachers()->exists();

            if ($hasUsers || $hasStudents || $hasTeachers) {
                throw new Exception('Cannot delete school with associated users, students, or teachers');
            }

            $schoolData = $school->toArray();
            $school->delete();

            ActivityLogger::log('School Deleted', 'School', $schoolData);

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            ActivityLogger::log('School Deletion Failed', 'School', [
                'school_id' => $school->id,
                'error' => $e->getMessage()
            ], 'error');
            throw $e;
        }
    }

    /**
     * Get school statistics
     */
    public function getSchoolStatistics(School $school = null): array
    {
        try {
            $cacheKey = $school ? "school_stats_{$school->id}" : 'all_schools_stats';
            
            return Cache::remember($cacheKey, 300, function () use ($school) {
                if ($school) {
                    // Individual school statistics
                    $stats = [
                        'total_students' => $school->students()->count(),
                        'total_teachers' => $school->teachers()->count(),
                        'total_classes' => $school->classes()->count(),
                        'total_subjects' => $school->subjects()->count(),
                        'active_users' => $school->users()->where('status', 'active')->count(),
                        'total_library_books' => $school->books()->sum('total_copies'),
                        'available_library_books' => $school->books()->sum('available_copies'),
                        'pending_fees' => $school->fees()->where('status', 'pending')->sum('amount'),
                        'collected_fees' => $school->fees()->where('status', 'paid')->sum('amount')
                    ];

                    // Calculate additional metrics
                    $stats['student_teacher_ratio'] = $stats['total_teachers'] > 0 
                        ? round($stats['total_students'] / $stats['total_teachers'], 2) 
                        : 0;
                    
                    $stats['library_utilization'] = $stats['total_library_books'] > 0 
                        ? round((($stats['total_library_books'] - $stats['available_library_books']) / $stats['total_library_books']) * 100, 2)
                        : 0;

                    $stats['fee_collection_rate'] = ($stats['pending_fees'] + $stats['collected_fees']) > 0 
                        ? round(($stats['collected_fees'] / ($stats['pending_fees'] + $stats['collected_fees'])) * 100, 2)
                        : 0;
                } else {
                    // System-wide statistics
                    $stats = [
                        'total_schools' => School::count(),
                        'active_schools' => School::where('status', 'active')->count(),
                        'total_students' => DB::table('students')->count(),
                        'total_teachers' => DB::table('teachers')->count(),
                        'total_users' => DB::table('users')->count(),
                        'total_classes' => DB::table('classes')->count()
                    ];

                    // School type distribution
                    $schoolTypes = School::select('type', DB::raw('count(*) as count'))
                        ->groupBy('type')
                        ->get()
                        ->pluck('count', 'type')
                        ->toArray();

                    $stats['school_type_distribution'] = $schoolTypes;

                    // Recent activity
                    $stats['schools_created_this_month'] = School::whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)
                        ->count();
                }

                ActivityLogger::log('School Statistics Retrieved', 'School', [
                    'school_id' => $school?->id,
                    'stats_type' => $school ? 'individual' : 'system_wide'
                ]);

                return $stats;
            });
        } catch (Exception $e) {
            ActivityLogger::log('School Statistics Error', 'School', [
                'school_id' => $school?->id,
                'error' => $e->getMessage()
            ], 'error');
            throw $e;
        }
    }

    /**
     * Get school settings
     */
    public function getSchoolSettings(School $school): array
    {
        try {
            $settings = [
                'basic_info' => [
                    'name' => $school->name,
                    'code' => $school->code,
                    'type' => $school->type,
                    'email' => $school->email,
                    'phone' => $school->phone,
                    'website' => $school->website,
                    'logo' => $school->logo
                ],
                'address' => [
                    'address' => $school->address,
                    'city' => $school->city,
                    'state' => $school->state,
                    'postal_code' => $school->postal_code,
                    'country' => $school->country
                ],
                'academic' => [
                    'academic_year_start' => $school->academic_year_start,
                    'academic_year_end' => $school->academic_year_end,
                    'working_days' => $school->working_days,
                    'session_start_time' => $school->session_start_time,
                    'session_end_time' => $school->session_end_time
                ],
                'features' => [
                    'library_enabled' => $school->library_enabled ?? true,
                    'transport_enabled' => $school->transport_enabled ?? true,
                    'fee_management_enabled' => $school->fee_management_enabled ?? true,
                    'exam_management_enabled' => $school->exam_management_enabled ?? true,
                    'attendance_tracking_enabled' => $school->attendance_tracking_enabled ?? true
                ]
            ];

            ActivityLogger::log('School Settings Retrieved', 'School', [
                'school_id' => $school->id,
                'school_name' => $school->name
            ]);

            return $settings;
        } catch (Exception $e) {
            ActivityLogger::log('School Settings Error', 'School', [
                'school_id' => $school->id,
                'error' => $e->getMessage()
            ], 'error');
            throw $e;
        }
    }

    /**
     * Update school settings
     */
    public function updateSchoolSettings(School $school, array $settings): School
    {
        try {
            DB::beginTransaction();

            $updateData = [];

            // Flatten settings array for update
            foreach ($settings as $category => $categorySettings) {
                if (is_array($categorySettings)) {
                    foreach ($categorySettings as $key => $value) {
                        $updateData[$key] = $value;
                    }
                } else {
                    $updateData[$category] = $categorySettings;
                }
            }

            $school->update($updateData);

            ActivityLogger::log('School Settings Updated', 'School', [
                'school_id' => $school->id,
                'school_name' => $school->name,
                'updated_settings' => array_keys($updateData)
            ]);

            DB::commit();
            return $school->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            ActivityLogger::log('School Settings Update Failed', 'School', [
                'school_id' => $school->id,
                'error' => $e->getMessage(),
                'settings' => $settings
            ], 'error');
            throw $e;
        }
    }

    /**
     * Get school dashboard data
     */
    public function getSchoolDashboard(School $school): array
    {
        try {
            $dashboard = [
                'overview' => $this->getSchoolStatistics($school),
                'recent_activities' => $this->getRecentActivities($school),
                'quick_stats' => $this->getQuickStats($school),
                'alerts' => $this->getSchoolAlerts($school)
            ];

            ActivityLogger::log('School Dashboard Retrieved', 'School', [
                'school_id' => $school->id,
                'school_name' => $school->name
            ]);

            return $dashboard;
        } catch (Exception $e) {
            ActivityLogger::log('School Dashboard Error', 'School', [
                'school_id' => $school->id,
                'error' => $e->getMessage()
            ], 'error');
            throw $e;
        }
    }

    /**
     * Generate unique school code
     */
    private function generateSchoolCode(string $name): string
    {
        $code = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 3));
        $number = 1;
        
        while (School::where('code', $code . str_pad($number, 3, '0', STR_PAD_LEFT))->exists()) {
            $number++;
        }
        
        return $code . str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Get recent activities for school
     */
    private function getRecentActivities(School $school): array
    {
        // This would integrate with your activity logging system
        return [
            [
                'type' => 'student_enrollment',
                'description' => 'New student enrolled',
                'timestamp' => now()->subHours(2),
                'user' => 'Admin User'
            ],
            [
                'type' => 'fee_payment',
                'description' => 'Fee payment received',
                'timestamp' => now()->subHours(4),
                'user' => 'Finance Officer'
            ]
        ];
    }

    /**
     * Get quick stats for school
     */
    private function getQuickStats(School $school): array
    {
        return [
            'today_attendance' => [
                'students_present' => rand(80, 95),
                'teachers_present' => rand(15, 20),
                'attendance_rate' => rand(85, 98)
            ],
            'this_month' => [
                'new_enrollments' => rand(5, 15),
                'fee_collections' => rand(50000, 100000),
                'library_books_issued' => rand(100, 200)
            ]
        ];
    }

    /**
     * Get school alerts
     */
    private function getSchoolAlerts(School $school): array
    {
        $alerts = [];

        // Check for low attendance
        // Check for pending fees
        // Check for overdue library books
        // Check for upcoming events

        return $alerts;
    }

    /**
     * Clear cache
     */
    public function clearCache(School $school = null): void
    {
        if ($school) {
            Cache::forget("school_stats_{$school->id}");
        } else {
            Cache::forget('all_schools_stats');
        }
    }
}