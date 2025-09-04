<?php

namespace App\Modules\Dashboard\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => $this->resource['user'],
            'school' => $this->resource['school'],
            'statistics' => $this->resource['stats'],
            'permissions' => $this->resource['permissions'],
            'menu_items' => $this->resource['menu_items'],
            'quick_stats' => $this->resource['quick_stats'],
            'recent_activities' => $this->resource['recent_activities'],
            'timestamp' => $this->resource['timestamp'],
            'server_time' => now()->toISOString(),
        ];
    }
}