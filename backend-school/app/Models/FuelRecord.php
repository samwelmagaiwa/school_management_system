<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FuelRecord extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'vehicle_id',
        'fuel_type',
        'quantity',
        'cost_per_unit',
        'total_cost',
        'odometer_reading',
        'fuel_station',
        'filled_by',
        'fill_date',
        'notes'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'quantity' => 'decimal:2',
        'cost_per_unit' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'odometer_reading' => 'integer',
        'fill_date' => 'date',
    ];

    /**
     * Get the vehicle that owns the fuel record
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Calculate fuel efficiency (km per liter)
     */
    public function calculateEfficiency(): float
    {
        $previousRecord = static::where('vehicle_id', $this->vehicle_id)
            ->where('fill_date', '<', $this->fill_date)
            ->orderBy('fill_date', 'desc')
            ->first();

        if (!$previousRecord || !$this->quantity) {
            return 0;
        }

        $distanceTraveled = $this->odometer_reading - $previousRecord->odometer_reading;
        return $distanceTraveled > 0 ? round($distanceTraveled / $this->quantity, 2) : 0;
    }
}