<?php

namespace App\Modules\School\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SchoolCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->transform(function ($school) {
                return new SchoolResource($school);
            }),
        ];
    }
}