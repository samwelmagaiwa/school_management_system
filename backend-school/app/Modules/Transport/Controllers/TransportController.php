<?php

namespace App\Modules\Transport\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Transport\Models\Vehicle;
use App\Modules\Transport\Models\TransportRoute;
use App\Modules\Transport\Models\Driver;
use App\Modules\Transport\Models\RouteStop;
use App\Modules\Transport\Requests\VehicleRequest;
use App\Modules\Transport\Requests\RouteRequest;
use App\Modules\Transport\Requests\DriverRequest;
use App\Modules\Transport\Services\TransportService;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TransportController extends Controller
{
    protected TransportService $transportService;

    public function __construct(TransportService $transportService)
    {
        $this->middleware('auth:sanctum');
        $this->transportService = $transportService;
    }

    // ==================== VEHICLE MANAGEMENT ====================

    /**
     * Display a listing of vehicles
     */
    public function indexVehicles(Request $request): JsonResponse
    {
        try {
            // Check authorization
            if (!in_array(auth()->user()->role, ['SuperAdmin', 'Admin', 'Teacher'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $query = Vehicle::with(['school', 'driver', 'route']);

            // Apply school scope for non-SuperAdmin users
            if (!auth()->user()->isSuperAdmin()) {
                $query->where('school_id', auth()->user()->school_id);
            }

            // Search functionality
            if ($request->filled('search')) {
                $query->search($request->search);
            }

            // School filter (only for SuperAdmin)
            if ($request->filled('school_id') && auth()->user()->isSuperAdmin()) {
                $query->where('school_id', $request->school_id);
            }

            // Vehicle type filter
            if ($request->filled('vehicle_type')) {
                $query->where('vehicle_type', $request->vehicle_type);
            }

            // Status filter
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Active filter
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'vehicle_number');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            $vehicles = $query->paginate($request->get('per_page', 15));

            ActivityLogger::log('Vehicles List Viewed', 'Transport', [
                'filters' => $request->only(['search', 'school_id', 'vehicle_type', 'status', 'is_active']),
                'total_results' => $vehicles->total()
            ]);

            return response()->json([
                'success' => true,
                'data' => $vehicles,
                'meta' => [
                    'current_page' => $vehicles->currentPage(),
                    'last_page' => $vehicles->lastPage(),
                    'per_page' => $vehicles->perPage(),
                    'total' => $vehicles->total(),
                ]
            ]);
        } catch (\Exception $e) {
            ActivityLogger::log('Vehicles List Error', 'Transport', [
                'error' => $e->getMessage()
            ], 'error');
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch vehicles'
            ], 500);
        }
    }

    /**
     * Store a newly created vehicle
     */
    public function storeVehicle(VehicleRequest $request): JsonResponse
    {
        try {
            // Check authorization
            if (!in_array(auth()->user()->role, ['SuperAdmin', 'Admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $vehicleData = $request->validated();
            
            // Set school_id for non-SuperAdmin users
            if (!auth()->user()->isSuperAdmin()) {
                $vehicleData['school_id'] = auth()->user()->school_id;
            }

            $vehicle = $this->transportService->createVehicle($vehicleData);

            return response()->json([
                'success' => true,
                'message' => 'Vehicle created successfully',
                'data' => $vehicle
            ], 201);
        } catch (\Exception $e) {
            ActivityLogger::log('Vehicle Creation Failed', 'Transport', [
                'error' => $e->getMessage(),
                'input_data' => $request->validated()
            ], 'error');
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create vehicle'
            ], 500);
        }
    }

    /**
     * Display the specified vehicle
     */
    public function showVehicle(Vehicle $vehicle): JsonResponse
    {
        try {
            // Check authorization
            $currentUser = auth()->user();
            if (!$currentUser->isSuperAdmin() && 
                !($currentUser->isAdmin() && $currentUser->school_id === $vehicle->school_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            ActivityLogger::log('Vehicle Viewed', 'Transport', [
                'vehicle_id' => $vehicle->id,
                'vehicle_number' => $vehicle->vehicle_number
            ]);

            return response()->json([
                'success' => true,
                'data' => $vehicle->load(['school', 'driver', 'route', 'students', 'maintenanceRecords', 'fuelRecords'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch vehicle details'
            ], 500);
        }
    }

    /**
     * Update the specified vehicle
     */
    public function updateVehicle(VehicleRequest $request, Vehicle $vehicle): JsonResponse
    {
        try {
            // Check authorization
            $currentUser = auth()->user();
            if (!$currentUser->isSuperAdmin() && 
                !($currentUser->isAdmin() && $currentUser->school_id === $vehicle->school_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $vehicleData = $request->validated();
            $vehicle = $this->transportService->updateVehicle($vehicle, $vehicleData);

            return response()->json([
                'success' => true,
                'message' => 'Vehicle updated successfully',
                'data' => $vehicle
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update vehicle'
            ], 500);
        }
    }

    /**
     * Remove the specified vehicle
     */
    public function destroyVehicle(Vehicle $vehicle): JsonResponse
    {
        try {
            // Check authorization
            $currentUser = auth()->user();
            if (!$currentUser->isSuperAdmin() && 
                !($currentUser->isAdmin() && $currentUser->school_id === $vehicle->school_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $this->transportService->deleteVehicle($vehicle);

            return response()->json([
                'success' => true,
                'message' => 'Vehicle deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    // ==================== ROUTE MANAGEMENT ====================

    /**
     * Display a listing of routes
     */
    public function indexRoutes(Request $request): JsonResponse
    {
        try {
            $query = TransportRoute::with(['school', 'vehicles', 'stops']);

            // Apply school scope for non-SuperAdmin users
            if (!auth()->user()->isSuperAdmin()) {
                $query->where('school_id', auth()->user()->school_id);
            }

            // Search functionality
            if ($request->filled('search')) {
                $query->search($request->search);
            }

            // School filter (only for SuperAdmin)
            if ($request->filled('school_id') && auth()->user()->isSuperAdmin()) {
                $query->where('school_id', $request->school_id);
            }

            // Active filter
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $routes = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $routes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch routes'
            ], 500);
        }
    }

    /**
     * Store a newly created route
     */
    public function storeRoute(RouteRequest $request): JsonResponse
    {
        try {
            $routeData = $request->validated();
            
            // Set school_id for non-SuperAdmin users
            if (!auth()->user()->isSuperAdmin()) {
                $routeData['school_id'] = auth()->user()->school_id;
            }

            $route = $this->transportService->createRoute($routeData);

            return response()->json([
                'success' => true,
                'message' => 'Route created successfully',
                'data' => $route
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create route'
            ], 500);
        }
    }

    /**
     * Display the specified route
     */
    public function showRoute(TransportRoute $route): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $route->load(['school', 'vehicles', 'stops'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch route details'
            ], 500);
        }
    }

    /**
     * Update the specified route
     */
    public function updateRoute(RouteRequest $request, TransportRoute $route): JsonResponse
    {
        try {
            $routeData = $request->validated();
            $route = $this->transportService->updateRoute($route, $routeData);

            return response()->json([
                'success' => true,
                'message' => 'Route updated successfully',
                'data' => $route
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update route'
            ], 500);
        }
    }

    /**
     * Remove the specified route
     */
    public function destroyRoute(TransportRoute $route): JsonResponse
    {
        try {
            $route->delete();

            return response()->json([
                'success' => true,
                'message' => 'Route deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete route'
            ], 500);
        }
    }

    // ==================== DRIVER MANAGEMENT ====================

    /**
     * Display a listing of drivers
     */
    public function indexDrivers(Request $request): JsonResponse
    {
        try {
            $query = Driver::with(['school', 'vehicle']);

            // Apply school scope for non-SuperAdmin users
            if (!auth()->user()->isSuperAdmin()) {
                $query->where('school_id', auth()->user()->school_id);
            }

            // Search functionality
            if ($request->filled('search')) {
                $query->search($request->search);
            }

            // Available filter
            if ($request->filled('available')) {
                if ($request->boolean('available')) {
                    $query->available();
                }
            }

            $drivers = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $drivers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch drivers'
            ], 500);
        }
    }

    /**
     * Store a newly created driver
     */
    public function storeDriver(DriverRequest $request): JsonResponse
    {
        try {
            $driverData = $request->validated();
            
            // Set school_id for non-SuperAdmin users
            if (!auth()->user()->isSuperAdmin()) {
                $driverData['school_id'] = auth()->user()->school_id;
            }

            $driver = $this->transportService->createDriver($driverData);

            return response()->json([
                'success' => true,
                'message' => 'Driver created successfully',
                'data' => $driver
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create driver'
            ], 500);
        }
    }

    // ==================== ASSIGNMENT OPERATIONS ====================

    /**
     * Assign driver to vehicle
     */
    public function assignDriver(Request $request, Vehicle $vehicle): JsonResponse
    {
        try {
            $request->validate([
                'driver_id' => 'required|exists:drivers,id'
            ]);

            $driver = Driver::findOrFail($request->driver_id);
            $vehicle = $this->transportService->assignDriverToVehicle($vehicle, $driver);

            return response()->json([
                'success' => true,
                'message' => 'Driver assigned successfully',
                'data' => $vehicle
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Assign students to vehicle
     */
    public function assignStudents(Request $request, Vehicle $vehicle): JsonResponse
    {
        try {
            $request->validate([
                'student_ids' => 'required|array',
                'student_ids.*' => 'exists:students,id',
                'pickup_stop_id' => 'nullable|exists:route_stops,id',
                'dropoff_stop_id' => 'nullable|exists:route_stops,id',
                'pickup_time' => 'nullable|date_format:H:i',
                'dropoff_time' => 'nullable|date_format:H:i'
            ]);

            $assignmentData = $request->only(['pickup_stop_id', 'dropoff_stop_id', 'pickup_time', 'dropoff_time']);
            $vehicle = $this->transportService->assignStudentsToVehicle(
                $vehicle, 
                $request->student_ids, 
                $assignmentData
            );

            return response()->json([
                'success' => true,
                'message' => 'Students assigned successfully',
                'data' => $vehicle
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    // ==================== MAINTENANCE & RECORDS ====================

    /**
     * Record vehicle maintenance
     */
    public function recordMaintenance(Request $request, Vehicle $vehicle): JsonResponse
    {
        try {
            $request->validate([
                'type' => 'required|string',
                'description' => 'required|string',
                'cost' => 'required|numeric|min:0',
                'service_provider' => 'nullable|string',
                'service_date' => 'required|date',
                'next_service_date' => 'nullable|date|after:service_date',
                'odometer_reading' => 'nullable|integer|min:0',
                'parts_replaced' => 'nullable|array',
                'notes' => 'nullable|string'
            ]);

            $maintenance = $this->transportService->recordMaintenance($vehicle, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Maintenance recorded successfully',
                'data' => $maintenance
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to record maintenance'
            ], 500);
        }
    }

    /**
     * Record fuel consumption
     */
    public function recordFuel(Request $request, Vehicle $vehicle): JsonResponse
    {
        try {
            $request->validate([
                'fuel_type' => 'required|string',
                'quantity' => 'required|numeric|min:0',
                'cost_per_unit' => 'required|numeric|min:0',
                'total_cost' => 'required|numeric|min:0',
                'odometer_reading' => 'nullable|integer|min:0',
                'fuel_station' => 'nullable|string',
                'filled_by' => 'nullable|string',
                'fill_date' => 'required|date',
                'notes' => 'nullable|string'
            ]);

            $fuelRecord = $this->transportService->recordFuelConsumption($vehicle, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Fuel consumption recorded successfully',
                'data' => $fuelRecord
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to record fuel consumption'
            ], 500);
        }
    }

    // ==================== STATISTICS & REPORTS ====================

    /**
     * Get transport statistics
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $stats = $this->transportService->getTransportStatistics(
                auth()->user()->isSuperAdmin() ? null : auth()->user()->school_id
            );

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch transport statistics'
            ], 500);
        }
    }

    /**
     * Get vehicles requiring maintenance
     */
    public function getMaintenanceAlerts(): JsonResponse
    {
        try {
            $vehicles = $this->transportService->getVehiclesRequiringMaintenance(
                auth()->user()->isSuperAdmin() ? null : auth()->user()->school_id
            );

            return response()->json([
                'success' => true,
                'data' => $vehicles
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch maintenance alerts'
            ], 500);
        }
    }

    /**
     * Get route efficiency analysis
     */
    public function getRouteEfficiency(): JsonResponse
    {
        try {
            $analysis = $this->transportService->getRouteEfficiencyAnalysis(
                auth()->user()->isSuperAdmin() ? null : auth()->user()->school_id
            );

            return response()->json([
                'success' => true,
                'data' => $analysis
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch route efficiency analysis'
            ], 500);
        }
    }

    /**
     * Get vehicle types
     */
    public function getVehicleTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Vehicle::TYPES
        ]);
    }

    /**
     * Get vehicle statuses
     */
    public function getVehicleStatuses(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Vehicle::STATUSES
        ]);
    }

    /**
     * Get fuel types
     */
    public function getFuelTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Vehicle::FUEL_TYPES
        ]);
    }
}