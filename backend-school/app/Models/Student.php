<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Attendance;
use Database\Factories\StudentFactory;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return StudentFactory::new();
    }

    protected $fillable = [
        'user_id',
        'school_id',
        'class_id',
        'admission_number',
        'roll_number',
        'section',
        'date_of_birth',
        'gender',
        'blood_group',
        'phone',
        'address',
        'parent_name',
        'parent_phone',
        'parent_email',
        'parent_id',
        'admission_date',
        'medical_info',
        'emergency_contacts',
        'status'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'admission_date' => 'date',
        'medical_info' => 'array',
        'emergency_contacts' => 'array',
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

    public function class()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class);
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

    public function scopeByClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeBySection($query, $section)
    {
        return $query->where('section', $section);
    }

    public function scopeSearch($query, $search)
    {
        return $query->whereHas('user', function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$search}%"]);
        })->orWhere('admission_number', 'like', "%{$search}%")
          ->orWhere('roll_number', 'like', "%{$search}%");
    }

    // Helper methods
    public static function generateAdmissionNumber($schoolId)
    {
        $year = date('Y');
        $school = School::find($schoolId);
        $schoolCode = $school ? $school->code : 'SCH';
        
        // Get the last admission number for this school and year
        $lastStudent = static::where('school_id', $schoolId)
            ->where('admission_number', 'like', "{$schoolCode}{$year}%")
            ->orderBy('admission_number', 'desc')
            ->first();

        if ($lastStudent) {
            // Extract the sequence number and increment
            $lastSequence = (int) substr($lastStudent->admission_number, -4);
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        return $schoolCode . $year . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
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

    // Get attendance percentage
    public function getAttendancePercentage($startDate = null, $endDate = null)
    {
        $query = $this->attendance();
        
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }
        
        $totalDays = $query->count();
        $presentDays = $query->whereIn('status', ['present', 'late'])->count();
        
        return $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0;
    }
}