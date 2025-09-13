<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'admission_number' => $this->admission_number,
            'roll_number' => $this->roll_number,
            'section' => $this->section,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'age' => $this->age,
            'gender' => $this->gender,
            'blood_group' => $this->blood_group,
            'phone' => $this->phone,
            'address' => $this->address,
            'parent_name' => $this->parent_name,
            'parent_phone' => $this->parent_phone,
            'parent_email' => $this->parent_email,
            'admission_date' => $this->admission_date?->format('Y-m-d'),
            'medical_info' => $this->medical_info,
            'emergency_contacts' => $this->emergency_contacts,
            'status' => $this->status,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            
            // Relationships
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'first_name' => $this->user->first_name,
                    'last_name' => $this->user->last_name,
                    'full_name' => $this->user->full_name,
                    'email' => $this->user->email,
                    'profile_picture' => $this->user->profile_picture,
                ];
            }),
            
            'school' => $this->whenLoaded('school', function () {
                return [
                    'id' => $this->school->id,
                    'name' => $this->school->name,
                    'code' => $this->school->code,
                ];
            }),
            
            'class' => $this->whenLoaded('schoolClass', function () {
                return [
                    'id' => $this->schoolClass->id,
                    'name' => $this->schoolClass->name,
                    'grade' => $this->schoolClass->grade,
                    'section' => $this->schoolClass->section,
                ];
            }),
            
            'parent' => $this->whenLoaded('parent', function () {
                return [
                    'id' => $this->parent->id,
                    'first_name' => $this->parent->first_name,
                    'last_name' => $this->parent->last_name,
                    'full_name' => $this->parent->full_name,
                    'email' => $this->parent->email,
                    'phone' => $this->parent->phone,
                ];
            }),
            
            // Computed attributes
            'full_name' => $this->full_name,
            'attendance_percentage' => $this->when(
                $request->has('include_attendance'),
                fn() => $this->getAttendancePercentage()
            ),
        ];
    }
}