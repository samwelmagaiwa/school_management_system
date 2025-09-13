<?php

namespace App\Services;

use App\Models\Subject;

use Illuminate\Support\Facades\DB;

class SubjectService
{
    /**
     * Create a new subject
     */
    public function createSubject(array $data): Subject
    {
        return DB::transaction(function () use ($data) {
            return Subject::create($data);
        });
    }

    /**
     * Update an existing subject
     */
    public function updateSubject(Subject $subject, array $data): Subject
    {
        return DB::transaction(function () use ($subject, $data) {
            $subject->update($data);
            return $subject;
        });
    }

    /**
     * Delete a subject
     */
    public function deleteSubject(Subject $subject): bool
    {
        return DB::transaction(function () use ($subject) {
            return $subject->delete();
        });
    }

    /**
     * Get subject statistics
     */
    public function getSubjectStatistics(?int $schoolId = null): array
    {
        $query = Subject::query();

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $totalSubjects = $query->count();
        $activeSubjects = $query->where('status', true)->count();
        $inactiveSubjects = $totalSubjects - $activeSubjects;

        // Statistics by type
        $typeStats = [];
        foreach (Subject::TYPES as $type) {
            $typeQuery = clone $query;
            $typeStats[$type] = $typeQuery->where('type', $type)->count();
        }

        // Subjects with teachers assigned
        $subjectsWithTeachers = $query->whereNotNull('teacher_id')->count();
        $subjectsWithoutTeachers = $totalSubjects - $subjectsWithTeachers;

        return [
            'total_subjects' => $totalSubjects,
            'active_subjects' => $activeSubjects,
            'inactive_subjects' => $inactiveSubjects,
            'subjects_with_teachers' => $subjectsWithTeachers,
            'subjects_without_teachers' => $subjectsWithoutTeachers,
            'by_type' => $typeStats,
            'average_credits' => $query->avg('credits') ?? 0,
        ];
    }

    /**
     * Assign teacher to subject
     */
    public function assignTeacher(Subject $subject, int $teacherId): Subject
    {
        return DB::transaction(function () use ($subject, $teacherId) {
            $subject->update(['teacher_id' => $teacherId]);
            return $subject;
        });
    }

    /**
     * Remove teacher from subject
     */
    public function removeTeacher(Subject $subject): Subject
    {
        return DB::transaction(function () use ($subject) {
            $subject->update(['teacher_id' => null]);
            return $subject;
        });
    }

    /**
     * Bulk update subject status
     */
    public function bulkUpdateStatus(array $subjectIds, bool $status): int
    {
        return DB::transaction(function () use ($subjectIds, $status) {
            return Subject::whereIn('id', $subjectIds)->update(['status' => $status]);
        });
    }

    /**
     * Get subjects by teacher
     */
    public function getSubjectsByTeacher(int $teacherId): \Illuminate\Database\Eloquent\Collection
    {
        return Subject::where('teacher_id', $teacherId)
                     ->active()
                     ->with(['school', 'class'])
                     ->get();
    }
    
    /**
     * Export subjects data
     */
    public function exportSubjects(array $filters, string $format = 'excel')
    {
        // Build query with filters
        $query = Subject::with(['school', 'teacher.user', 'class']);
        
        if (isset($filters['school_id'])) {
            $query->where('school_id', $filters['school_id']);
        }
        
        if (isset($filters['class_id'])) {
            $query->where('class_id', $filters['class_id']);
        }
        
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }
        
        $subjects = $query->get();
        
        // Generate export file
        $filename = 'subjects_export_' . date('Y-m-d_H-i-s') . ($format === 'excel' ? '.xlsx' : '.csv');
        
        $headers = [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ];
        
        return response()->streamDownload(function () use ($subjects) {
            $output = fopen('php://output', 'w');
            
            // Write headers
            fputcsv($output, [
                'Name', 'Code', 'Type', 'Credits', 'Class', 'Teacher', 
                'Description', 'Status', 'School', 'Created Date'
            ]);
            
            // Write data
            foreach ($subjects as $subject) {
                fputcsv($output, [
                    $subject->name,
                    $subject->code,
                    $subject->type,
                    $subject->credits,
                    $subject->class->name ?? '',
                    $subject->teacher->user->name ?? '',
                    $subject->description,
                    $subject->status ? 'Active' : 'Inactive',
                    $subject->school->name ?? '',
                    $subject->created_at->format('Y-m-d')
                ]);
            }
            
            fclose($output);
        }, $filename, $headers);
    }
    
    /**
     * Assign multiple teachers to subject
     */
    public function assignTeachers(Subject $subject, array $teacherIds): array
    {
        return DB::transaction(function () use ($subject, $teacherIds) {
            // For now, we'll just assign the first teacher as primary
            // In a full implementation, you might have a pivot table for multiple teachers
            $primaryTeacherId = $teacherIds[0] ?? null;
            
            if ($primaryTeacherId) {
                $subject->update(['teacher_id' => $primaryTeacherId]);
            }
            
            return [
                'assigned' => count($teacherIds),
                'primary_teacher_id' => $primaryTeacherId,
                'subject_id' => $subject->id
            ];
        });
    }
    
    /**
     * Get subject prerequisites
     */
    public function getSubjectPrerequisites(Subject $subject): array
    {
        // Placeholder implementation - would need a prerequisites table/relationship
        return [
            'prerequisites' => [], // Would be actual prerequisite subjects
            'recommendations' => [], // Recommended prior knowledge
            'difficulty_level' => $subject->difficulty_level ?? 'Intermediate',
            'estimated_study_hours' => $subject->credits * 15 ?? 45
        ];
    }
}
