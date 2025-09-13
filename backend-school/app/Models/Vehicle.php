<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models$1;
use App\Models$1;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'school_id',
        'vehicle_number',
        'vehicle_type',
        'model',
        'manufacturer',
        'year',
        'capacity',
        'fuel_type',
        'insurance_number',
        'insurance_expiry',
        'registration_expiry',
        'last_maintenance',
        'next_maintenance',
        'driver_id',
        'route_id',
        'is_active',
        'status'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'capacity' => 'integer',
        'year' => 'integer',
        'insurance_expiry' => 'date',
        'registration_expiry' => 'date',
        'last_maintenance' => 'date',
        'next_maintenance' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Vehicle types
     */
    const TYPE_BUS = 'bus';
    const TYPE_VAN = 'van';
    const TYPE_CAR = 'car';
    const TYPE_MINI_BUS = 'mini_bus';

    const TYPES = [
        self::TYPE_BUS => 'Bus',
        self::TYPE_VAN => 'Van',
        self::TYPE_CAR => 'Car',
        self::TYPE_MINI_BUS => 'Mini Bus'
    ];

    /**
     * Vehicle statuses
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_MAINTENANCE = 'maintenance';
    const STATUS_OUT_OF_SERVICE = 'out_of_service';
    const STATUS_REPAIR = 'repair';

    const STATUSES = [
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_MAINTENANCE => 'Under Maintenance',
        self::STATUS_OUT_OF_SERVICE => 'Out of Service',
        self::STATUS_REPAIR => 'Under Repair'
    ];

    /**
     * Fuel types
     */
    const FUEL_PETROL = 'petrol';
    const FUEL_DIESEL = 'diesel';
    const FUEL_CNG = 'cng';
    const FUEL_ELECTRIC = 'electric';

    const FUEL_TYPES = [
        self::FUEL_PETROL => 'Petrol',
        self::FUEL_DIESEL => 'Diesel',
        self::FUEL_CNG => 'CNG',
        self::FUEL_ELECTRIC => 'Electric'
    ];

    /**
     * Get the school that owns the vehicle
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the driver assigned to the vehicle
     */
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the route assigned to the vehicle
     */
    public function route()
    {
        return $this->belongsTo(TransportRoute::class, 'route_id');
    }

    /**
     * Get the students assigned to this vehicle
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_transport', 'vehicle_id', 'student_id')
                    ->withPivot(['pickup_stop_id', 'dropoff_stop_id', 'pickup_time', 'dropoff_time'])
                    ->withTimestamps();
    }

    /**
     * Get maintenance records for the vehicle
     */
    public function maintenanceRecords()
    {
        return $this->hasMany(MaintenanceRecord::class);
    }

    /**
     * Get fuel records for the vehicle
     */
    public function fuelRecords()
    {
        return $this->hasMany(FuelRecord::class);
    }

    /**
     * Get trip records for the vehicle
     */
    public function tripRecords()
    {
        return $this->hasMany(TripRecord::class);
    }

    /**
     * Scope to get only active vehicles
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by vehicle type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('vehicle_type', $type);
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by school
     */
    public function scopeBySchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    /**
     * Scope to search vehicles
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('vehicle_number', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('manufacturer', 'like', "%{$search}%");
    }

    /**
     * Get current occupancy
     */
    public function getCurrentOccupancy(): int
    {
        return $this->students()->count();
    }

    /**
     * Get occupancy percentage
     */
    public function getOccupancyPercentage(): float
    {
        if ($this->capacity === 0) return 0;
        return round(($this->getCurrentOccupancy() / $this->capacity) * 100, 2);
    }

    /**
     * Check if vehicle needs maintenance
     */
    public function needsMaintenance(): bool
    {
        return $this->next_maintenance && $this->next_maintenance <= now()->addDays(7);
    }

    /**
     * Check if insurance is expiring soon
     */
    public function insuranceExpiringSoon(): bool
    {
        return $this->insurance_expiry && $this->insurance_expiry <= now()->addDays(30);
    }

    /**
     * Check if registration is expiring soon
     */
    public function registrationExpiringSoon(): bool
    {
        return $this->registration_expiry && $this->registration_expiry <= now()->addDays(30);
    }

    /**
     * Get vehicle age in years
     */
    public function getAgeAttribute(): int
    {
        return $this->year ? now()->year - $this->year : 0;
    }

    /**
     * Get available capacity
     */
    public function getAvailableCapacityAttribute(): int
    {
        return $this->capacity - $this->getCurrentOccupancy();
    }

    /**
     * Check if vehicle is available for assignment
     */
    public function isAvailableForAssignment(): bool
    {
        return $this->is_active && 
               $this->status === self::STATUS_ACTIVE && 
               $this->getAvailableCapacityAttribute() > 0;
    }

    /**
     * Get type display name
     */
    public function getTypeDisplayAttribute(): string
    {
        return self::TYPES[$this->vehicle_type] ?? $this->vehicle_type;
    }

    /**
     * Get status display name
     */
    public function getStatusDisplayAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Get fuel type display name
     */
    public function getFuelTypeDisplayAttribute(): string
    {
        return self::FUEL_TYPES[$this->fuel_type] ?? $this->fuel_type;
    }
}