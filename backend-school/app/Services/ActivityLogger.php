<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ActivityLogger
{
    /**
     * Log user activity
     */
    public static function log(string $action, string $module, array $data = [], string $level = 'info', ?string $channel = null): void
    {
        $user = Auth::user();
        $request = request();
        
        $logData = [
            'timestamp' => Carbon::now()->toISOString(),
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'user_role' => $user?->role,
            'user_school_id' => $user?->school_id,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'module' => $module,
            'action' => $action,
            'url' => $request?->fullUrl(),
            'method' => $request?->method(),
            'data' => self::sanitizeData($data),
            'session_id' => session()->getId(),
        ];

        $message = sprintf(
            '[%s] [%s] User %s (%s) performed %s in %s module',
            $logData['timestamp'],
            strtoupper($level),
            $user?->email ?? 'Guest',
            $user?->role ?? 'Unknown',
            $action,
            $module
        );

        // Always log to the default Laravel log channel (laravel.log)
        Log::log($level, $message, $logData);
    }

    /**
     * Log authentication events
     */
    public static function logAuth(string $action, array $data = [], string $level = 'info'): void
    {
        self::log($action, 'Auth', $data, $level, 'auth');
    }

    /**
     * Log security events
     */
    public static function logSecurity(string $action, array $data = [], string $level = 'warning'): void
    {
        self::log($action, 'Security', $data, $level, 'security');
    }

    /**
     * Log school module activities
     */
    public static function logSchool(string $action, array $data = [], string $level = 'info'): void
    {
        self::log($action, 'Schools', $data, $level, 'schools');
    }

    /**
     * Log user module activities
     */
    public static function logUser(string $action, array $data = [], string $level = 'info'): void
    {
        self::log($action, 'Users', $data, $level, 'users');
    }

    /**
     * Log student module activities
     */
    public static function logStudent(string $action, array $data = [], string $level = 'info'): void
    {
        self::log($action, 'Students', $data, $level, 'students');
    }

    /**
     * Log API requests
     */
    public static function logRequest(Request $request, $response = null): void
    {
        $user = Auth::user();
        
        $logData = [
            'timestamp' => Carbon::now()->toISOString(),
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'user_role' => $user?->role,
            'ip_address' => $request->ip(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'route' => $request->route()?->getName(),
            'parameters' => self::sanitizeData($request->route()?->parameters() ?? []),
            'query_params' => self::sanitizeData($request->query()),
            'request_body' => self::sanitizeData($request->except(['password', 'password_confirmation', 'current_password'])),
            'response_status' => $response?->getStatusCode(),
            'execution_time' => defined('LARAVEL_START') ? round((microtime(true) - LARAVEL_START) * 1000, 2) . 'ms' : null,
        ];

        $message = sprintf(
            '[API] %s %s - Status: %s - User: %s',
            $request->method(),
            $request->path(),
            $response?->getStatusCode() ?? 'Unknown',
            $user?->email ?? 'Guest'
        );

        // Log to the default Laravel log channel (laravel.log)
        Log::info($message, $logData);
    }

    /**
     * Log failed authorization attempts
     */
    public static function logUnauthorized(string $action, string $resource, array $data = []): void
    {
        $user = Auth::user();
        
        $logData = array_merge($data, [
            'attempted_action' => $action,
            'resource' => $resource,
            'user_permissions' => $user?->role,
        ]);

        self::logSecurity("Unauthorized access attempt: {$action} on {$resource}", $logData, 'warning');
    }

    /**
     * Log validation failures
     */
    public static function logValidationFailure(string $action, array $errors, array $data = []): void
    {
        $logData = array_merge($data, [
            'validation_errors' => $errors,
        ]);

        self::log("Validation failed for {$action}", 'Validation', $logData, 'warning');
    }

    /**
     * Log database operations
     */
    public static function logDatabaseOperation(string $operation, string $model, $modelId = null, array $data = []): void
    {
        $logData = array_merge($data, [
            'operation' => $operation,
            'model' => $model,
            'model_id' => $modelId,
        ]);

        self::log("Database {$operation}: {$model}" . ($modelId ? " (ID: {$modelId})" : ''), 'Database', $logData);
    }

    /**
     * Sanitize sensitive data from logs
     */
    private static function sanitizeData(array $data): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'current_password',
            'token',
            'api_token',
            'remember_token',
            'credit_card',
            'ssn',
            'social_security',
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }

        // Recursively sanitize nested arrays
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::sanitizeData($value);
            }
        }

        return $data;
    }

    /**
     * Get log statistics
     */
    public static function getLogStats(string $module = null, int $days = 7): array
    {
        $logFile = storage_path('logs/laravel.log');
        
        if (!file_exists($logFile)) {
            return [
                'total_entries' => 0,
                'error_count' => 0,
                'warning_count' => 0,
                'info_count' => 0,
                'debug_count' => 0,
            ];
        }

        // This is a basic implementation - for production, consider using a proper log parser
        $content = file_get_contents($logFile);
        $lines = explode("\n", $content);
        
        // Filter by module if specified
        if ($module) {
            $filteredLines = array_filter($lines, function($line) use ($module) {
                return strpos($line, "in {$module} module") !== false;
            });
            $lines = $filteredLines;
            $content = implode("\n", $lines);
        }
        
        $stats = [
            'total_entries' => count(array_filter($lines)),
            'error_count' => substr_count($content, '.ERROR:'),
            'warning_count' => substr_count($content, '.WARNING:'),
            'info_count' => substr_count($content, '.INFO:'),
            'debug_count' => substr_count($content, '.DEBUG:'),
        ];

        return $stats;
    }

    /**
     * Clean old log files
     */
    public static function cleanOldLogs(int $days = 30): int
    {
        $logPath = storage_path('logs');
        $files = glob($logPath . '/*.log');
        $deletedCount = 0;
        $cutoffDate = Carbon::now()->subDays($days);

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffDate->timestamp) {
                if (unlink($file)) {
                    $deletedCount++;
                }
            }
        }

        return $deletedCount;
    }
}