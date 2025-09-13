<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripRecord extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'vehicle_id',
        'driver_id',
        'route_id',
        'trip_type',
        'start_time',
        'end_time',
        'start_odometer',
        'end_odometer',
        'students_count',
        'fuel_consumed',
        'status',
        'notes',
        'rating'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'start_odometer' => 'integer',
        'end_odometer' => 'integer',
        'students_count' => 'integer',
        'fuel_consumed' => 'decimal:2',
        'rating' => 'decimal:1',
    ];

    /**
     * Trip types
     */
    const TYPE_PICKUP = 'pickup';
    const TYPE_DROPOFF = 'dropoff';
    const TYPE_ROUND_TRIP = 'round_trip';
    const TYPE_SPECIAL = 'special';

    const TYPES = [
        self::TYPE_PICKUP => 'Pickup',
        self::TYPE_DROPOFF => 'Dropoff',
        self::TYPE_ROUND_TRIP => 'Round Trip',
        self::TYPE_SPECIAL => 'Special Trip'
    ];

    /**
     * Trip statuses
     */
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_DELAYED = 'delayed';

    const STATUSES = [
        self::STATUS_SCHEDULED => 'Scheduled',
        self::STATUS_IN_PROGRESS => 'In Progress',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_CANCELLED => 'Cancelled',
        self::STATUS_DELAYED => 'Delayed'
    ];

    /**
     * Get the vehicle for the trip
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the driver for the trip
     */
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the route for the trip
     */
    public function route()
    {
        return $this->belongsTo(TransportRoute::class, 'route_id');
    }

    /**
     * Calculate trip duration in minutes
     */
    public function getDurationAttribute(): int
    {
        if (!$this->start_time || !$this->end_time) {
            return 0;
        }
        return $this->start_time->diffInMinutes($this->end_time);
    }

    /**
     * Calculate distance traveled
     */
    public function getDistanceAttribute(): int
    {
        return $this->end_odometer - $this->start_odometer;
    }

    /**
     * Get type display name
     */
    public function getTypeDisplayAttribute(): string
    {
        return self::TYPES[$this->trip_type] ?? $this->trip_type;
    }

    /**
     * Get status display name
     */
    public function getStatusDisplayAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}