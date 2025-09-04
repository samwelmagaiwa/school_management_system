<?php

namespace App\Modules\School\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\User\Models\User;
use App\Modules\Student\Models\Student;
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

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }
}