<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RouteStop extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'route_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'sequence',
        'pickup_time',
        'dropoff_time',
        'is_active'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'sequence' => 'integer',
        'pickup_time' => 'datetime:H:i',
        'dropoff_time' => 'datetime:H:i',
        'is_active' => 'boolean',
    ];

    /**
     * Get the route that owns the stop
     */
    public function route()
    {
        return $this->belongsTo(TransportRoute::class, 'route_id');
    }

    /**
     * Get students who use this stop for pickup
     */
    public function pickupStudents()
    {
        return $this->hasMany(\App\Modules\Student\Models\Student::class, 'pickup_stop_id');
    }

    /**
     * Get students who use this stop for dropoff
     */
    public function dropoffStudents()
    {
        return $this->hasMany(\App\Modules\Student\Models\Student::class, 'dropoff_stop_id');
    }

    /**
     * Scope to get only active stops
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sequence
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sequence');
    }

    /**
     * Get total students using this stop
     */
    public function getTotalStudents(): int
    {
        return $this->pickupStudents()->count() + $this->dropoffStudents()->count();
    }

    /**
     * Get formatted coordinates
     */
    public function getCoordinatesAttribute(): string
    {
        if ($this->latitude && $this->longitude) {
            return $this->latitude . ', ' . $this->longitude;
        }
        return 'Not set';
    }

    /**
     * Get formatted pickup time
     */
    public function getFormattedPickupTimeAttribute(): string
    {
        return $this->pickup_time ? $this->pickup_time->format('H:i') : 'Not set';
    }

    /**
     * Get formatted dropoff time
     */
    public function getFormattedDropoffTimeAttribute(): string
    {
        return $this->dropoff_time ? $this->dropoff_time->format('H:i') : 'Not set';
    }
}