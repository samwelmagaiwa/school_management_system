<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\Student;
use App\Models\Subject;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExamService
{
    /**
     * Get exams with filters and pagination
     */
    public function getExams(array $filters = [])
    {
        $query = Exam::with(['school', 'class', 'subject']);

        // Apply filters
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('academic_year', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['school_id'])) {
            $query->where('school_id', $filters['school_id']);
        }

        if (isset($filters['class_id'])) {
            $query->where('class_id', $filters['class_id']);
        }

        if (isset($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }

        if (isset($filters['academic_year'])) {
            $query->where('academic_year', $filters['academic_year']);
        }

        if (isset($filters['exam_date_from'])) {
            $query->where('exam_date', '>=', $filters['exam_date_from']);
        }

        if (isset($filters['exam_date_to'])) {
            $query->where('exam_date', '<=', $filters['exam_date_to']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'exam_date';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $filters['per_page'] ?? 15;
        return $query->paginate($perPage);
    }

    /**
     * Create a new exam
     */
    public function createExam(array $data): Exam
    {
        return DB::transaction(function () use ($data) {
            return Exam::create($data);
        });
    }

    /**
     * Update an exam
     */
    public function updateExam(Exam $exam, array $data): Exam
    {
        return DB::transaction(function () use ($exam, $data) {
            $exam->update($data);
            return $exam;
        });
    }

    /**
     * Delete an exam
     */
    public function deleteExam(Exam $exam): bool
    {
        return DB::transaction(function () use ($exam) {
            // Delete related results first
            $exam->results()->delete();
            return $exam->delete();
        });
    }

    /**
     * Get exam statistics
     */
    public function getExamStatistics(?int $schoolId = null): array
    {
        $query = Exam::query();

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $totalExams = $query->count();
        $upcomingExams = $query->clone()->where('exam_date', '>', now())->count();
        $completedExams = $query->clone()->where('exam_date', '<=', now())->count();
        $todayExams = $query->clone()->whereDate('exam_date', today())->count();

        // Exams by subject
        $examsBySubject = $query->clone()
            ->join('subjects', 'exams.subject_id', '=', 'subjects.id')
            ->select('subjects.name as subject_name', DB::raw('count(*) as exam_count'))
            ->groupBy('subjects.id', 'subjects.name')
            ->get()
            ->pluck('exam_count', 'subject_name')
            ->toArray();

        // Exams by class
        $examsByClass = $query->clone()
            ->join('school_classes', 'exams.class_id', '=', 'school_classes.id')
            ->select('school_classes.name as class_name', DB::raw('count(*) as exam_count'))
            ->groupBy('school_classes.id', 'school_classes.name')
            ->get()
            ->pluck('exam_count', 'class_name')
            ->toArray();

        // Average marks statistics
        $resultsQuery = ExamResult::query()
            ->join('exams', 'exam_results.exam_id', '=', 'exams.id');

        if ($schoolId) {
            $resultsQuery->where('exams.school_id', $schoolId);
        }

        $averageMarks = $resultsQuery->avg('marks_obtained') ?? 0;
        $highestMarks = $resultsQuery->max('marks_obtained') ?? 0;
        $lowestMarks = $resultsQuery->min('marks_obtained') ?? 0;

        // Pass/Fail statistics
        $totalResults = $resultsQuery->count();
        $passedResults = $resultsQuery->clone()
            ->whereRaw('exam_results.marks_obtained >= exams.pass_marks')
            ->count();
        $failedResults = $totalResults - $passedResults;
        $passPercentage = $totalResults > 0 ? ($passedResults / $totalResults) * 100 : 0;

        return [
            'total_exams' => $totalExams,
            'upcoming_exams' => $upcomingExams,
            'completed_exams' => $completedExams,
            'today_exams' => $todayExams,
            'exams_by_subject' => $examsBySubject,
            'exams_by_class' => $examsByClass,
            'results_statistics' => [
                'total_results' => $totalResults,
                'passed_results' => $passedResults,
                'failed_results' => $failedResults,
                'pass_percentage' => round($passPercentage, 2),
                'average_marks' => round($averageMarks, 2),
                'highest_marks' => $highestMarks,
                'lowest_marks' => $lowestMarks,
            ]
        ];
    }

    /**
     * Store exam results
     */
    public function storeExamResults(Exam $exam, array $data): array
    {
        return DB::transaction(function () use ($exam, $data) {
            $results = [];

            foreach ($data['results'] as $resultData) {
                // Check if result already exists
                $existingResult = ExamResult::where('exam_id', $exam->id)
                    ->where('student_id', $resultData['student_id'])
                    ->first();

                if ($existingResult) {
                    // Update existing result
                    $existingResult->update([
                        'marks_obtained' => $resultData['marks_obtained'],
                        'grade' => $this->calculateGrade($resultData['marks_obtained'], $exam->total_marks),
                        'remarks' => $resultData['remarks'] ?? null
                    ]);
                    $results[] = $existingResult;
                } else {
                    // Create new result
                    $result = ExamResult::create([
                        'exam_id' => $exam->id,
                        'student_id' => $resultData['student_id'],
                        'marks_obtained' => $resultData['marks_obtained'],
                        'grade' => $this->calculateGrade($resultData['marks_obtained'], $exam->total_marks),
                        'remarks' => $resultData['remarks'] ?? null
                    ]);
                    $results[] = $result;
                }
            }

            return $results;
        });
    }

    /**
     * Get exam results
     */
    public function getExamResults(Exam $exam)
    {
        return $exam->results()
            ->with(['student.user'])
            ->orderBy('marks_obtained', 'desc')
            ->get()
            ->map(function ($result) use ($exam) {
                return [
                    'id' => $result->id,
                    'student' => [
                        'id' => $result->student->id,
                        'name' => $result->student->user->name,
                        'student_id' => $result->student->student_id,
                    ],
                    'marks_obtained' => $result->marks_obtained,
                    'total_marks' => $exam->total_marks,
                    'percentage' => round(($result->marks_obtained / $exam->total_marks) * 100, 2),
                    'grade' => $result->grade,
                    'status' => $result->marks_obtained >= $exam->pass_marks ? 'Pass' : 'Fail',
                    'remarks' => $result->remarks,
                ];
            });
    }

    /**
     * Generate exam report
     */
    public function generateExamReport(Exam $exam): array
    {
        $results = $this->getExamResults($exam);
        
        $totalStudents = $results->count();
        $passedStudents = $results->where('status', 'Pass')->count();
        $failedStudents = $results->where('status', 'Fail')->count();
        $passPercentage = $totalStudents > 0 ? ($passedStudents / $totalStudents) * 100 : 0;

        $averageMarks = $results->avg('marks_obtained');
        $highestMarks = $results->max('marks_obtained');
        $lowestMarks = $results->min('marks_obtained');

        // Grade distribution
        $gradeDistribution = $results->groupBy('grade')->map(function ($group) {
            return $group->count();
        })->toArray();

        // Top performers
        $topPerformers = $results->sortByDesc('marks_obtained')->take(5)->values();

        return [
            'exam' => [
                'id' => $exam->id,
                'name' => $exam->name,
                'exam_date' => $exam->exam_date,
                'total_marks' => $exam->total_marks,
                'pass_marks' => $exam->pass_marks,
                'class' => $exam->class->name,
                'subject' => $exam->subject->name,
            ],
            'statistics' => [
                'total_students' => $totalStudents,
                'passed_students' => $passedStudents,
                'failed_students' => $failedStudents,
                'pass_percentage' => round($passPercentage, 2),
                'average_marks' => round($averageMarks, 2),
                'highest_marks' => $highestMarks,
                'lowest_marks' => $lowestMarks,
            ],
            'grade_distribution' => $gradeDistribution,
            'top_performers' => $topPerformers,
            'all_results' => $results,
        ];
    }

    /**
     * Calculate grade based on marks
     */
    private function calculateGrade(float $marksObtained, float $totalMarks): string
    {
        $percentage = ($marksObtained / $totalMarks) * 100;

        if ($percentage >= 90) return 'A+';
        if ($percentage >= 80) return 'A';
        if ($percentage >= 70) return 'B+';
        if ($percentage >= 60) return 'B';
        if ($percentage >= 50) return 'C+';
        if ($percentage >= 40) return 'C';
        if ($percentage >= 33) return 'D';
        return 'F';
    }

    /**
     * Get upcoming exams
     */
    public function getUpcomingExams(?int $schoolId = null, int $limit = 10)
    {
        $query = Exam::with(['school', 'class', 'subject'])
            ->where('exam_date', '>', now())
            ->orderBy('exam_date', 'asc');

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Get student exam history
     */
    public function getStudentExamHistory(int $studentId, ?int $schoolId = null)
    {
        $query = ExamResult::with(['exam.class', 'exam.subject'])
            ->where('student_id', $studentId);

        if ($schoolId) {
            $query->whereHas('exam', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Bulk create exams for multiple classes/subjects
     */
    public function bulkCreateExams(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $createdExams = [];

            foreach ($data['class_ids'] as $classId) {
                foreach ($data['subject_ids'] as $subjectId) {
                    $examData = array_merge($data['exam_data'], [
                        'class_id' => $classId,
                        'subject_id' => $subjectId,
                    ]);

                    $exam = Exam::create($examData);
                    $createdExams[] = $exam;
                }
            }

            return $createdExams;
        });
    }
}