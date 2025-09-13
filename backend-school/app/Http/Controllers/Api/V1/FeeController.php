<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Fee;
use App\Http\Requests\FeeRequest;
use App\Http\Requests\StoreFeeRequest;
use App\Http\Requests\UpdateFeeRequest;
use App\Services\FeeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class FeeController extends Controller
{
    protected FeeService $feeService;

    public function __construct(FeeService $feeService)
    {
        $this->middleware('auth:sanctum');
        $this->feeService = $feeService;
    }

    /**
     * Display a listing of fees.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Fee::class);

        $fees = $this->feeService->getAllFees($request->all());

        return response()->json([
            'success' => true,
            'data' => $fees
        ]);
    }

    /**
     * Store a newly created fee.
     */
    public function store(StoreFeeRequest $request): JsonResponse
    {
        $this->authorize('create', Fee::class);

        try {
            DB::beginTransaction();

            $fee = $this->feeService->createFee($request->validated());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Fee created successfully',
                'data' => $fee
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create fee',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified fee.
     */
    public function show(Fee $fee): JsonResponse
    {
        $this->authorize('view', $fee);

        return response()->json([
            'success' => true,
            'data' => $fee->load(['student', 'school'])
        ]);
    }

    /**
     * Update the specified fee.
     */
    public function update(UpdateFeeRequest $request, Fee $fee): JsonResponse
    {
        $this->authorize('update', $fee);

        try {
            DB::beginTransaction();

            $fee = $this->feeService->updateFee($fee, $request->validated());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Fee updated successfully',
                'data' => $fee
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update fee',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified fee.
     */
    public function destroy(Fee $fee): JsonResponse
    {
        $this->authorize('delete', $fee);

        try {
            DB::beginTransaction();

            $this->feeService->deleteFee($fee);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Fee deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete fee',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get fee statistics.
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', Fee::class);

        $stats = $this->feeService->getFeeStatistics();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Record a fee payment.
     */
    public function recordPayment(Request $request): JsonResponse
    {
        $this->authorize('create', Fee::class);

        try {
            DB::beginTransaction();

            $payment = $this->feeService->recordPayment($request->all());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully',
                'data' => $payment
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to record payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get fee payments.
     */
    public function getPayments(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Fee::class);

        $payments = $this->feeService->getPayments($request->all());

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    /**
     * Generate fee report.
     */
    public function report(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Fee::class);

        $report = $this->feeService->generateReport($request->all());

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }
}
