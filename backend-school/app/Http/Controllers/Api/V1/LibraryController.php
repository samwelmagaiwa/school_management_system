<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Http\Requests\BookRequest;
use App\Services\LibraryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LibraryController extends Controller
{
    protected LibraryService $libraryService;

    public function __construct(LibraryService $libraryService)
    {
        $this->middleware('auth:sanctum');
        $this->libraryService = $libraryService;
    }

    /**
     * Display a listing of books.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Book::class);

        $books = $this->libraryService->getAllBooks($request->all());

        return response()->json([
            'success' => true,
            'data' => $books
        ]);
    }

    /**
     * Store a newly created book.
     */
    public function store(BookRequest $request): JsonResponse
    {
        $this->authorize('create', Book::class);

        try {
            DB::beginTransaction();

            $book = $this->libraryService->createBook($request->validated());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Book created successfully',
                'data' => $book
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create book',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified book.
     */
    public function show(Book $book): JsonResponse
    {
        $this->authorize('view', $book);

        return response()->json([
            'success' => true,
            'data' => $book->load(['category', 'borrowings'])
        ]);
    }

    /**
     * Update the specified book.
     */
    public function update(BookRequest $request, Book $book): JsonResponse
    {
        $this->authorize('update', $book);

        try {
            DB::beginTransaction();

            $book = $this->libraryService->updateBook($book, $request->validated());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Book updated successfully',
                'data' => $book
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update book',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified book.
     */
    public function destroy(Book $book): JsonResponse
    {
        $this->authorize('delete', $book);

        try {
            DB::beginTransaction();

            $this->libraryService->deleteBook($book);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Book deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete book',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get library statistics.
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', Book::class);

        $stats = $this->libraryService->getLibraryStatistics();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Issue a book to a student.
     */
    public function issueBook(Request $request): JsonResponse
    {
        $this->authorize('create', Book::class);

        try {
            DB::beginTransaction();

            $borrowing = $this->libraryService->issueBook($request->all());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Book issued successfully',
                'data' => $borrowing
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to issue book',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Return a book from a student.
     */
    public function returnBook(Request $request): JsonResponse
    {
        $this->authorize('update', Book::class);

        try {
            DB::beginTransaction();

            $borrowing = $this->libraryService->returnBook($request->all());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Book returned successfully',
                'data' => $borrowing
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to return book',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get issued books.
     */
    public function issuedBooks(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Book::class);

        $issuedBooks = $this->libraryService->getIssuedBooks($request->all());

        return response()->json([
            'success' => true,
            'data' => $issuedBooks
        ]);
    }
}
