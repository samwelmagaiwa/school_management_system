<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\TransportRoute;
use App\Http\Requests\VehicleRequest;
use App\Services\TransportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class TransportController extends Controller
{
    protected TransportService $transportService;

    public function __construct(TransportService $transportService)
    {
        $this->middleware('auth:sanctum');
        $this->transportService = $transportService;
    }

    /**
     * Display a listing of vehicles.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Vehicle::class);

        $vehicles = $this->transportService->getAllVehicles($request->all());

        return response()->json([
            'success' => true,
            'data' => $vehicles
        ]);
    }

    /**
     * Store a newly created vehicle.
     */
    public function store(VehicleRequest $request): JsonResponse
    {
        $this->authorize('create', Vehicle::class);

        try {
            DB::beginTransaction();

            $vehicle = $this->transportService->createVehicle($request->validated());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Vehicle created successfully',
                'data' => $vehicle
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create vehicle',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified vehicle.
     */
    public function show(Vehicle $vehicle): JsonResponse
    {
        $this->authorize('view', $vehicle);

        return response()->json([
            'success' => true,
            'data' => $vehicle->load(['driver', 'route', 'students'])
        ]);
    }

    /**
     * Update the specified vehicle.
     */
    public function update(VehicleRequest $request, Vehicle $vehicle): JsonResponse
    {
        $this->authorize('update', $vehicle);

        try {
            DB::beginTransaction();

            $vehicle = $this->transportService->updateVehicle($vehicle, $request->validated());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Vehicle updated successfully',
                'data' => $vehicle
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update vehicle',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified vehicle.
     */
    public function destroy(Vehicle $vehicle): JsonResponse
    {
        $this->authorize('delete', $vehicle);

        try {
            DB::beginTransaction();

            $this->transportService->deleteVehicle($vehicle);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Vehicle deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete vehicle',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transport statistics.
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', Vehicle::class);

        $stats = $this->transportService->getTransportStatistics();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get transport routes.
     */
    public function routes(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TransportRoute::class);

        $routes = $this->transportService->getRoutes($request->all());

        return response()->json([
            'success' => true,
            'data' => $routes
        ]);
    }

    /**
     * Create a new transport route.
     */
    public function createRoute(Request $request): JsonResponse
    {
        $this->authorize('create', TransportRoute::class);

        try {
            DB::beginTransaction();

            $route = $this->transportService->createRoute($request->all());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Route created successfully',
                'data' => $route
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create route',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get students using transport.
     */
    public function transportStudents(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Vehicle::class);

        $students = $this->transportService->getTransportStudents($request->all());

        return response()->json([
            'success' => true,
            'data' => $students
        ]);
    }
}
