<?php

namespace App\Modules\User\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'gender' => $this->gender,
            'role' => $this->role,
            'school_id' => $this->school_id,
            'school' => $this->whenLoaded('school', function () {
                return [
                    'id' => $this->school->id,
                    'name' => $this->school->name,
                    'code' => $this->school->code,
                ];
            }),
            'profile_picture' => $this->profile_picture,
            'status' => $this->status,
            'email_verified_at' => $this->email_verified_at?->format('Y-m-d H:i:s'),
            'student' => $this->whenLoaded('student', function () {
                return [
                    'id' => $this->student->id,
                    'admission_no' => $this->student->admission_no,
                    'class_id' => $this->student->class_id,
                    'section' => $this->student->section,
                    'roll_number' => $this->student->roll_number,
                ];
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}