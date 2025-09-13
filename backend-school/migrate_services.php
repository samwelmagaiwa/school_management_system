<?php

// Script to migrate Services from modular structure to standard Laravel structure

$servicesToMigrate = [
    'app/Modules/Attendance/Services/AttendanceService.php' => 'app/Services/AttendanceService.php',
    'app/Modules/Attendance/Services/QuickActionAttendanceService.php' => 'app/Services/QuickActionAttendanceService.php',
    'app/Modules/Class/Services/ClassService.php' => 'app/Services/ClassService.php',
    'app/Modules/Class/Services/QuickActionClassService.php' => 'app/Services/QuickActionClassService.php',
    'app/Modules/Dashboard/Services/DashboardService.php' => 'app/Services/DashboardService.php',
    'app/Modules/Exam/Services/ExamService.php' => 'app/Services/ExamService.php',
    'app/Modules/Fee/Services/FeeService.php' => 'app/Services/FeeService.php',
    'app/Modules/HR/Services/HRService.php' => 'app/Services/HRService.php',
    'app/Modules/HR/Services/LeaveService.php' => 'app/Services/LeaveService.php',
    'app/Modules/HR/Services/PayrollService.php' => 'app/Services/PayrollService.php',
    'app/Modules/IDCard/Services/IDCardGenerator.php' => 'app/Services/IDCardGenerator.php',
    'app/Modules/IDCard/Services/IDCardService.php' => 'app/Services/IDCardService.php',
    'app/Modules/Library/Services/LibraryService.php' => 'app/Services/LibraryService.php',
    'app/Modules/School/Services/SchoolService.php' => 'app/Services/SchoolService.php',
    'app/Modules/Student/Services/QuickActionStudentService.php' => 'app/Services/QuickActionStudentService.php',
    'app/Modules/Student/Services/StudentService.php' => 'app/Services/StudentService.php',
    'app/Modules/Subject/Services/SubjectService.php' => 'app/Services/SubjectService.php',
    'app/Modules/SuperAdmin/Services/SubscriptionPlanService.php' => 'app/Services/SubscriptionPlanService.php',
    'app/Modules/SuperAdmin/Services/SuperAdminService.php' => 'app/Services/SuperAdminService.php',
    'app/Modules/SuperAdmin/Services/SuperAdminUserService.php' => 'app/Services/SuperAdminUserService.php',
    'app/Modules/SuperAdmin/Services/TenantService.php' => 'app/Services/TenantService.php',
    'app/Modules/Teacher/Services/QuickActionTeacherService.php' => 'app/Services/QuickActionTeacherService.php',
    'app/Modules/Teacher/Services/TeacherService.php' => 'app/Services/TeacherService.php',
    'app/Modules/Transport/Services/TransportService.php' => 'app/Services/TransportService.php',
    'app/Modules/User/Services/UserService.php' => 'app/Services/UserService.php',
];

foreach ($servicesToMigrate as $source => $destination) {
    if (file_exists($source)) {
        echo "Processing: $source -> $destination\n";
        
        $content = file_get_contents($source);
        
        // Update namespace from modular to standard Laravel
        $content = preg_replace(
            '/namespace App\\\\Modules\\\\[^\\\\]+\\\\Services;/',
            'namespace App\\Services;',
            $content
        );
        
        // Update use statements to refer to App\Models namespace
        $content = preg_replace(
            '/use App\\\\Modules\\\\[^\\\\]+\\\\Models\\\\([^;]+);/',
            'use App\\Models\\$1;',
            $content
        );
        
        // Update use statements for Services
        $content = preg_replace(
            '/use App\\\\Modules\\\\[^\\\\]+\\\\Services\\\\([^;]+);/',
            'use App\\Services\\$1;',
            $content
        );
        
        // Update use statements for Requests
        $content = preg_replace(
            '/use App\\\\Modules\\\\[^\\\\]+\\\\Requests\\\\([^;]+);/',
            'use App\\Http\\Requests\\$1;',
            $content
        );
        
        // Ensure destination directory exists
        $destinationDir = dirname($destination);
        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }
        
        // Write the updated content
        file_put_contents($destination, $content);
        
        echo "Successfully migrated: " . basename($source) . "\n";
    } else {
        echo "Source file not found: $source\n";
    }
}

echo "Service migration completed!\n";
