<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models$1;
use App\Models$1;
use App\Models$1;

class IDCard extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'school_id',
        'student_id',
        'teacher_id',
        'card_number',
        'type',
        'issue_date',
        'expiry_date',
        'photo_path',
        'qr_code_path',
        'template_used',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * ID Card types
     */
    const TYPE_STUDENT = 'student';
    const TYPE_TEACHER = 'teacher';

    /**
     * Get the school that the ID card belongs to
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the student associated with the ID card
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the teacher associated with the ID card
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Get the holder of the ID card (student or teacher)
     */
    public function getHolderAttribute()
    {
        return $this->type === self::TYPE_STUDENT ? $this->student : $this->teacher;
    }

    /**
     * Get the holder's name
     */
    public function getHolderNameAttribute(): string
    {
        $holder = $this->holder;
        return $holder ? $holder->user->name : '';
    }

    /**
     * Get the photo URL
     */
    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? asset('storage/' . $this->photo_path) : null;
    }

    /**
     * Get the QR code URL
     */
    public function getQrCodeUrlAttribute(): ?string
    {
        return $this->qr_code_path ? asset('storage/' . $this->qr_code_path) : null;
    }

    /**
     * Check if ID card is expired
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Check if ID card is valid
     */
    public function isValid(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    /**
     * Scope to get only active ID cards
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only valid ID cards
     */
    public function scopeValid($query)
    {
        return $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('expiry_date')
                          ->orWhere('expiry_date', '>', now());
                    });
    }

    /**
     * Scope to filter by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get student ID cards
     */
    public function scopeStudents($query)
    {
        return $query->where('type', self::TYPE_STUDENT);
    }

    /**
     * Scope to get teacher ID cards
     */
    public function scopeTeachers($query)
    {
        return $query->where('type', self::TYPE_TEACHER);
    }
}