<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models$1;
use App\Models$1;
use App\Models$1;
use App\Models$1;

class Teacher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'school_id',
        'employee_id',
        'date_of_birth',
        'gender',
        'phone',
        'address',
        'qualification',
        'experience_years',
        'employment_type',
        'salary',
        'joining_date',
        'leaving_date',
        'emergency_contacts',
        'documents',
        'status'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'experience_years' => 'integer',
        'salary' => 'decimal:2',
        'joining_date' => 'date',
        'leaving_date' => 'date',
        'emergency_contacts' => 'array',
        'documents' => 'array',
        'status' => 'boolean'
    ];

    protected $dates = ['deleted_at'];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'teacher_subjects');
    }

    public function classes()
    {
        return $this->hasMany(SchoolClass::class, 'class_teacher_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeBySchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeByEmploymentType($query, $type)
    {
        return $query->where('employment_type', $type);
    }

    public function scopeSearch($query, $search)
    {
        return $query->whereHas('user', function($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        })->orWhere('employee_id', 'like', "%{$search}%");
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return $this->user ? $this->user->full_name : '';
    }

    public function getAgeAttribute()
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }
}