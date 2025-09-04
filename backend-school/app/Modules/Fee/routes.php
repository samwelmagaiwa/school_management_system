<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Fee\Controllers\FeeController;

/*
|--------------------------------------------------------------------------
| Fee Module API Routes
|--------------------------------------------------------------------------
|
| Here are the routes for the Fee module. All routes are protected
| by auth:sanctum middleware and use role-based authorization.
|
*/

Route::middleware(['auth:sanctum'])->prefix('fees')->group(function () {
    Route::get('/', [FeeController::class, 'index'])->name('fees.index');
    Route::post('/', [FeeController::class, 'store'])->name('fees.store');
    Route::get('/types', [FeeController::class, 'getTypes'])->name('fees.types');
    Route::get('/statistics', [FeeController::class, 'getStatistics'])->name('fees.statistics');
    Route::get('/export', [FeeController::class, 'export'])->name('fees.export');
    Route::get('/student/{studentId}', [FeeController::class, 'getStudentFees'])->name('fees.student');
    Route::get('/{fee}', [FeeController::class, 'show'])->name('fees.show');
    Route::put('/{fee}', [FeeController::class, 'update'])->name('fees.update');
    Route::delete('/{fee}', [FeeController::class, 'destroy'])->name('fees.destroy');
    Route::post('/{fee}/mark-paid', [FeeController::class, 'markAsPaid'])->name('fees.mark-paid');
    Route::get('/{fee}/invoice', [FeeController::class, 'generateInvoice'])->name('fees.invoice');
    Route::get('/{fee}/receipt', [FeeController::class, 'generateReceipt'])->name('fees.receipt');
});
