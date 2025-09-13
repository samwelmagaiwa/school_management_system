<?php

// Script to migrate models from modular structure to standard Laravel structure

$modelsToMigrate = [
    'app/Modules/Attendance/Models/Attendance.php' => 'app/Models/Attendance.php',
    'app/Modules/Attendance/Models/AttendanceSummary.php' => 'app/Models/AttendanceSummary.php',
    'app/Modules/Class/Models/SchoolClass.php' => 'app/Models/SchoolClass.php',
    'app/Modules/Exam/Models/Exam.php' => 'app/Models/Exam.php',
    'app/Modules/Exam/Models/ExamResult.php' => 'app/Models/ExamResult.php',
    'app/Modules/Fee/Models/Fee.php' => 'app/Models/Fee.php',
    'app/Modules/HR/Models/Department.php' => 'app/Models/Department.php',
    'app/Modules/HR/Models/Employee.php' => 'app/Models/Employee.php',
    'app/Modules/HR/Models/LeaveRequest.php' => 'app/Models/LeaveRequest.php',
    'app/Modules/HR/Models/LeaveType.php' => 'app/Models/LeaveType.php',
    'app/Modules/HR/Models/Payroll.php' => 'app/Models/Payroll.php',
    'app/Modules/HR/Models/Position.php' => 'app/Models/Position.php',
    'app/Modules/IDCard/Models/IDCard.php' => 'app/Models/IDCard.php',
    'app/Modules/Library/Models/Book.php' => 'app/Models/Book.php',
    'app/Modules/School/Models/School.php' => 'app/Models/School.php',
    'app/Modules/Student/Models/Student.php' => 'app/Models/Student.php',
    'app/Modules/Subject/Models/Subject.php' => 'app/Models/Subject.php',
    'app/Modules/SuperAdmin/Models/Permission.php' => 'app/Models/Permission.php',
    'app/Modules/SuperAdmin/Models/Role.php' => 'app/Models/Role.php',
    'app/Modules/SuperAdmin/Models/SubscriptionPlan.php' => 'app/Models/SubscriptionPlan.php',
    'app/Modules/SuperAdmin/Models/SystemSetting.php' => 'app/Models/SystemSetting.php',
    'app/Modules/SuperAdmin/Models/Tenant.php' => 'app/Models/Tenant.php',
    'app/Modules/SuperAdmin/Models/TenantPermission.php' => 'app/Models/TenantPermission.php',
    'app/Modules/Teacher/Models/Teacher.php' => 'app/Models/Teacher.php',
    'app/Modules/Transport/Models/Driver.php' => 'app/Models/Driver.php',
    'app/Modules/Transport/Models/FuelRecord.php' => 'app/Models/FuelRecord.php',
    'app/Modules/Transport/Models/MaintenanceRecord.php' => 'app/Models/MaintenanceRecord.php',
    'app/Modules/Transport/Models/RouteStop.php' => 'app/Models/RouteStop.php',
    'app/Modules/Transport/Models/TransportRoute.php' => 'app/Models/TransportRoute.php',
    'app/Modules/Transport/Models/TripRecord.php' => 'app/Models/TripRecord.php',
    'app/Modules/Transport/Models/Vehicle.php' => 'app/Models/Vehicle.php',
    'app/Modules/User/Models/User.php' => 'app/Models/User.php',
];

foreach ($modelsToMigrate as $source => $destination) {
    if (file_exists($source)) {
        echo "Processing: $source -> $destination\n";
        
        $content = file_get_contents($source);
        
        // Update namespace from modular to standard Laravel
        $content = preg_replace(
            '/namespace App\\\\Modules\\\\[^\\\\]+\\\\Models;/',
            'namespace App\\Models;',
            $content
        );
        
        // Update use statements to refer to App\Models namespace
        $content = preg_replace(
            '/use App\\\\Modules\\\\[^\\\\]+\\\\Models\\\\([^;]+);/',
            'use App\\Models\\$1;',
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

echo "Model migration completed!\n";
