<?php

namespace App\Modules\Fee\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Fee\Models\Fee;
use App\Modules\Fee\Requests\StoreFeeRequest;
use App\Modules\Fee\Requests\UpdateFeeRequest;
use App\Modules\Fee\Resources\FeeResource;
use App\Modules\Fee\Resources\FeeCollection;
use App\Modules\Fee\Services\FeeService;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FeeController extends Controller
{
    protected FeeService $feeService;

    public function __construct(FeeService $feeService)
    {
        $this->middleware('auth:sanctum');
        $this->feeService = $feeService;
    }

    /**
     * Display a listing of fees with filters and search
     */
    public function index(Request $request): JsonResponse
    {
        // Check authorization
        $user = auth()->user();
        if (!in_array($user->role, ['SuperAdmin', 'Admin', 'Teacher', 'Student', 'Parent'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        ActivityLogger::log('View Fees List', 'Fees', [
            'filters' => $request->only(['student_id', 'school_id', 'type', 'status', 'search', 'sort_by', 'sort_order'])
        ]);

        $query = Fee::with(['school', 'student.user', 'class', 'subject']);

        // Apply role-based filtering
        if ($user->isSuperAdmin()) {
            // SuperAdmin can see all fees
        } elseif ($user->isAdmin()) {
            $query->bySchool($user->school_id);
        } elseif ($user->isTeacher()) {
            $query->bySchool($user->school_id);
        } elseif ($user->isStudent()) {
            $query->byStudent($user->student->id ?? 0);
        } elseif ($user->isParent()) {
            // Parent can see their children's fees
            $childrenIds = $user->children->pluck('id')->toArray();
            $query->whereIn('student_id', $childrenIds);
        }

        // Student filter
        if ($request->filled('student_id')) {
            $query->byStudent($request->student_id);
        }

        // School filter (only for SuperAdmin)
        if ($request->filled('school_id') && $user->isSuperAdmin()) {
            $query->bySchool($request->school_id);
        }

        // Type filter
        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        // Search functionality
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'due_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $fees = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => new FeeCollection($fees),
            'meta' => [
                'current_page' => $fees->currentPage(),
                'last_page' => $fees->lastPage(),
                'per_page' => $fees->perPage(),
                'total' => $fees->total(),
            ],
            'filters' => [
                'student_id' => $request->student_id,
                'school_id' => $request->school_id,
                'type' => $request->type,
                'status' => $request->status,
                'search' => $request->search,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ]
        ]);
    }

    /**
     * Store a newly created fee
     */
    public function store(StoreFeeRequest $request): JsonResponse
    {
        // Check authorization
        if (!in_array(auth()->user()->role, ['SuperAdmin', 'Admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $feeData = $request->validated();

        // Set school_id for non-SuperAdmin users
        if (!auth()->user()->isSuperAdmin()) {
            $feeData['school_id'] = auth()->user()->school_id;
        }

        $fee = $this->feeService->createFee($feeData);

        ActivityLogger::log('Fee Created', 'Fees', [
            'fee_id' => $fee->id,
            'fee_name' => $fee->name,
            'amount' => $fee->amount,
            'student_id' => $fee->student_id,
            'school_id' => $fee->school_id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Fee created successfully',
            'data' => new FeeResource($fee->load(['school', 'student.user', 'class', 'subject']))
        ], 201);
    }

    /**
     * Display the specified fee
     */
    public function show(Fee $fee): JsonResponse
    {
        // Check authorization
        $user = auth()->user();
        if (!$user->isSuperAdmin() && 
            !($user->isAdmin() && $user->school_id === $fee->school_id) &&
            !($user->isTeacher() && $user->school_id === $fee->school_id) &&
            !($user->isStudent() && $user->student && $user->student->id === $fee->student_id) &&
            !($user->isParent() && $user->children->contains('id', $fee->student_id))) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        ActivityLogger::log('View Fee Details', 'Fees', [
            'fee_id' => $fee->id,
            'fee_name' => $fee->name
        ]);

        $fee->load(['school', 'student.user', 'class', 'subject']);

        return response()->json([
            'success' => true,
            'data' => new FeeResource($fee)
        ]);
    }

    /**
     * Update the specified fee
     */
    public function update(UpdateFeeRequest $request, Fee $fee): JsonResponse
    {
        // Check authorization
        $user = auth()->user();
        if (!$user->isSuperAdmin() && 
            !($user->isAdmin() && $user->school_id === $fee->school_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $feeData = $request->validated();
        $originalData = $fee->toArray();
        
        $fee = $this->feeService->updateFee($fee, $feeData);

        ActivityLogger::log('Fee Updated', 'Fees', [
            'fee_id' => $fee->id,
            'fee_name' => $fee->name,
            'changes' => array_diff_assoc($feeData, $originalData)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Fee updated successfully',
            'data' => new FeeResource($fee->fresh(['school', 'student.user', 'class', 'subject']))
        ]);
    }

    /**
     * Remove the specified fee
     */
    public function destroy(Fee $fee): JsonResponse
    {
        // Check authorization
        $user = auth()->user();
        if (!$user->isSuperAdmin() && 
            !($user->isAdmin() && $user->school_id === $fee->school_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        // Prevent deletion of paid fees
        if ($fee->status === 'Paid') {
            ActivityLogger::log('Fee Deletion Failed', 'Fees', [
                'fee_id' => $fee->id,
                'fee_name' => $fee->name,
                'reason' => 'Cannot delete paid fee'
            ], 'warning');

            return response()->json([
                'success' => false,
                'message' => 'Cannot delete paid fee'
            ], 422);
        }

        ActivityLogger::log('Fee Deleted', 'Fees', [
            'fee_id' => $fee->id,
            'fee_name' => $fee->name
        ]);

        $this->feeService->deleteFee($fee);

        return response()->json([
            'success' => true,
            'message' => 'Fee deleted successfully'
        ]);
    }

    /**
     * Mark fee as paid
     */
    public function markAsPaid(Request $request, Fee $fee): JsonResponse
    {
        $request->validate([
            'payment_method' => 'required|in:' . implode(',', Fee::PAYMENT_METHODS),
            'transaction_id' => 'nullable|string|max:255',
            'paid_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000'
        ]);

        $fee = $this->feeService->markAsPaid($fee, $request->all());

        ActivityLogger::log('Fee Marked as Paid', 'Fees', [
            'fee_id' => $fee->id,
            'fee_name' => $fee->name,
            'amount' => $fee->net_amount,
            'payment_method' => $fee->payment_method
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Fee marked as paid successfully',
            'data' => new FeeResource($fee)
        ]);
    }

    /**
     * Get fee statistics
     */
    public function getStatistics(): JsonResponse
    {
        $stats = $this->feeService->getFeeStatistics(
            auth()->user()->isSuperAdmin() ? null : auth()->user()->school_id
        );

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get fee types
     */
    public function getTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'types' => Fee::TYPES,
                'frequencies' => Fee::FREQUENCIES,
                'statuses' => Fee::STATUSES,
                'payment_methods' => Fee::PAYMENT_METHODS
            ]
        ]);
    }

    /**
     * Export fees data
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'excel');
        $filters = $request->only(['student_id', 'school_id', 'type', 'status', 'search']);
        
        if (!auth()->user()->isSuperAdmin()) {
            $filters['school_id'] = auth()->user()->school_id;
        }
        
        try {
            return $this->feeService->exportFees($filters, $format);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting fees: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get student fees
     */
    public function getStudentFees(Request $request, int $studentId): JsonResponse
    {
        // Check authorization
        $user = auth()->user();
        $student = \App\Modules\Student\Models\Student::findOrFail($studentId);
        
        if (!$user->isSuperAdmin() && 
            !($user->isAdmin() && $user->school_id === $student->school_id) &&
            !($user->isTeacher() && $user->school_id === $student->school_id) &&
            !($user->isStudent() && $user->student && $user->student->id === $studentId) &&
            !($user->isParent() && $user->children->contains('id', $studentId))) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }
        
        $fees = $this->feeService->getStudentFees($studentId, $request->all());
        
        return response()->json([
            'success' => true,
            'data' => $fees
        ]);
    }

    /**
     * Generate fee invoice
     */
    public function generateInvoice(Fee $fee)
    {
        try {
            return $this->feeService->generateInvoice($fee);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate payment receipt
     */
    public function generateReceipt(Fee $fee)
    {
        try {
            if ($fee->status !== 'Paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Receipt can only be generated for paid fees'
                ], 422);
            }
            
            return $this->feeService->generateReceipt($fee);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating receipt: ' . $e->getMessage()
            ], 500);
        }
    }
}
