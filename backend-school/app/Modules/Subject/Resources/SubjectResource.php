<?php

namespace App\Modules\Subject\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'full_name' => $this->full_name,
            'description' => $this->description,
            'credits' => $this->credits,
            'type' => $this->type,
            'status' => $this->status,
            'syllabus' => $this->syllabus,
            'books_required' => $this->books_required,
            'assessment_criteria' => $this->assessment_criteria,
            
            // Relationships
            'school' => $this->whenLoaded('school', function () {
                return [
                    'id' => $this->school->id,
                    'name' => $this->school->name,
                    'code' => $this->school->code,
                ];
            }),
            
            'teacher' => $this->whenLoaded('teacher', function () {
                return [
                    'id' => $this->teacher->id,
                    'name' => $this->teacher->full_name,
                    'email' => $this->teacher->email,
                ];
            }),
            
            'class' => $this->whenLoaded('class', function () {
                return [
                    'id' => $this->class->id,
                    'name' => $this->class->name,
                    'section' => $this->class->section,
                ];
            }),

            // Counts
            'exams_count' => $this->whenCounted('exams'),
            'fees_count' => $this->whenCounted('fees'),
            
            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}