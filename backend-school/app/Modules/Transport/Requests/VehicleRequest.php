<?php

namespace App\Modules\Transport\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'school_id' => 'required|exists:schools,id',
            'vehicle_number' => 'required|string|max:20',
            'model' => 'required|string|max:100',
            'capacity' => 'required|integer|min:1|max:100',
            'driver_name' => 'required|string|max:255',
            'driver_phone' => 'required|string|max:20',
            'driver_license' => 'required|string|max:50',
            'route_id' => 'nullable|exists:transport_routes,id',
            'is_active' => 'boolean',
        ];
    }
}