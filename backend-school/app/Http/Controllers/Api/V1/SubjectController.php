<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Http\Requests\StoreSubjectRequest;
use App\Http\Requests\UpdateSubjectRequest;
use App\Http\Resources\SubjectResource;
use App\Services\SubjectService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SubjectController extends Controller
{
    protected SubjectService $subjectService;

    public function __construct(SubjectService $subjectService)
    {
        $this->middleware('auth:sanctum');
        $this->subjectService = $subjectService;
    }

    /**
     * Display a listing of subjects
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Subject::class);

        $query = Subject::with(['school', 'teachers', 'classes']);

        // Apply school scope for non-SuperAdmin users
        if (!auth()->user()->isSuperAdmin()) {
            $query->bySchool(auth()->user()->school_id);
        }

        // Apply filters
        if ($request->filled('school_id') && auth()->user()->isSuperAdmin()) {
            $query->bySchool($request->school_id);
        }

        if ($request->filled('class_id')) {
            $query->whereHas('classes', function ($q) use ($request) {
                $q->where('classes.id', $request->class_id);
            });
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->boolean('status'));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $subjects = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => SubjectResource::collection($subjects),
            'meta' => [
                'current_page' => $subjects->currentPage(),
                'last_page' => $subjects->lastPage(),
                'per_page' => $subjects->perPage(),
                'total' => $subjects->total(),
            ]
        ]);
    }

    /**
     * Store a newly created subject
     */
    public function store(StoreSubjectRequest $request): JsonResponse
    {
        $this->authorize('create', Subject::class);

        $subjectData = $request->validated();

        if (!auth()->user()->isSuperAdmin()) {
            $subjectData['school_id'] = auth()->user()->school_id;
        }

        $subject = $this->subjectService->createSubject($subjectData);

        return response()->json([
            'success' => true,
            'message' => 'Subject created successfully',
            'data' => new SubjectResource($subject->load(['school']))
        ], 201);
    }

    /**
     * Display the specified subject
     */
    public function show(Subject $subject): JsonResponse
    {
        $this->authorize('view', $subject);

        $subject->load(['school', 'teachers', 'classes']);

        return response()->json([
            'success' => true,
            'data' => new SubjectResource($subject)
        ]);
    }

    /**
     * Update the specified subject
     */
    public function update(UpdateSubjectRequest $request, Subject $subject): JsonResponse
    {
        $this->authorize('update', $subject);

        $subject = $this->subjectService->updateSubject($subject, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Subject updated successfully',
            'data' => new SubjectResource($subject->fresh(['school']))
        ]);
    }

    /**
     * Remove the specified subject
     */
    public function destroy(Subject $subject): JsonResponse
    {
        $this->authorize('delete', $subject);

        $this->subjectService->deleteSubject($subject);

        return response()->json([
            'success' => true,
            'message' => 'Subject deleted successfully'
        ]);
    }
}
