<?php

// Script to migrate Form Requests from modular structure to standard Laravel structure

$requestsToMigrate = [
    'app/Modules/Attendance/Requests/AttendanceRequest.php' => 'app/Http/Requests/AttendanceRequest.php',
    'app/Modules/Attendance/Requests/BulkAttendanceRequest.php' => 'app/Http/Requests/BulkAttendanceRequest.php',
    'app/Modules/Attendance/Requests/QuickTakeAttendanceRequest.php' => 'app/Http/Requests/QuickTakeAttendanceRequest.php',
    'app/Modules/Auth/Requests/LoginRequest.php' => 'app/Http/Requests/LoginRequest.php',
    'app/Modules/Auth/Requests/RegisterRequest.php' => 'app/Http/Requests/RegisterRequest.php',
    'app/Modules/Class/Requests/ClassRequest.php' => 'app/Http/Requests/ClassRequest.php',
    'app/Modules/Class/Requests/QuickCreateClassRequest.php' => 'app/Http/Requests/QuickCreateClassRequest.php',
    'app/Modules/Exam/Requests/ExamRequest.php' => 'app/Http/Requests/ExamRequest.php',
    'app/Modules/Exam/Requests/ExamResultRequest.php' => 'app/Http/Requests/ExamResultRequest.php',
    'app/Modules/Fee/Requests/FeeRequest.php' => 'app/Http/Requests/FeeRequest.php',
    'app/Modules/Fee/Requests/StoreFeeRequest.php' => 'app/Http/Requests/StoreFeeRequest.php',
    'app/Modules/Fee/Requests/UpdateFeeRequest.php' => 'app/Http/Requests/UpdateFeeRequest.php',
    'app/Modules/HR/Requests/DepartmentRequest.php' => 'app/Http/Requests/DepartmentRequest.php',
    'app/Modules/HR/Requests/EmployeeRequest.php' => 'app/Http/Requests/EmployeeRequest.php',
    'app/Modules/HR/Requests/LeaveRequestRequest.php' => 'app/Http/Requests/LeaveRequestRequest.php',
    'app/Modules/HR/Requests/PayrollRequest.php' => 'app/Http/Requests/PayrollRequest.php',
    'app/Modules/IDCard/Requests/GenerateIDRequest.php' => 'app/Http/Requests/GenerateIDRequest.php',
    'app/Modules/Library/Requests/BookRequest.php' => 'app/Http/Requests/BookRequest.php',
    'app/Modules/School/Requests/SchoolRequest.php' => 'app/Http/Requests/SchoolRequest.php',
    'app/Modules/School/Requests/StoreSchoolRequest.php' => 'app/Http/Requests/StoreSchoolRequest.php',
    'app/Modules/School/Requests/UpdateSchoolRequest.php' => 'app/Http/Requests/UpdateSchoolRequest.php',
    'app/Modules/Student/Requests/QuickAddStudentRequest.php' => 'app/Http/Requests/QuickAddStudentRequest.php',
    'app/Modules/Student/Requests/StoreStudentRequest.php' => 'app/Http/Requests/StoreStudentRequest.php',
    'app/Modules/Student/Requests/StudentRequest.php' => 'app/Http/Requests/StudentRequest.php',
    'app/Modules/Student/Requests/UpdateStudentRequest.php' => 'app/Http/Requests/UpdateStudentRequest.php',
    'app/Modules/Subject/Requests/StoreSubjectRequest.php' => 'app/Http/Requests/StoreSubjectRequest.php',
    'app/Modules/Subject/Requests/SubjectRequest.php' => 'app/Http/Requests/SubjectRequest.php',
    'app/Modules/Subject/Requests/UpdateSubjectRequest.php' => 'app/Http/Requests/UpdateSubjectRequest.php',
    'app/Modules/SuperAdmin/Requests/RoleRequest.php' => 'app/Http/Requests/RoleRequest.php',
    'app/Modules/SuperAdmin/Requests/StoreSubscriptionPlanRequest.php' => 'app/Http/Requests/StoreSubscriptionPlanRequest.php',
    'app/Modules/SuperAdmin/Requests/StoreTenantRequest.php' => 'app/Http/Requests/StoreTenantRequest.php',
    'app/Modules/SuperAdmin/Requests/SuperAdminUserBulkRequest.php' => 'app/Http/Requests/SuperAdminUserBulkRequest.php',
    'app/Modules/SuperAdmin/Requests/SuperAdminUserStoreRequest.php' => 'app/Http/Requests/SuperAdminUserStoreRequest.php',
    'app/Modules/SuperAdmin/Requests/SuperAdminUserUpdateRequest.php' => 'app/Http/Requests/SuperAdminUserUpdateRequest.php',
    'app/Modules/SuperAdmin/Requests/TenantPermissionRequest.php' => 'app/Http/Requests/TenantPermissionRequest.php',
    'app/Modules/SuperAdmin/Requests/UpdateSubscriptionPlanRequest.php' => 'app/Http/Requests/UpdateSubscriptionPlanRequest.php',
    'app/Modules/SuperAdmin/Requests/UpdateTenantRequest.php' => 'app/Http/Requests/UpdateTenantRequest.php',
    'app/Modules/Teacher/Requests/QuickAddTeacherRequest.php' => 'app/Http/Requests/QuickAddTeacherRequest.php',
    'app/Modules/Teacher/Requests/TeacherRequest.php' => 'app/Http/Requests/TeacherRequest.php',
    'app/Modules/Transport/Requests/VehicleRequest.php' => 'app/Http/Requests/VehicleRequest.php',
    'app/Modules/User/Requests/CreateUserRequest.php' => 'app/Http/Requests/CreateUserRequest.php',
    'app/Modules/User/Requests/StoreUserRequest.php' => 'app/Http/Requests/StoreUserRequest.php',
    'app/Modules/User/Requests/UpdateUserRequest.php' => 'app/Http/Requests/UpdateUserRequest.php',
];

foreach ($requestsToMigrate as $source => $destination) {
    if (file_exists($source)) {
        echo "Processing: $source -> $destination\n";
        
        $content = file_get_contents($source);
        
        // Update namespace from modular to standard Laravel
        $content = preg_replace(
            '/namespace App\\\\Modules\\\\[^\\\\]+\\\\Requests;/',
            'namespace App\\Http\\Requests;',
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

echo "Request migration completed!\n";
