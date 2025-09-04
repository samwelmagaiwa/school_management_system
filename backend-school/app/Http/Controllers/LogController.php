<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;

class LogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get log statistics
     */
    public function getStats(Request $request): JsonResponse
    {
        // Only SuperAdmin and Admin can view logs
        if (!Gate::allows('view-reports')) {
            ActivityLogger::logSecurity('Unauthorized log access attempt', [
                'attempted_action' => 'view_log_stats'
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $channel = $request->get('channel', 'activity');
        $days = $request->get('days', 7);
        
        $stats = ActivityLogger::getLogStats($channel, $days);
        
        ActivityLogger::log('Log Statistics Viewed', 'System', [
            'channel' => $channel,
            'days' => $days
        ]);
        
        return response()->json([
            'success' => true,
            'data' => [
                'channel' => $channel,
                'period_days' => $days,
                'statistics' => $stats,
                'generated_at' => Carbon::now()->toISOString()
            ]
        ]);
    }

    /**
     * Get recent log entries
     */
    public function getRecentLogs(Request $request): JsonResponse
    {
        // Only SuperAdmin and Admin can view logs
        if (!Gate::allows('view-reports')) {
            ActivityLogger::logSecurity('Unauthorized log access attempt', [
                'attempted_action' => 'view_recent_logs'
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $channel = $request->get('channel', 'activity');
        $lines = $request->get('lines', 100);
        
        $logFile = storage_path("logs/{$channel}.log");
        
        if (!file_exists($logFile)) {
            return response()->json([
                'success' => false,
                'message' => 'Log file not found'
            ], 404);
        }

        // Get last N lines from log file
        $logs = $this->getLastLines($logFile, $lines);
        
        ActivityLogger::log('Recent Logs Viewed', 'System', [
            'channel' => $channel,
            'lines_requested' => $lines
        ]);
        
        return response()->json([
            'success' => true,
            'data' => [
                'channel' => $channel,
                'lines_count' => count($logs),
                'logs' => $logs,
                'retrieved_at' => Carbon::now()->toISOString()
            ]
        ]);
    }

    /**
     * Get available log channels
     */
    public function getChannels(): JsonResponse
    {
        // Only SuperAdmin and Admin can view logs
        if (!Gate::allows('view-reports')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $channels = [
            'activity' => 'General Activity',
            'auth' => 'Authentication Events',
            'schools' => 'School Management',
            'users' => 'User Management',
            'students' => 'Student Management',
            'security' => 'Security Events'
        ];
        
        return response()->json([
            'success' => true,
            'data' => $channels
        ]);
    }

    /**
     * Search logs
     */
    public function searchLogs(Request $request): JsonResponse
    {
        // Only SuperAdmin and Admin can view logs
        if (!Gate::allows('view-reports')) {
            ActivityLogger::logSecurity('Unauthorized log search attempt', [
                'attempted_action' => 'search_logs'
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $request->validate([
            'channel' => 'required|string',
            'search' => 'required|string|min:3',
            'lines' => 'integer|min:10|max:1000'
        ]);

        $channel = $request->get('channel');
        $search = $request->get('search');
        $lines = $request->get('lines', 100);
        
        $logFile = storage_path("logs/{$channel}.log");
        
        if (!file_exists($logFile)) {
            return response()->json([
                'success' => false,
                'message' => 'Log file not found'
            ], 404);
        }

        // Search in log file
        $matchingLogs = $this->searchInLogFile($logFile, $search, $lines);
        
        ActivityLogger::log('Log Search Performed', 'System', [
            'channel' => $channel,
            'search_term' => $search,
            'results_count' => count($matchingLogs)
        ]);
        
        return response()->json([
            'success' => true,
            'data' => [
                'channel' => $channel,
                'search_term' => $search,
                'results_count' => count($matchingLogs),
                'logs' => $matchingLogs,
                'searched_at' => Carbon::now()->toISOString()
            ]
        ]);
    }

    /**
     * Get last N lines from a file
     */
    private function getLastLines(string $filename, int $lines): array
    {
        $file = file($filename);
        return array_slice($file, -$lines);
    }

    /**
     * Search for a term in log file
     */
    private function searchInLogFile(string $filename, string $search, int $maxResults): array
    {
        $file = fopen($filename, 'r');
        $results = [];
        $count = 0;
        
        if ($file) {
            while (($line = fgets($file)) !== false && $count < $maxResults) {
                if (stripos($line, $search) !== false) {
                    $results[] = trim($line);
                    $count++;
                }
            }
            fclose($file);
        }
        
        return $results;
    }
}