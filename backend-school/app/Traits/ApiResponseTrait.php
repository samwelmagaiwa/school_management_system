<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponseTrait
{
    /**
     * Return a successful response
     */
    protected function successResponse(mixed $data = null, string $message = 'Operation completed successfully', int $status = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            if ($data instanceof LengthAwarePaginator) {
                $response['data'] = $data->items();
                $response['meta'] = [
                    'current_page' => $data->currentPage(),
                    'last_page' => $data->lastPage(),
                    'per_page' => $data->perPage(),
                    'total' => $data->total(),
                    'from' => $data->firstItem(),
                    'to' => $data->lastItem(),
                ];
            } else {
                $response['data'] = $data;
            }
        }

        return response()->json($response, $status);
    }

    /**
     * Return an error response
     */
    protected function errorResponse(string $message = 'Operation failed', int $status = 400, mixed $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Return a validation error response
     */
    protected function validationErrorResponse(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], 422);
    }

    /**
     * Return an unauthorized response
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized access'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 403);
    }

    /**
     * Return a not found response
     */
    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 404);
    }

    /**
     * Return a created response
     */
    protected function createdResponse(mixed $data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Return a no content response
     */
    protected function noContentResponse(string $message = 'Operation completed successfully'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
        ], 204);
    }
}
