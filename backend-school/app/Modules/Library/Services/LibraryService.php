<?php

namespace App\Modules\Library\Services;

use App\Modules\Library\Models\Book;
use App\Modules\Library\Models\BookCategory;
use App\Modules\Library\Models\BookBorrowing;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Exception;

class LibraryService
{
    /**
     * Get books with filters and pagination
     */
    public function getBooks(array $filters = []): LengthAwarePaginator
    {
        try {
            $query = Book::with(['school', 'category', 'borrowings'])
                ->when(isset($filters['school_id']), function ($q) use ($filters) {
                    return $q->where('school_id', $filters['school_id']);
                })
                ->when(isset($filters['category_id']), function ($q) use ($filters) {
                    return $q->where('category_id', $filters['category_id']);
                })
                ->when(isset($filters['search']), function ($q) use ($filters) {
                    return $q->where(function ($query) use ($filters) {
                        $query->where('title', 'like', "%{$filters['search']}%")
                              ->orWhere('author', 'like', "%{$filters['search']}%")
                              ->orWhere('isbn', 'like', "%{$filters['search']}%")
                              ->orWhere('publisher', 'like', "%{$filters['search']}%");
                    });
                })
                ->when(isset($filters['availability']), function ($q) use ($filters) {
                    if ($filters['availability'] === 'available') {
                        return $q->where('available_copies', '>', 0);
                    } elseif ($filters['availability'] === 'unavailable') {
                        return $q->where('available_copies', '<=', 0);
                    }
                })
                ->when(isset($filters['publication_year']), function ($q) use ($filters) {
                    return $q->where('publication_year', $filters['publication_year']);
                });

            // Sorting
            $sortBy = $filters['sort_by'] ?? 'title';
            $sortOrder = $filters['sort_order'] ?? 'asc';
            $query->orderBy($sortBy, $sortOrder);

            $books = $query->paginate($filters['per_page'] ?? 15);

            ActivityLogger::log('Books List Retrieved', 'Library', [
                'filters' => $filters,
                'total_books' => $books->total()
            ]);

            return $books;
        } catch (Exception $e) {
            ActivityLogger::log('Books List Error', 'Library', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ], 'error');
            throw $e;
        }
    }

    /**
     * Create a new book
     */
    public function createBook(array $data): Book
    {
        try {
            DB::beginTransaction();

            $book = Book::create($data);

            ActivityLogger::log('Book Created', 'Library', [
                'book_id' => $book->id,
                'title' => $book->title,
                'author' => $book->author,
                'isbn' => $book->isbn,
                'school_id' => $book->school_id
            ]);

            DB::commit();
            return $book->load(['school', 'category']);
        } catch (Exception $e) {
            DB::rollBack();
            ActivityLogger::log('Book Creation Failed', 'Library', [
                'error' => $e->getMessage(),
                'data' => $data
            ], 'error');
            throw $e;
        }
    }

    /**
     * Update a book
     */
    public function updateBook(Book $book, array $data): Book
    {
        try {
            DB::beginTransaction();

            $originalData = $book->toArray();
            $book->update($data);

            ActivityLogger::log('Book Updated', 'Library', [
                'book_id' => $book->id,
                'title' => $book->title,
                'changes' => array_diff_assoc($data, $originalData)
            ]);

            DB::commit();
            return $book->fresh(['school', 'category']);
        } catch (Exception $e) {
            DB::rollBack();
            ActivityLogger::log('Book Update Failed', 'Library', [
                'book_id' => $book->id,
                'error' => $e->getMessage(),
                'data' => $data
            ], 'error');
            throw $e;
        }
    }

    /**
     * Delete a book
     */
    public function deleteBook(Book $book): bool
    {
        try {
            DB::beginTransaction();

            // Check if book has active borrowings
            $activeBorrowings = $book->borrowings()
                ->whereNull('returned_date')
                ->count();

            if ($activeBorrowings > 0) {
                throw new Exception('Cannot delete book with active borrowings');
            }

            $bookData = $book->toArray();
            $book->delete();

            ActivityLogger::log('Book Deleted', 'Library', $bookData);

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            ActivityLogger::log('Book Deletion Failed', 'Library', [
                'book_id' => $book->id,
                'error' => $e->getMessage()
            ], 'error');
            throw $e;
        }
    }

    /**
     * Get library statistics
     */
    public function getStatistics(int $schoolId = null): array
    {
        try {
            $cacheKey = "library_stats_" . ($schoolId ?? 'all');
            
            return Cache::remember($cacheKey, 300, function () use ($schoolId) {
                $query = Book::query();
                if ($schoolId) {
                    $query->where('school_id', $schoolId);
                }

                $totalBooks = $query->sum('total_copies');
                $availableBooks = $query->sum('available_copies');
                $borrowedBooks = $totalBooks - $availableBooks;
                $uniqueTitles = $query->count();

                $borrowingQuery = BookBorrowing::query();
                if ($schoolId) {
                    $borrowingQuery->whereHas('book', function ($q) use ($schoolId) {
                        $q->where('school_id', $schoolId);
                    });
                }

                $activeBorrowings = $borrowingQuery->whereNull('returned_date')->count();
                $overdueBorrowings = $borrowingQuery->whereNull('returned_date')
                    ->where('due_date', '<', now())->count();

                $categoryStats = BookCategory::withCount('books')
                    ->when($schoolId, function ($q) use ($schoolId) {
                        return $q->where('school_id', $schoolId);
                    })
                    ->get()
                    ->map(function ($category) {
                        return [
                            'name' => $category->name,
                            'count' => $category->books_count
                        ];
                    });

                $stats = [
                    'total_books' => $totalBooks,
                    'available_books' => $availableBooks,
                    'borrowed_books' => $borrowedBooks,
                    'unique_titles' => $uniqueTitles,
                    'active_borrowings' => $activeBorrowings,
                    'overdue_borrowings' => $overdueBorrowings,
                    'availability_rate' => $totalBooks > 0 ? round(($availableBooks / $totalBooks) * 100, 2) : 0,
                    'category_distribution' => $categoryStats
                ];

                ActivityLogger::log('Library Statistics Retrieved', 'Library', [
                    'school_id' => $schoolId,
                    'stats' => $stats
                ]);

                return $stats;
            });
        } catch (Exception $e) {
            ActivityLogger::log('Library Statistics Error', 'Library', [
                'school_id' => $schoolId,
                'error' => $e->getMessage()
            ], 'error');
            throw $e;
        }
    }

    /**
     * Borrow a book
     */
    public function borrowBook(int $bookId, int $studentId, array $data = []): BookBorrowing
    {
        try {
            DB::beginTransaction();

            $book = Book::findOrFail($bookId);
            
            if (!$book->isAvailable()) {
                throw new Exception('Book is not available for borrowing');
            }

            // Check if student has overdue books
            $overdueCount = BookBorrowing::where('student_id', $studentId)
                ->whereNull('returned_date')
                ->where('due_date', '<', now())
                ->count();

            if ($overdueCount > 0) {
                throw new Exception('Student has overdue books. Cannot borrow new books.');
            }

            // Create borrowing record
            $borrowing = BookBorrowing::create([
                'book_id' => $bookId,
                'student_id' => $studentId,
                'borrowed_date' => $data['borrowed_date'] ?? now(),
                'due_date' => $data['due_date'] ?? now()->addDays(14),
                'status' => 'borrowed'
            ]);

            // Update book availability
            $book->decrement('available_copies');

            ActivityLogger::log('Book Borrowed', 'Library', [
                'borrowing_id' => $borrowing->id,
                'book_id' => $bookId,
                'book_title' => $book->title,
                'student_id' => $studentId,
                'due_date' => $borrowing->due_date
            ]);

            DB::commit();
            return $borrowing->load(['book', 'student']);
        } catch (Exception $e) {
            DB::rollBack();
            ActivityLogger::log('Book Borrowing Failed', 'Library', [
                'book_id' => $bookId,
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ], 'error');
            throw $e;
        }
    }

    /**
     * Return a book
     */
    public function returnBook(int $borrowingId, array $data = []): BookBorrowing
    {
        try {
            DB::beginTransaction();

            $borrowing = BookBorrowing::with(['book'])->findOrFail($borrowingId);
            
            if ($borrowing->returned_date) {
                throw new Exception('Book has already been returned');
            }

            $returnDate = $data['returned_date'] ?? now();
            $fineAmount = 0;

            // Calculate fine if overdue
            if ($returnDate > $borrowing->due_date) {
                $overdueDays = $returnDate->diffInDays($borrowing->due_date);
                $fineAmount = $overdueDays * 1.00; // $1 per day fine
            }

            $borrowing->update([
                'returned_date' => $returnDate,
                'status' => 'returned',
                'fine_amount' => $fineAmount
            ]);

            // Update book availability
            $borrowing->book->increment('available_copies');

            ActivityLogger::log('Book Returned', 'Library', [
                'borrowing_id' => $borrowing->id,
                'book_id' => $borrowing->book_id,
                'book_title' => $borrowing->book->title,
                'student_id' => $borrowing->student_id,
                'return_date' => $returnDate,
                'fine_amount' => $fineAmount
            ]);

            DB::commit();
            return $borrowing->fresh(['book', 'student']);
        } catch (Exception $e) {
            DB::rollBack();
            ActivityLogger::log('Book Return Failed', 'Library', [
                'borrowing_id' => $borrowingId,
                'error' => $e->getMessage()
            ], 'error');
            throw $e;
        }
    }

    /**
     * Get borrowing history
     */
    public function getBorrowingHistory(array $filters = []): LengthAwarePaginator
    {
        try {
            $query = BookBorrowing::with(['book', 'student'])
                ->when(isset($filters['student_id']), function ($q) use ($filters) {
                    return $q->where('student_id', $filters['student_id']);
                })
                ->when(isset($filters['book_id']), function ($q) use ($filters) {
                    return $q->where('book_id', $filters['book_id']);
                })
                ->when(isset($filters['status']), function ($q) use ($filters) {
                    return $q->where('status', $filters['status']);
                })
                ->when(isset($filters['overdue']), function ($q) use ($filters) {
                    if ($filters['overdue']) {
                        return $q->whereNull('returned_date')
                                 ->where('due_date', '<', now());
                    }
                })
                ->when(isset($filters['school_id']), function ($q) use ($filters) {
                    return $q->whereHas('book', function ($query) use ($filters) {
                        $query->where('school_id', $filters['school_id']);
                    });
                });

            $sortBy = $filters['sort_by'] ?? 'borrowed_date';
            $sortOrder = $filters['sort_order'] ?? 'desc';
            $query->orderBy($sortBy, $sortOrder);

            $borrowings = $query->paginate($filters['per_page'] ?? 15);

            ActivityLogger::log('Borrowing History Retrieved', 'Library', [
                'filters' => $filters,
                'total_records' => $borrowings->total()
            ]);

            return $borrowings;
        } catch (Exception $e) {
            ActivityLogger::log('Borrowing History Error', 'Library', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ], 'error');
            throw $e;
        }
    }

    /**
     * Get book categories
     */
    public function getCategories(int $schoolId = null): Collection
    {
        try {
            $query = BookCategory::withCount('books');
            
            if ($schoolId) {
                $query->where('school_id', $schoolId);
            }

            $categories = $query->orderBy('name')->get();

            ActivityLogger::log('Book Categories Retrieved', 'Library', [
                'school_id' => $schoolId,
                'categories_count' => $categories->count()
            ]);

            return $categories;
        } catch (Exception $e) {
            ActivityLogger::log('Book Categories Error', 'Library', [
                'school_id' => $schoolId,
                'error' => $e->getMessage()
            ], 'error');
            throw $e;
        }
    }

    /**
     * Create book category
     */
    public function createCategory(array $data): BookCategory
    {
        try {
            $category = BookCategory::create($data);

            ActivityLogger::log('Book Category Created', 'Library', [
                'category_id' => $category->id,
                'name' => $category->name,
                'school_id' => $category->school_id
            ]);

            return $category;
        } catch (Exception $e) {
            ActivityLogger::log('Book Category Creation Failed', 'Library', [
                'error' => $e->getMessage(),
                'data' => $data
            ], 'error');
            throw $e;
        }
    }

    /**
     * Get overdue books report
     */
    public function getOverdueReport(int $schoolId = null): array
    {
        try {
            $query = BookBorrowing::with(['book', 'student'])
                ->whereNull('returned_date')
                ->where('due_date', '<', now());

            if ($schoolId) {
                $query->whereHas('book', function ($q) use ($schoolId) {
                    $q->where('school_id', $schoolId);
                });
            }

            $overdueBorrowings = $query->orderBy('due_date')->get();

            $report = [
                'total_overdue' => $overdueBorrowings->count(),
                'total_fine_amount' => $overdueBorrowings->sum('fine_amount'),
                'borrowings' => $overdueBorrowings->map(function ($borrowing) {
                    $overdueDays = now()->diffInDays($borrowing->due_date);
                    return [
                        'id' => $borrowing->id,
                        'book_title' => $borrowing->book->title,
                        'student_name' => $borrowing->student->user->name ?? 'Unknown',
                        'borrowed_date' => $borrowing->borrowed_date,
                        'due_date' => $borrowing->due_date,
                        'overdue_days' => $overdueDays,
                        'fine_amount' => $borrowing->fine_amount
                    ];
                })
            ];

            ActivityLogger::log('Overdue Report Generated', 'Library', [
                'school_id' => $schoolId,
                'total_overdue' => $report['total_overdue']
            ]);

            return $report;
        } catch (Exception $e) {
            ActivityLogger::log('Overdue Report Error', 'Library', [
                'school_id' => $schoolId,
                'error' => $e->getMessage()
            ], 'error');
            throw $e;
        }
    }

    /**
     * Export books data
     */
    public function exportBooks(array $filters, string $format = 'excel')
    {
        // Build query with filters
        $query = Book::with(['school', 'category']);
        
        if (isset($filters['school_id'])) {
            $query->where('school_id', $filters['school_id']);
        }
        
        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        
        if (isset($filters['availability'])) {
            if ($filters['availability'] === 'available') {
                $query->where('available_copies', '>', 0);
            } elseif ($filters['availability'] === 'unavailable') {
                $query->where('available_copies', '<=', 0);
            }
        }
        
        if (isset($filters['publication_year'])) {
            $query->where('publication_year', $filters['publication_year']);
        }
        
        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }
        
        $books = $query->get();
        
        // Generate export file
        $filename = 'books_export_' . date('Y-m-d_H-i-s') . ($format === 'excel' ? '.xlsx' : '.csv');
        
        $headers = [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ];
        
        return response()->streamDownload(function () use ($books) {
            $output = fopen('php://output', 'w');
            
            // Write headers
            fputcsv($output, [
                'Title', 'Author', 'ISBN', 'Category', 'Publisher', 
                'Publication Year', 'Total Copies', 'Available Copies', 
                'School', 'Location', 'Status'
            ]);
            
            // Write data
            foreach ($books as $book) {
                fputcsv($output, [
                    $book->title,
                    $book->author,
                    $book->isbn,
                    $book->category->name ?? '',
                    $book->publisher,
                    $book->publication_year,
                    $book->total_copies,
                    $book->available_copies,
                    $book->school->name ?? '',
                    $book->location,
                    $book->status ? 'Active' : 'Inactive'
                ]);
            }
            
            fclose($output);
        }, $filename, $headers);
    }

    /**
     * Clear cache
     */
    public function clearCache(int $schoolId = null): void
    {
        if ($schoolId) {
            Cache::forget("library_stats_{$schoolId}");
        } else {
            Cache::forget('library_stats_all');
        }
    }
}
