<?php

namespace App\Modules\Transport\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\School\Models\School;

class TransportRoute extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'school_id',
        'name',
        'code',
        'description',
        'start_location',
        'end_location',
        'distance',
        'estimated_duration',
        'fare',
        'is_active'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'distance' => 'decimal:2',
        'estimated_duration' => 'integer', // in minutes
        'fare' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the school that owns the route
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the vehicles assigned to this route
     */
    public function vehicles()
    {
        return $this->hasMany(Vehicle::class, 'route_id');
    }

    /**
     * Get the stops on this route
     */
    public function stops()
    {
        return $this->hasMany(RouteStop::class, 'route_id')->orderBy('sequence');
    }

    /**
     * Get the students using this route
     */
    public function students()
    {
        return $this->hasManyThrough(
            \App\Modules\Student\Models\Student::class,
            Vehicle::class,
            'route_id', // Foreign key on vehicles table
            'id', // Foreign key on students table
            'id', // Local key on routes table
            'id' // Local key on vehicles table
        );
    }

    /**
     * Scope to get only active routes
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
     * Scope to search routes
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
    }

    /**
     * Get total students on this route
     */
    public function getTotalStudents(): int
    {
        return $this->vehicles()->withCount('students')->get()->sum('students_count');
    }

    /**
     * Get total capacity of vehicles on this route
     */
    public function getTotalCapacity(): int
    {
        return $this->vehicles()->sum('capacity');
    }

    /**
     * Get occupancy percentage
     */
    public function getOccupancyPercentage(): float
    {
        $totalCapacity = $this->getTotalCapacity();
        if ($totalCapacity === 0) return 0;
        
        return round(($this->getTotalStudents() / $totalCapacity) * 100, 2);
    }

    /**
     * Get estimated duration in hours and minutes
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->estimated_duration) return 'N/A';
        
        $hours = intval($this->estimated_duration / 60);
        $minutes = $this->estimated_duration % 60;
        
        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'm';
        }
        
        return $minutes . 'm';
    }

    /**
     * Get number of stops
     */
    public function getStopCountAttribute(): int
    {
        return $this->stops()->count();
    }

    /**
     * Get active vehicles count
     */
    public function getActiveVehiclesCountAttribute(): int
    {
        return $this->vehicles()->active()->count();
    }
}