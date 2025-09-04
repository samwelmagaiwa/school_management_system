<?php

namespace App\Modules\IDCard\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\IDCard\Models\IDCard;
use App\Modules\IDCard\Requests\IDCardRequest;
use App\Modules\IDCard\Services\IDCardService;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class IDCardController extends Controller
{
    protected IDCardService $idCardService;

    public function __construct(IDCardService $idCardService)
    {
        $this->idCardService = $idCardService;
    }

    /**
     * Display a listing of ID cards
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['search', 'school_id', 'type', 'is_active', 'expired']);
            $perPage = $request->get('per_page', 15);
            
            $idCards = $this->idCardService->getIDCards($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $idCards
            ]);
        } catch (\Exception $e) {
            ActivityLogger::log('ID Cards List Error', 'IDCard', [
                'error' => $e->getMessage(),
                'filters' => $request->only(['search', 'school_id', 'type'])
            ], 'error');
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch ID cards',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Generate new ID card
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:student,teacher',
            'student_id' => 'required_if:type,student|exists:students,id',
            'teacher_id' => 'required_if:type,teacher|exists:teachers,id',
            'expiry_date' => 'nullable|date|after:today',
            'template' => 'nullable|string',
            'force_regenerate' => 'boolean'
        ]);

        try {
            $options = [
                'expiry_date' => $request->expiry_date,
                'template' => $request->template,
                'force_regenerate' => $request->force_regenerate ?? false
            ];

            if ($request->type === 'student') {
                $idCard = $this->idCardService->generateStudentIDCard($request->student_id, $options);
            } else {
                $idCard = $this->idCardService->generateTeacherIDCard($request->teacher_id, $options);
            }

            return response()->json([
                'success' => true,
                'message' => 'ID card generated successfully',
                'data' => $idCard
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'Failed to generate ID card'
            ], 400);
        }
    }

    /**
     * Bulk generate ID cards
     */
    public function bulkGenerate(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:student,teacher',
            'student_ids' => 'required_if:type,student|array',
            'student_ids.*' => 'exists:students,id',
            'teacher_ids' => 'required_if:type,teacher|array',
            'teacher_ids.*' => 'exists:teachers,id',
            'options.expiry_date' => 'nullable|date|after:today',
            'options.template' => 'nullable|string',
            'options.force_regenerate' => 'boolean'
        ]);

        try {
            $data = [
                'type' => $request->type,
                'student_ids' => $request->student_ids,
                'teacher_ids' => $request->teacher_ids,
                'options' => $request->options ?? []
            ];

            $results = $this->idCardService->bulkGenerate($data);

            return response()->json([
                'success' => true,
                'message' => 'Bulk ID card generation completed',
                'data' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate ID cards in bulk',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display the specified ID card
     */
    public function show(IDCard $idCard): JsonResponse
    {
        try {
            $idCardData = $idCard->load(['school', 'student.user', 'teacher.user']);

            // Add computed attributes
            $idCardData->is_expired = $idCard->isExpired();
            $idCardData->is_valid = $idCard->isValid();
            $idCardData->holder_name = $idCard->holder_name;
            $idCardData->photo_url = $idCard->photo_url;
            $idCardData->qr_code_url = $idCard->qr_code_url;

            ActivityLogger::log('ID Card Details Viewed', 'IDCard', [
                'id_card_id' => $idCard->id,
                'card_number' => $idCard->card_number,
                'type' => $idCard->type
            ]);

            return response()->json([
                'success' => true,
                'data' => $idCardData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch ID card details',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Download ID card
     */
    public function download(IDCard $idCard): JsonResponse
    {
        try {
            $printablePath = $this->idCardService->generatePrintableCard($idCard);

            return response()->json([
                'success' => true,
                'download_url' => asset('storage/' . $printablePath),
                'message' => 'ID card ready for download'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate downloadable ID card',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Regenerate ID card
     */
    public function regenerate(IDCard $idCard, Request $request): JsonResponse
    {
        $request->validate([
            'expiry_date' => 'nullable|date|after:today',
            'template' => 'nullable|string'
        ]);

        try {
            $options = [
                'expiry_date' => $request->expiry_date,
                'template' => $request->template,
                'force_regenerate' => true
            ];

            if ($idCard->type === 'student') {
                $newCard = $this->idCardService->generateStudentIDCard($idCard->student_id, $options);
            } else {
                $newCard = $this->idCardService->generateTeacherIDCard($idCard->teacher_id, $options);
            }

            return response()->json([
                'success' => true,
                'message' => 'ID card regenerated successfully',
                'data' => $newCard
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to regenerate ID card',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Deactivate ID card
     */
    public function deactivate(IDCard $idCard, Request $request): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500'
        ]);

        try {
            $deactivatedCard = $this->idCardService->deactivateIDCard($idCard, $request->reason);

            return response()->json([
                'success' => true,
                'message' => 'ID card deactivated successfully',
                'data' => $deactivatedCard
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to deactivate ID card',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get ID card statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $schoolId = $request->get('school_id', auth()->user()->school_id);
            $stats = $this->idCardService->getIDCardStatistics($schoolId);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch ID card statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get available templates
     */
    public function getTemplates(): JsonResponse
    {
        try {
            $templates = $this->idCardService->getAvailableTemplates();

            return response()->json([
                'success' => true,
                'data' => $templates
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch templates',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Get ID card generation history
     */
    public function getHistory(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['school_id', 'type', 'date_from', 'date_to', 'search']);
            
            if (!auth()->user()->isSuperAdmin()) {
                $filters['school_id'] = auth()->user()->school_id;
            }
            
            $history = $this->idCardService->getGenerationHistory($filters);
            
            return response()->json([
                'success' => true,
                'data' => $history
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch generation history',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Export ID cards data
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'excel');
        $filters = $request->only(['school_id', 'type', 'is_active', 'expired', 'search']);
        
        if (!auth()->user()->isSuperAdmin()) {
            $filters['school_id'] = auth()->user()->school_id;
        }
        
        try {
            return $this->idCardService->exportIDCards($filters, $format);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting ID cards: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Bulk print ID cards
     */
    public function bulkPrint(Request $request): JsonResponse
    {
        $request->validate([
            'id_card_ids' => 'required|array',
            'id_card_ids.*' => 'exists:id_cards,id',
            'template' => 'nullable|string'
        ]);
        
        try {
            $result = $this->idCardService->bulkPrintIDCards(
                $request->id_card_ids, 
                $request->template
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Bulk print initiated successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error in bulk printing: ' . $e->getMessage()
            ], 500);
        }
    }
}
