<?php

namespace App\Modules\HR\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HR\Controllers\EmployeeController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * HRController - Main HR module controller
 * 
 * This controller acts as a wrapper for HR-related operations,
 * primarily delegating employee management to EmployeeController.
 */
class HRController extends Controller
{
    protected $employeeController;

    public function __construct(EmployeeController $employeeController)
    {
        $this->employeeController = $employeeController;
    }

    /**
     * Display a listing of employees
     */
    public function index(Request $request): JsonResponse
    {
        return $this->employeeController->index($request);
    }

    /**
     * Store a newly created employee
     */
    public function store(Request $request): JsonResponse
    {
        return $this->employeeController->store($request);
    }

    /**
     * Display the specified employee
     */
    public function show($employee): JsonResponse
    {
        return $this->employeeController->show($employee);
    }

    /**
     * Update the specified employee
     */
    public function update(Request $request, $employee): JsonResponse
    {
        return $this->employeeController->update($request, $employee);
    }

    /**
     * Remove the specified employee
     */
    public function destroy($employee): JsonResponse
    {
        return $this->employeeController->destroy($employee);
    }
}