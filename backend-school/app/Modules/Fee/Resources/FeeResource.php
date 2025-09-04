<?php

namespace App\Modules\Fee\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'amount' => $this->amount,
            'late_fee' => $this->late_fee,
            'discount' => $this->discount,
            'net_amount' => $this->net_amount,
            'type' => $this->type,
            'frequency' => $this->frequency,
            'status' => $this->status,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'paid_date' => $this->paid_date?->format('Y-m-d'),
            'payment_method' => $this->payment_method,
            'transaction_id' => $this->transaction_id,
            'notes' => $this->notes,
            'is_overdue' => $this->is_overdue,
            'days_overdue' => $this->days_overdue,
            
            // Relationships
            'school' => $this->whenLoaded('school', function () {
                return [
                    'id' => $this->school->id,
                    'name' => $this->school->name,
                    'code' => $this->school->code,
                ];
            }),
            
            'student' => $this->whenLoaded('student', function () {
                return [
                    'id' => $this->student->id,
                    'admission_no' => $this->student->admission_no,
                    'user' => $this->whenLoaded('student.user', function () {
                        return [
                            'id' => $this->student->user->id,
                            'name' => $this->student->user->full_name,
                            'email' => $this->student->user->email,
                        ];
                    }),
                ];
            }),
            
            'class' => $this->whenLoaded('class', function () {
                return [
                    'id' => $this->class->id,
                    'name' => $this->class->name,
                    'section' => $this->class->section,
                ];
            }),
            
            'subject' => $this->whenLoaded('subject', function () {
                return [
                    'id' => $this->subject->id,
                    'name' => $this->subject->name,
                    'code' => $this->subject->code,
                ];
            }),
            
            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}