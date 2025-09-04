<?php

namespace App\Modules\Transport\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\School\Models\School;

class Driver extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'school_id',
        'name',
        'phone',
        'email',
        'address',
        'license_number',
        'license_type',
        'license_expiry',
        'date_of_birth',
        'experience_years',
        'emergency_contact',
        'emergency_phone',
        'salary',
        'joining_date',
        'is_active'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'license_expiry' => 'date',
        'date_of_birth' => 'date',
        'joining_date' => 'date',
        'experience_years' => 'integer',
        'salary' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * License types
     */
    const LICENSE_LIGHT_VEHICLE = 'light_vehicle';
    const LICENSE_HEAVY_VEHICLE = 'heavy_vehicle';
    const LICENSE_COMMERCIAL = 'commercial';
    const LICENSE_PASSENGER = 'passenger';

    const LICENSE_TYPES = [
        self::LICENSE_LIGHT_VEHICLE => 'Light Vehicle',
        self::LICENSE_HEAVY_VEHICLE => 'Heavy Vehicle',
        self::LICENSE_COMMERCIAL => 'Commercial',
        self::LICENSE_PASSENGER => 'Passenger Vehicle'
    ];

    /**
     * Get the school that employs the driver
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the vehicle assigned to the driver
     */
    public function vehicle()
    {
        return $this->hasOne(Vehicle::class);
    }

    /**
     * Get trip records for the driver
     */
    public function tripRecords()
    {
        return $this->hasMany(TripRecord::class);
    }

    /**
     * Scope to get only active drivers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by school
     */
    public function scopeBySchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    /**
     * Scope to search drivers
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('license_number', 'like', "%{$search}%");
    }

    /**
     * Scope to get available drivers (not assigned to any vehicle)
     */
    public function scopeAvailable($query)
    {
        return $query->active()->doesntHave('vehicle');
    }

    /**
     * Get driver's age
     */
    public function getAgeAttribute(): int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : 0;
    }

    /**
     * Check if license is expiring soon
     */
    public function licenseExpiringSoon(): bool
    {
        return $this->license_expiry && $this->license_expiry <= now()->addDays(30);
    }

    /**
     * Get years of service
     */
    public function getYearsOfServiceAttribute(): float
    {
        return $this->joining_date ? $this->joining_date->diffInYears(now()) : 0;
    }

    /**
     * Get license type display name
     */
    public function getLicenseTypeDisplayAttribute(): string
    {
        return self::LICENSE_TYPES[$this->license_type] ?? $this->license_type;
    }

    /**
     * Check if driver is available for assignment
     */
    public function isAvailableForAssignment(): bool
    {
        return $this->is_active && 
               !$this->vehicle && 
               $this->license_expiry > now();
    }

    /**
     * Get total trips completed
     */
    public function getTotalTrips(): int
    {
        return $this->tripRecords()->count();
    }

    /**
     * Get average rating from trip records
     */
    public function getAverageRating(): float
    {
        return $this->tripRecords()->avg('rating') ?? 0;
    }
}