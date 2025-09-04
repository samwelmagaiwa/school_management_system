<?php

namespace App\Modules\Transport\Services;

use App\Modules\Transport\Models\Vehicle;
use App\Modules\Transport\Models\TransportRoute;
use App\Modules\Transport\Models\Driver;
use App\Modules\Transport\Models\RouteStop;
use App\Modules\Transport\Models\MaintenanceRecord;
use App\Modules\Transport\Models\FuelRecord;
use App\Modules\Transport\Models\TripRecord;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class TransportService
{
    /**
     * Create a new vehicle
     */
    public function createVehicle(array $vehicleData): Vehicle
    {
        return DB::transaction(function () use ($vehicleData) {
            $vehicle = Vehicle::create($vehicleData);

            // Clear related caches
            $this->clearTransportCaches();

            ActivityLogger::log('Vehicle Created', 'Transport', [
                'vehicle_id' => $vehicle->id,
                'vehicle_number' => $vehicle->vehicle_number,
                'school_id' => $vehicle->school_id,
                'capacity' => $vehicle->capacity
            ]);

            return $vehicle->load(['school', 'driver', 'route']);
        });
    }

    /**
     * Update an existing vehicle
     */
    public function updateVehicle(Vehicle $vehicle, array $vehicleData): Vehicle
    {
        return DB::transaction(function () use ($vehicle, $vehicleData) {
            $originalData = $vehicle->toArray();
            $vehicle->update($vehicleData);

            // Clear related caches
            $this->clearTransportCaches();

            ActivityLogger::log('Vehicle Updated', 'Transport', [
                'vehicle_id' => $vehicle->id,
                'vehicle_number' => $vehicle->vehicle_number,
                'changes' => array_diff_assoc($vehicleData, $originalData)
            ]);

            return $vehicle->load(['school', 'driver', 'route']);
        });
    }

    /**
     * Delete a vehicle
     */
    public function deleteVehicle(Vehicle $vehicle): bool
    {
        return DB::transaction(function () use ($vehicle) {
            $vehicleData = [
                'vehicle_id' => $vehicle->id,
                'vehicle_number' => $vehicle->vehicle_number,
                'school_id' => $vehicle->school_id
            ];

            // Check if vehicle has assigned students
            if ($vehicle->students()->exists()) {
                throw new \Exception('Cannot delete vehicle with assigned students');
            }

            $result = $vehicle->delete();

            // Clear related caches
            $this->clearTransportCaches();

            ActivityLogger::log('Vehicle Deleted', 'Transport', $vehicleData);

            return $result;
        });
    }

    /**
     * Create a new route
     */
    public function createRoute(array $routeData): TransportRoute
    {
        return DB::transaction(function () use ($routeData) {
            // Extract stops data if provided
            $stopsData = $routeData['stops'] ?? [];
            unset($routeData['stops']);

            $route = TransportRoute::create($routeData);

            // Create route stops
            if (!empty($stopsData)) {
                foreach ($stopsData as $index => $stopData) {
                    $stopData['route_id'] = $route->id;
                    $stopData['sequence'] = $index + 1;
                    RouteStop::create($stopData);
                }
            }

            // Clear related caches
            $this->clearTransportCaches();

            ActivityLogger::log('Transport Route Created', 'Transport', [
                'route_id' => $route->id,
                'route_name' => $route->name,
                'school_id' => $route->school_id,
                'stops_count' => count($stopsData)
            ]);

            return $route->load(['school', 'stops', 'vehicles']);
        });
    }

    /**
     * Update an existing route
     */
    public function updateRoute(TransportRoute $route, array $routeData): TransportRoute
    {
        return DB::transaction(function () use ($route, $routeData) {
            $originalData = $route->toArray();

            // Extract stops data if provided
            $stopsData = $routeData['stops'] ?? null;
            unset($routeData['stops']);

            $route->update($routeData);

            // Update route stops if provided
            if ($stopsData !== null) {
                // Delete existing stops
                $route->stops()->delete();

                // Create new stops
                foreach ($stopsData as $index => $stopData) {
                    $stopData['route_id'] = $route->id;
                    $stopData['sequence'] = $index + 1;
                    RouteStop::create($stopData);
                }
            }

            // Clear related caches
            $this->clearTransportCaches();

            ActivityLogger::log('Transport Route Updated', 'Transport', [
                'route_id' => $route->id,
                'route_name' => $route->name,
                'changes' => array_diff_assoc($routeData, $originalData)
            ]);

            return $route->load(['school', 'stops', 'vehicles']);
        });
    }

    /**
     * Create a new driver
     */
    public function createDriver(array $driverData): Driver
    {
        return DB::transaction(function () use ($driverData) {
            $driver = Driver::create($driverData);

            // Clear related caches
            $this->clearTransportCaches();

            ActivityLogger::log('Driver Created', 'Transport', [
                'driver_id' => $driver->id,
                'driver_name' => $driver->name,
                'school_id' => $driver->school_id,
                'license_number' => $driver->license_number
            ]);

            return $driver->load(['school', 'vehicle']);
        });
    }

    /**
     * Assign driver to vehicle
     */
    public function assignDriverToVehicle(Vehicle $vehicle, Driver $driver): Vehicle
    {
        return DB::transaction(function () use ($vehicle, $driver) {
            // Check if driver is available
            if (!$driver->isAvailableForAssignment()) {
                throw new \Exception('Driver is not available for assignment');
            }

            // Unassign current driver if any
            if ($vehicle->driver) {
                $oldDriver = $vehicle->driver;
                $vehicle->update(['driver_id' => null]);
                
                ActivityLogger::log('Driver Unassigned from Vehicle', 'Transport', [
                    'vehicle_id' => $vehicle->id,
                    'old_driver_id' => $oldDriver->id,
                    'old_driver_name' => $oldDriver->name
                ]);
            }

            // Assign new driver
            $vehicle->update(['driver_id' => $driver->id]);

            // Clear related caches
            $this->clearTransportCaches();

            ActivityLogger::log('Driver Assigned to Vehicle', 'Transport', [
                'vehicle_id' => $vehicle->id,
                'vehicle_number' => $vehicle->vehicle_number,
                'driver_id' => $driver->id,
                'driver_name' => $driver->name
            ]);

            return $vehicle->load(['driver', 'route']);
        });
    }

    /**
     * Assign students to vehicle
     */
    public function assignStudentsToVehicle(Vehicle $vehicle, array $studentIds, array $assignmentData = []): Vehicle
    {
        return DB::transaction(function () use ($vehicle, $studentIds, $assignmentData) {
            // Check vehicle capacity
            $currentOccupancy = $vehicle->getCurrentOccupancy();
            $newStudentsCount = count($studentIds);
            
            if (($currentOccupancy + $newStudentsCount) > $vehicle->capacity) {
                throw new \Exception('Vehicle capacity exceeded');
            }

            // Prepare pivot data
            $pivotData = [];
            foreach ($studentIds as $studentId) {
                $pivotData[$studentId] = array_merge($assignmentData, [
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            // Attach students to vehicle
            $vehicle->students()->attach($pivotData);

            // Clear related caches
            $this->clearTransportCaches();

            ActivityLogger::log('Students Assigned to Vehicle', 'Transport', [
                'vehicle_id' => $vehicle->id,
                'vehicle_number' => $vehicle->vehicle_number,
                'student_ids' => $studentIds,
                'students_count' => $newStudentsCount
            ]);

            return $vehicle->load(['students', 'route']);
        });
    }

    /**
     * Record vehicle maintenance
     */
    public function recordMaintenance(Vehicle $vehicle, array $maintenanceData): MaintenanceRecord
    {
        return DB::transaction(function () use ($vehicle, $maintenanceData) {
            $maintenanceData['vehicle_id'] = $vehicle->id;
            $maintenance = MaintenanceRecord::create($maintenanceData);

            // Update vehicle's next maintenance date if provided
            if (isset($maintenanceData['next_service_date'])) {
                $vehicle->update(['next_maintenance' => $maintenanceData['next_service_date']]);
            }

            // Update vehicle status if it's a major repair
            if ($maintenanceData['type'] === MaintenanceRecord::TYPE_REPAIR) {
                $vehicle->update(['status' => Vehicle::STATUS_MAINTENANCE]);
            }

            ActivityLogger::log('Vehicle Maintenance Recorded', 'Transport', [
                'vehicle_id' => $vehicle->id,
                'maintenance_id' => $maintenance->id,
                'maintenance_type' => $maintenance->type,
                'cost' => $maintenance->cost
            ]);

            return $maintenance;
        });
    }

    /**
     * Record fuel consumption
     */
    public function recordFuelConsumption(Vehicle $vehicle, array $fuelData): FuelRecord
    {
        return DB::transaction(function () use ($vehicle, $fuelData) {
            $fuelData['vehicle_id'] = $vehicle->id;
            $fuelRecord = FuelRecord::create($fuelData);

            ActivityLogger::log('Fuel Consumption Recorded', 'Transport', [
                'vehicle_id' => $vehicle->id,
                'fuel_record_id' => $fuelRecord->id,
                'quantity' => $fuelRecord->quantity,
                'total_cost' => $fuelRecord->total_cost
            ]);

            return $fuelRecord;
        });
    }

    /**
     * Start a trip
     */
    public function startTrip(Vehicle $vehicle, array $tripData): TripRecord
    {
        return DB::transaction(function () use ($vehicle, $tripData) {
            $tripData['vehicle_id'] = $vehicle->id;
            $tripData['start_time'] = now();
            $tripData['status'] = TripRecord::STATUS_IN_PROGRESS;

            $trip = TripRecord::create($tripData);

            // Update vehicle status
            $vehicle->update(['status' => Vehicle::STATUS_ACTIVE]);

            ActivityLogger::log('Trip Started', 'Transport', [
                'vehicle_id' => $vehicle->id,
                'trip_id' => $trip->id,
                'route_id' => $trip->route_id,
                'driver_id' => $trip->driver_id
            ]);

            return $trip;
        });
    }

    /**
     * Complete a trip
     */
    public function completeTrip(TripRecord $trip, array $completionData): TripRecord
    {
        return DB::transaction(function () use ($trip, $completionData) {
            $trip->update(array_merge($completionData, [
                'end_time' => now(),
                'status' => TripRecord::STATUS_COMPLETED
            ]));

            ActivityLogger::log('Trip Completed', 'Transport', [
                'trip_id' => $trip->id,
                'vehicle_id' => $trip->vehicle_id,
                'duration' => $trip->duration,
                'distance' => $trip->distance
            ]);

            return $trip;
        });
    }

    /**
     * Get transport statistics
     */
    public function getTransportStatistics(?int $schoolId = null): array
    {
        $cacheKey = "transport_statistics_" . ($schoolId ?? 'all');
        
        return Cache::remember($cacheKey, 300, function () use ($schoolId) {
            $vehicleQuery = Vehicle::query();
            $routeQuery = TransportRoute::query();
            $driverQuery = Driver::query();

            if ($schoolId) {
                $vehicleQuery->where('school_id', $schoolId);
                $routeQuery->where('school_id', $schoolId);
                $driverQuery->where('school_id', $schoolId);
            }

            // Vehicle statistics
            $totalVehicles = $vehicleQuery->count();
            $activeVehicles = $vehicleQuery->where('is_active', true)->count();
            $vehiclesInMaintenance = $vehicleQuery->where('status', Vehicle::STATUS_MAINTENANCE)->count();

            // Route statistics
            $totalRoutes = $routeQuery->count();
            $activeRoutes = $routeQuery->where('is_active', true)->count();

            // Driver statistics
            $totalDrivers = $driverQuery->count();
            $activeDrivers = $driverQuery->where('is_active', true)->count();
            $availableDrivers = $driverQuery->available()->count();

            // Student transportation statistics
            $totalStudentsTransported = $vehicleQuery->withCount('students')->get()->sum('students_count');
            $totalCapacity = $vehicleQuery->sum('capacity');
            $occupancyRate = $totalCapacity > 0 ? round(($totalStudentsTransported / $totalCapacity) * 100, 2) : 0;

            // Vehicle type distribution
            $vehicleTypeStats = $vehicleQuery->selectRaw('vehicle_type, COUNT(*) as count')
                ->groupBy('vehicle_type')
                ->pluck('count', 'vehicle_type')
                ->toArray();

            // Fuel type distribution
            $fuelTypeStats = $vehicleQuery->selectRaw('fuel_type, COUNT(*) as count')
                ->groupBy('fuel_type')
                ->pluck('count', 'fuel_type')
                ->toArray();

            // Maintenance alerts
            $maintenanceAlerts = $vehicleQuery->whereDate('next_maintenance', '<=', now()->addDays(7))->count();
            $insuranceAlerts = $vehicleQuery->whereDate('insurance_expiry', '<=', now()->addDays(30))->count();
            $registrationAlerts = $vehicleQuery->whereDate('registration_expiry', '<=', now()->addDays(30))->count();

            $stats = [
                'total_vehicles' => $totalVehicles,
                'active_vehicles' => $activeVehicles,
                'vehicles_in_maintenance' => $vehiclesInMaintenance,
                'total_routes' => $totalRoutes,
                'active_routes' => $activeRoutes,
                'total_drivers' => $totalDrivers,
                'active_drivers' => $activeDrivers,
                'available_drivers' => $availableDrivers,
                'total_students_transported' => $totalStudentsTransported,
                'total_capacity' => $totalCapacity,
                'occupancy_rate' => $occupancyRate,
                'vehicle_type_distribution' => $vehicleTypeStats,
                'fuel_type_distribution' => $fuelTypeStats,
                'maintenance_alerts' => $maintenanceAlerts,
                'insurance_alerts' => $insuranceAlerts,
                'registration_alerts' => $registrationAlerts,
                'monthly_trends' => $this->getMonthlyTrends($schoolId),
                'cost_analysis' => $this->getCostAnalysis($schoolId)
            ];

            ActivityLogger::log('Transport Statistics Retrieved', 'Transport', [
                'school_id' => $schoolId,
                'statistics' => $stats
            ]);

            return $stats;
        });
    }

    /**
     * Get vehicles requiring maintenance
     */
    public function getVehiclesRequiringMaintenance(?int $schoolId = null): array
    {
        $query = Vehicle::with(['school', 'driver'])
            ->where(function ($q) {
                $q->whereDate('next_maintenance', '<=', now()->addDays(7))
                  ->orWhereDate('insurance_expiry', '<=', now()->addDays(30))
                  ->orWhereDate('registration_expiry', '<=', now()->addDays(30));
            });

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $vehicles = $query->get()->map(function ($vehicle) {
            return [
                'vehicle' => $vehicle,
                'maintenance_due' => $vehicle->needsMaintenance(),
                'insurance_expiring' => $vehicle->insuranceExpiringSoon(),
                'registration_expiring' => $vehicle->registrationExpiringSoon(),
                'days_until_maintenance' => $vehicle->next_maintenance ? 
                    now()->diffInDays($vehicle->next_maintenance, false) : null,
                'days_until_insurance_expiry' => $vehicle->insurance_expiry ? 
                    now()->diffInDays($vehicle->insurance_expiry, false) : null,
                'days_until_registration_expiry' => $vehicle->registration_expiry ? 
                    now()->diffInDays($vehicle->registration_expiry, false) : null
            ];
        })->toArray();

        ActivityLogger::log('Vehicles Requiring Maintenance Retrieved', 'Transport', [
            'school_id' => $schoolId,
            'count' => count($vehicles)
        ]);

        return $vehicles;
    }

    /**
     * Get route efficiency analysis
     */
    public function getRouteEfficiencyAnalysis(?int $schoolId = null): array
    {
        $query = TransportRoute::with(['vehicles', 'stops']);

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $routes = $query->get()->map(function ($route) {
            return [
                'route' => $route,
                'total_students' => $route->getTotalStudents(),
                'total_capacity' => $route->getTotalCapacity(),
                'occupancy_percentage' => $route->getOccupancyPercentage(),
                'vehicles_count' => $route->vehicles()->count(),
                'stops_count' => $route->stops()->count(),
                'efficiency_score' => $this->calculateRouteEfficiency($route)
            ];
        })->toArray();

        return $routes;
    }

    /**
     * Calculate route efficiency score
     */
    private function calculateRouteEfficiency(TransportRoute $route): float
    {
        $occupancyScore = $route->getOccupancyPercentage();
        $distanceScore = $route->distance > 0 ? min(100, (50 / $route->distance) * 100) : 0;
        $stopsScore = $route->stops()->count() > 0 ? min(100, (10 / $route->stops()->count()) * 100) : 0;

        return round(($occupancyScore * 0.5) + ($distanceScore * 0.3) + ($stopsScore * 0.2), 2);
    }

    /**
     * Get monthly trends
     */
    private function getMonthlyTrends(?int $schoolId): array
    {
        // Placeholder implementation - would calculate actual trends
        return [
            'fuel_consumption' => [
                'current_month' => rand(1000, 2000),
                'previous_month' => rand(1000, 2000),
                'trend' => rand(-10, 10)
            ],
            'maintenance_costs' => [
                'current_month' => rand(5000, 15000),
                'previous_month' => rand(5000, 15000),
                'trend' => rand(-20, 20)
            ],
            'trips_completed' => [
                'current_month' => rand(200, 500),
                'previous_month' => rand(200, 500),
                'trend' => rand(-5, 15)
            ]
        ];
    }

    /**
     * Get cost analysis
     */
    private function getCostAnalysis(?int $schoolId): array
    {
        // Placeholder implementation - would calculate actual costs
        return [
            'total_monthly_cost' => rand(50000, 100000),
            'fuel_cost_percentage' => rand(40, 60),
            'maintenance_cost_percentage' => rand(20, 30),
            'driver_salary_percentage' => rand(20, 30),
            'cost_per_student' => rand(500, 1000),
            'cost_per_km' => rand(10, 25)
        ];
    }

    /**
     * Clear transport-related caches
     */
    private function clearTransportCaches(): void
    {
        // Clear statistics caches
        $cacheKeys = Cache::getRedis()->keys('*transport_statistics_*');
        if (!empty($cacheKeys)) {
            Cache::getRedis()->del($cacheKeys);
        }
    }
}