<?php

namespace App\Modules\Student\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\User\Models\User;
use App\Modules\School\Models\School;
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
        'student_id',
        'roll_number',
        'class_id',
        'section',
        'admission_date',
        'admission_number',
        'admission_type',
        'date_of_birth',
        'gender',
        'blood_group',
        'nationality',
        'religion',
        'caste',
        'category',
        'address',
        'city',
        'state',
        'postal_code',
        'phone',
        'father_name',
        'father_occupation',
        'father_phone',
        'father_email',
        'mother_name',
        'mother_occupation',
        'mother_phone',
        'mother_email',
        'guardian_name',
        'guardian_relation',
        'guardian_phone',
        'guardian_email',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'previous_school',
        'previous_class',
        'previous_percentage',
        'medical_conditions',
        'allergies',
        'special_needs',
        'uses_transport',
        'vehicle_id',
        'pickup_point',
        'drop_point',
        'status',
        'status_date',
        'status_reason'
    ];

    protected $casts = [
        'admission_date' => 'date',
        'date_of_birth' => 'date',
        'status_date' => 'date',
        'previous_percentage' => 'decimal:2',
        'uses_transport' => 'boolean',
        'status' => 'string'
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

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeBySchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeByClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeSearch($query, $search)
    {
        return $query->whereHas('user', function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$search}%"]);
        })->orWhere('admission_number', 'like', "%{$search}%");
    }

    // Helper methods
    public static function generateAdmissionNumber($schoolId)
    {
        $year = date('Y');
        $schoolCode = School::find($schoolId)->code ?? 'SCH';
        
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
}