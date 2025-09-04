<?php

namespace App\Modules\HR\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HR\Models\Department;
use App\Modules\HR\Requests\DepartmentRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DepartmentController extends Controller
{
    /**
     * Display a listing of departments
     */
    public function index(Request $request): JsonResponse
    {
        $departments = Department::with(['school', 'head', 'employees'])
            ->when($request->search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                           ->orWhere('code', 'like', "%{$search}%");
            })
            ->when($request->school_id, function ($query, $schoolId) {
                return $query->where('school_id', $schoolId);
            })
            ->paginate($request->per_page ?? 15);

        return response()->json($departments);
    }

    /**
     * Store a newly created department
     */
    public function store(DepartmentRequest $request): JsonResponse
    {
        $department = Department::create($request->validated());

        return response()->json([
            'message' => 'Department created successfully',
            'department' => $department->load(['school', 'head'])
        ], 201);
    }

    /**
     * Display the specified department
     */
    public function show(Department $department): JsonResponse
    {
        return response()->json([
            'department' => $department->load(['school', 'head', 'employees.user', 'positions'])
        ]);
    }

    /**
     * Update the specified department
     */
    public function update(DepartmentRequest $request, Department $department): JsonResponse
    {
        $department->update($request->validated());

        return response()->json([
            'message' => 'Department updated successfully',
            'department' => $department->load(['school', 'head'])
        ]);
    }

    /**
     * Remove the specified department
     */
    public function destroy(Department $department): JsonResponse
    {
        // Check if department has employees
        if ($department->employees()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete department with active employees'
            ], 422);
        }

        $department->delete();

        return response()->json([
            'message' => 'Department deleted successfully'
        ]);
    }

    /**
     * Get department statistics
     */
    public function statistics(Department $department): JsonResponse
    {
        $stats = [
            'total_employees' => $department->employees()->count(),
            'active_employees' => $department->employees()->where('employment_status', 'active')->count(),
            'positions_count' => $department->positions()->count(),
            'average_salary' => $department->employees()->avg('salary'),
            'gender_distribution' => $department->employees()
                ->selectRaw('gender, count(*) as count')
                ->groupBy('gender')
                ->pluck('count', 'gender')
                ->toArray(),
        ];

        return response()->json([
            'department' => $department->load(['school', 'head']),
            'statistics' => $stats
        ]);
    }

    /**
     * Assign department head
     */
    public function assignHead(Request $request, Department $department): JsonResponse
    {
        $request->validate([
            'head_id' => 'required|exists:employees,id',
        ]);

        $department->update(['head_id' => $request->head_id]);

        return response()->json([
            'message' => 'Department head assigned successfully',
            'department' => $department->load(['head.user'])
        ]);
    }
}