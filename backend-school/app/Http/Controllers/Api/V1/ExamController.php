<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Http\Requests\StoreExamRequest;
use App\Http\Requests\UpdateExamRequest;
use App\Http\Resources\ExamResource;
use App\Services\ExamService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ExamController extends Controller
{
    protected ExamService $examService;

    public function __construct(ExamService $examService)
    {
        $this->middleware('auth:sanctum');
        $this->examService = $examService;
    }

    /**
     * Display a listing of exams
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Exam::class);

        $query = Exam::with(['school', 'class', 'subject']);

        // Apply school scope for non-SuperAdmin users
        if (!auth()->user()->isSuperAdmin()) {
            $query->bySchool(auth()->user()->school_id);
        }

        // Apply filters
        if ($request->filled('school_id') && auth()->user()->isSuperAdmin()) {
            $query->bySchool($request->school_id);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('exam_type')) {
            $query->where('exam_type', $request->exam_type);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'exam_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $exams = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => ExamResource::collection($exams),
            'meta' => [
                'current_page' => $exams->currentPage(),
                'last_page' => $exams->lastPage(),
                'per_page' => $exams->perPage(),
                'total' => $exams->total(),
            ]
        ]);
    }

    /**
     * Store a newly created exam
     */
    public function store(StoreExamRequest $request): JsonResponse
    {
        $this->authorize('create', Exam::class);

        $examData = $request->validated();

        if (!auth()->user()->isSuperAdmin()) {
            $examData['school_id'] = auth()->user()->school_id;
        }

        $exam = $this->examService->createExam($examData);

        return response()->json([
            'success' => true,
            'message' => 'Exam created successfully',
            'data' => new ExamResource($exam->load(['school', 'class', 'subject']))
        ], 201);
    }

    /**
     * Display the specified exam
     */
    public function show(Exam $exam): JsonResponse
    {
        $this->authorize('view', $exam);

        $exam->load(['school', 'class', 'subject', 'results']);

        return response()->json([
            'success' => true,
            'data' => new ExamResource($exam)
        ]);
    }

    /**
     * Update the specified exam
     */
    public function update(UpdateExamRequest $request, Exam $exam): JsonResponse
    {
        $this->authorize('update', $exam);

        $exam = $this->examService->updateExam($exam, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Exam updated successfully',
            'data' => new ExamResource($exam->fresh(['school', 'class', 'subject']))
        ]);
    }

    /**
     * Remove the specified exam
     */
    public function destroy(Exam $exam): JsonResponse
    {
        $this->authorize('delete', $exam);

        $this->examService->deleteExam($exam);

        return response()->json([
            'success' => true,
            'message' => 'Exam deleted successfully'
        ]);
    }
}
