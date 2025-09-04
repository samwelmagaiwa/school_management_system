<?php

use Illuminate\Support\Facades\Route;
use App\Modules\IDCard\Controllers\IDCardController;

/*
|--------------------------------------------------------------------------
| ID Card Module Routes
|--------------------------------------------------------------------------
|
| Here are the routes for ID card management functionality including
| generating, viewing, and managing student and teacher ID cards.
|
*/

Route::middleware('auth:sanctum')->prefix('id-cards')->group(function () {
    // ID Card CRUD operations
    Route::get('/', [IDCardController::class, 'index']);
    Route::get('/{idCard}', [IDCardController::class, 'show']);
    
    // ID Card generation
    Route::post('/generate', [IDCardController::class, 'generate']);
    Route::post('/bulk-generate', [IDCardController::class, 'bulkGenerate']);
    Route::post('/{idCard}/regenerate', [IDCardController::class, 'regenerate']);
    
    // ID Card management
    Route::get('/{idCard}/download', [IDCardController::class, 'download']);
    Route::patch('/{idCard}/deactivate', [IDCardController::class, 'deactivate']);
    
    // Statistics and data
    Route::get('/statistics/overview', [IDCardController::class, 'statistics']);
    Route::get('/templates/available', [IDCardController::class, 'getTemplates']);
    
    // Export and printing
    Route::get('/export', [IDCardController::class, 'export']);
    Route::post('/print-multiple', [IDCardController::class, 'printMultiple']);
    Route::get('/generation-history', [IDCardController::class, 'getGenerationHistory']);
});