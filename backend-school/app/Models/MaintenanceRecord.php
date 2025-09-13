<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceRecord extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'vehicle_id',
        'type',
        'description',
        'cost',
        'service_provider',
        'service_date',
        'next_service_date',
        'odometer_reading',
        'parts_replaced',
        'notes',
        'status'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'cost' => 'decimal:2',
        'service_date' => 'date',
        'next_service_date' => 'date',
        'odometer_reading' => 'integer',
        'parts_replaced' => 'array',
    ];

    /**
     * Maintenance types
     */
    const TYPE_ROUTINE = 'routine';
    const TYPE_REPAIR = 'repair';
    const TYPE_EMERGENCY = 'emergency';
    const TYPE_INSPECTION = 'inspection';

    const TYPES = [
        self::TYPE_ROUTINE => 'Routine Maintenance',
        self::TYPE_REPAIR => 'Repair',
        self::TYPE_EMERGENCY => 'Emergency Repair',
        self::TYPE_INSPECTION => 'Inspection'
    ];

    /**
     * Maintenance statuses
     */
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    const STATUSES = [
        self::STATUS_SCHEDULED => 'Scheduled',
        self::STATUS_IN_PROGRESS => 'In Progress',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_CANCELLED => 'Cancelled'
    ];

    /**
     * Get the vehicle that owns the maintenance record
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get type display name
     */
    public function getTypeDisplayAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Get status display name
     */
    public function getStatusDisplayAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}