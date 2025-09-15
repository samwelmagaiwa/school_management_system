<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Database\Factories\SchoolFactory;

class School extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return SchoolFactory::new();
    }

    protected $fillable = [
        'name',
        'code',
        'address',
        'phone',
        'email',
        'website',
        'logo',
        'established_year',
        'principal_name',
        'principal_email',
        'principal_phone',
        'description',
        'board_affiliation',
        'school_type',
        'registration_number',
        'tax_id',
        'settings',
        'is_active'
    ];

    protected $casts = [
        'established_year' => 'integer',
        'settings' => 'array',
        'is_active' => 'boolean'
    ];

    // Add accessor for status to maintain compatibility
    public function getStatusAttribute()
    {
        return $this->is_active;
    }

    // Add mutator for status to maintain compatibility
    public function setStatusAttribute($value)
    {
        $this->attributes['is_active'] = $value;
    }

    protected $dates = ['deleted_at'];

    // Relationships
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByStatus($query, $status)
    {
        if ($status !== null && $status !== '') {
            return $query->where('is_active', (bool) $status);
        }
        return $query;
    }

    public function scopeBySchoolType($query, $type)
    {
        if ($type) {
            return $query->where('school_type', $type);
        }
        return $query;
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }
}