<?php

namespace App\Modules\Student\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'school_id' => $this->school_id,
            'admission_no' => $this->admission_no,
            'class_id' => $this->class_id,
            'section' => $this->section,
            'roll_number' => $this->roll_number,
            'admission_date' => $this->admission_date?->format('Y-m-d'),
            'parent_name' => $this->parent_name,
            'parent_phone' => $this->parent_phone,
            'parent_email' => $this->parent_email,
            'emergency_contact' => $this->emergency_contact,
            'blood_group' => $this->blood_group,
            'medical_conditions' => $this->medical_conditions,
            'transport_required' => $this->transport_required,
            'status' => $this->status,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'first_name' => $this->user->first_name,
                    'last_name' => $this->user->last_name,
                    'full_name' => $this->user->full_name,
                    'email' => $this->user->email,
                    'phone' => $this->user->phone,
                    'date_of_birth' => $this->user->date_of_birth?->format('Y-m-d'),
                    'gender' => $this->user->gender,
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
            'full_name' => $this->full_name,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}