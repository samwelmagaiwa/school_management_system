<?php

namespace App\Modules\School\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SchoolResource extends JsonResource
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
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'logo' => $this->logo,
            'established_date' => $this->established_date?->format('Y-m-d'),
            'principal_name' => $this->principal_name,
            'description' => $this->description,
            'status' => $this->status,
            'users_count' => $this->whenCounted('users'),
            'students_count' => $this->whenCounted('students'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}