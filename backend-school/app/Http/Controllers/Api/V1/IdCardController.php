<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\IDCard;
use App\Http\Requests\GenerateIDRequest;
use App\Services\IDCardService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class IdCardController extends Controller
{
    protected IDCardService $idCardService;

    public function __construct(IDCardService $idCardService)
    {
        $this->middleware('auth:sanctum');
        $this->idCardService = $idCardService;
    }

    /**
     * Generate ID cards.
     */
    public function generate(GenerateIDRequest $request): JsonResponse
    {
        $this->authorize('create', IDCard::class);

        try {
            DB::beginTransaction();

            $idCards = $this->idCardService->generateIDCards($request->validated());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'ID cards generated successfully',
                'data' => $idCards
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate ID cards',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available ID card templates.
     */
    public function templates(): JsonResponse
    {
        $this->authorize('viewAny', IDCard::class);

        $templates = $this->idCardService->getTemplates();

        return response()->json([
            'success' => true,
            'data' => $templates
        ]);
    }

    /**
     * Generate ID cards in bulk.
     */
    public function bulkGenerate(Request $request): JsonResponse
    {
        $this->authorize('create', IDCard::class);

        try {
            DB::beginTransaction();

            $idCards = $this->idCardService->bulkGenerateIDCards($request->all());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'ID cards generated successfully',
                'data' => [
                    'total_generated' => count($idCards),
                    'id_cards' => $idCards
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate ID cards',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download ID card.
     */
    public function download(IDCard $idCard): JsonResponse
    {
        $this->authorize('view', $idCard);

        try {
            $filePath = $this->idCardService->getIDCardFile($idCard);

            if (!Storage::exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID card file not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'download_url' => Storage::url($filePath),
                    'file_name' => basename($filePath)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get ID card download link',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get ID cards list.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', IDCard::class);

        $idCards = $this->idCardService->getAllIDCards($request->all());

        return response()->json([
            'success' => true,
            'data' => $idCards
        ]);
    }

    /**
     * Display the specified ID card.
     */
    public function show(IDCard $idCard): JsonResponse
    {
        $this->authorize('view', $idCard);

        return response()->json([
            'success' => true,
            'data' => $idCard->load(['student', 'teacher', 'school'])
        ]);
    }

    /**
     * Remove the specified ID card.
     */
    public function destroy(IDCard $idCard): JsonResponse
    {
        $this->authorize('delete', $idCard);

        try {
            DB::beginTransaction();

            $this->idCardService->deleteIDCard($idCard);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'ID card deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete ID card',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
