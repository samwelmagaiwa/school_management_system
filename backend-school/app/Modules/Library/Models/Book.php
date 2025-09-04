<?php

namespace App\Modules\Library\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id', 'category_id', 'title', 'author', 'isbn', 
        'publisher', 'publication_year', 'total_copies', 'available_copies', 'price'
    ];

    protected $casts = [
        'publication_year' => 'integer',
        'total_copies' => 'integer',
        'available_copies' => 'integer',
        'price' => 'decimal:2',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function category()
    {
        return $this->belongsTo(BookCategory::class, 'category_id');
    }

    public function borrowings()
    {
        return $this->hasMany(BookBorrowing::class);
    }

    public function isAvailable(): bool
    {
        return $this->available_copies > 0;
    }
}

class BookCategory extends Model
{
    use HasFactory;

    protected $fillable = ['school_id', 'name', 'description'];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function books()
    {
        return $this->hasMany(Book::class, 'category_id');
    }
}

class BookBorrowing extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id', 'student_id', 'borrowed_date', 'due_date', 
        'returned_date', 'status', 'fine_amount'
    ];

    protected $casts = [
        'borrowed_date' => 'date',
        'due_date' => 'date',
        'returned_date' => 'date',
        'fine_amount' => 'decimal:2',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}