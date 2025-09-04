<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Library\Controllers\LibraryController;

/*
|--------------------------------------------------------------------------
| Library Module API Routes
|--------------------------------------------------------------------------
|
| Here are the routes for the Library module. All routes are protected
| by auth:sanctum middleware for API authentication.
|
*/

Route::middleware(['auth:sanctum'])->prefix('library')->group(function () {
    // Books management
    Route::get('/books', [LibraryController::class, 'index'])->name('library.books.index');
    Route::post('/books', [LibraryController::class, 'store'])->name('library.books.store');
    Route::get('/books/{book}', [LibraryController::class, 'show'])->name('library.books.show');
    Route::put('/books/{book}', [LibraryController::class, 'update'])->name('library.books.update');
    Route::delete('/books/{book}', [LibraryController::class, 'destroy'])->name('library.books.destroy');
    
    // Library statistics
    Route::get('/statistics', [LibraryController::class, 'statistics'])->name('library.statistics');
    
    // Book borrowing
    Route::post('/borrow', [LibraryController::class, 'borrowBook'])->name('library.borrow');
    Route::put('/return/{borrowingId}', [LibraryController::class, 'returnBook'])->name('library.return');
    Route::get('/borrowing-history', [LibraryController::class, 'borrowingHistory'])->name('library.borrowing.history');
    
    // Categories
    Route::get('/categories', [LibraryController::class, 'categories'])->name('library.categories.index');
    Route::post('/categories', [LibraryController::class, 'createCategory'])->name('library.categories.store');
    
    // Reports
    Route::get('/reports/overdue', [LibraryController::class, 'overdueReport'])->name('library.reports.overdue');
    
    // Export functionality
    Route::get('/books/export', [LibraryController::class, 'exportBooks'])->name('library.books.export');
});