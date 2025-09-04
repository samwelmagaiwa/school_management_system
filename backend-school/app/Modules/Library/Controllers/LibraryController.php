<?php

namespace App\Modules\Library\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Library\Models\Book;
use App\Modules\Library\Models\BookCategory;
use App\Modules\Library\Models\BookBorrowing;
use App\Modules\Library\Requests\BookRequest;
use App\Modules\Library\Services\LibraryService;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class LibraryController extends Controller
{
    protected LibraryService $libraryService;

    public function __construct(LibraryService $libraryService)
    {
        $this->middleware('auth:sanctum');
        $this->libraryService = $libraryService;
    }

    /**
     * Get books with filters and pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'search', 'school_id', 'category_id', 'availability', 
                'publication_year', 'sort_by', 'sort_order', 'per_page'
            ]);

            $books = $this->libraryService->getBooks($filters);

            return response()->json([
                'success' => true,
                'data' => $books->items(),
                'meta' => [
                    'current_page' => $books->currentPage(),
                    'last_page' => $books->lastPage(),
                    'per_page' => $books->perPage(),
                    'total' => $books->total(),
                    'from' => $books->firstItem(),
                    'to' => $books->lastItem()
                ],
                'filters' => $filters
            ]);
        } catch (Exception $e) {
            ActivityLogger::log('Books List Error', 'Library', [
                'error' => $e->getMessage(),
                'filters' => $request->all()
            ], 'error');

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve books',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new book
     */
    public function store(BookRequest $request): JsonResponse
    {
        try {
            $book = $this->libraryService->createBook($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Book created successfully',
                'data' => $book
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create book',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show a specific book
     */
    public function show(Book $book): JsonResponse
    {
        try {
            ActivityLogger::log('Book Details Viewed', 'Library', [
                'book_id' => $book->id,
                'book_title' => $book->title
            ]);

            return response()->json([
                'success' => true,
                'data' => $book->load(['school', 'category', 'borrowings.student.user'])
            ]);
        } catch (Exception $e) {
            ActivityLogger::log('Book Details Error', 'Library', [
                'book_id' => $book->id,
                'error' => $e->getMessage()
            ], 'error');

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve book details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a book
     */
    public function update(BookRequest $request, Book $book): JsonResponse
    {
        try {
            $updatedBook = $this->libraryService->updateBook($book, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Book updated successfully',
                'data' => $updatedBook
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update book',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a book
     */
    public function destroy(Book $book): JsonResponse
    {
        try {
            $this->libraryService->deleteBook($book);

            return response()->json([
                'success' => true,
                'message' => 'Book deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete book',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get library statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $schoolId = $request->get('school_id');
            $stats = $this->libraryService->getStatistics($schoolId);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Borrow a book
     */
    public function borrowBook(Request $request): JsonResponse
    {
        $request->validate([
            'book_id' => 'required|exists:books,id',
            'student_id' => 'required|exists:students,id',
            'due_date' => 'nullable|date|after:today'
        ]);

        try {
            $borrowing = $this->libraryService->borrowBook(
                $request->book_id,
                $request->student_id,
                $request->only(['due_date'])
            );

            return response()->json([
                'success' => true,
                'message' => 'Book borrowed successfully',
                'data' => $borrowing
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to borrow book',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Return a book
     */
    public function returnBook(Request $request, int $borrowingId): JsonResponse
    {
        $request->validate([
            'returned_date' => 'nullable|date'
        ]);

        try {
            $borrowing = $this->libraryService->returnBook(
                $borrowingId,
                $request->only(['returned_date'])
            );

            return response()->json([
                'success' => true,
                'message' => 'Book returned successfully',
                'data' => $borrowing
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to return book',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get borrowing history
     */
    public function borrowingHistory(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'student_id', 'book_id', 'status', 'overdue', 
                'school_id', 'sort_by', 'sort_order', 'per_page'
            ]);

            $borrowings = $this->libraryService->getBorrowingHistory($filters);

            return response()->json([
                'success' => true,
                'data' => $borrowings->items(),
                'meta' => [
                    'current_page' => $borrowings->currentPage(),
                    'last_page' => $borrowings->lastPage(),
                    'per_page' => $borrowings->perPage(),
                    'total' => $borrowings->total()
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve borrowing history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get book categories
     */
    public function categories(Request $request): JsonResponse
    {
        try {
            $schoolId = $request->get('school_id');
            $categories = $this->libraryService->getCategories($schoolId);

            return response()->json([
                'success' => true,
                'data' => $categories
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create book category
     */
    public function createCategory(Request $request): JsonResponse
    {
        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500'
        ]);

        try {
            $category = $this->libraryService->createCategory($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully',
                'data' => $category
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get overdue books report
     */
    public function overdueReport(Request $request): JsonResponse
    {
        try {
            $schoolId = $request->get('school_id');
            $report = $this->libraryService->getOverdueReport($schoolId);

            return response()->json([
                'success' => true,
                'data' => $report
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate overdue report',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Export books data
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'excel');
        $filters = $request->only(['search', 'school_id', 'category_id', 'availability', 'publication_year']);
        
        if (!auth()->user()->isSuperAdmin()) {
            $filters['school_id'] = auth()->user()->school_id;
        }
        
        try {
            return $this->libraryService->exportBooks($filters, $format);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting books: ' . $e->getMessage()
            ], 500);
        }
    }
}
