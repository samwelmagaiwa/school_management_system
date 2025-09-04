<?php

namespace App\Modules\User\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->transform(function ($user) {
                return new UserResource($user);
            }),
        ];
    }
}