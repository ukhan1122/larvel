<?php

namespace App\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Generate a success response
     *
     * @param mixed|null $data
     * @param string|null $message
     * @param int $statusCode
     * @param array $meta
     * @return JsonResponse
     */
    public function successResponse(mixed $data = null, string $message = null, int $statusCode = 200, array $meta = []): JsonResponse
    {
        $response = [
            'status' => 'success',
            'message' => $message ?? __('responses.success'),
            'data' => $data,
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Generate an error response
     *
     * @param string|null $message
     * @param int $statusCode
     * @param array|null $errors
     * @param array $meta
     * @return JsonResponse
     */
    public function errorResponse(string $message = null, int $statusCode = 400, array $errors = null, array $meta = []): JsonResponse
    {
        $response = [
            'status' => 'error',
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Generate a paginated response
     *
     * @param LengthAwarePaginator $paginator
     * @param string|null $message
     * @param int $statusCode
     * @return JsonResponse
     */
    public function paginatedResponse(LengthAwarePaginator $paginator, string $message = null, int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message ?? __('responses.success'),
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], $statusCode);
    }

    /**
     * Generate a no content response
     *
     * @param string|null $message
     * @return JsonResponse
     */
    public function noContentResponse(string $message = null): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message ?? __('responses.no_content'),
        ], 204);
    }

    /**
     * Generate a created response
     *
     * @param mixed $data
     * @param string|null $message
     * @return JsonResponse
     */
    public function createdResponse(mixed $data, ?string $message = 'Resource created successfully'): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message ?? __('responses.created'),
            'data' => $data,
        ], 201);
    }

    /**
     * Generate an unauthorized response
     *
     * @param string|null $message
     * @return JsonResponse
     */
    public function unauthorizedResponse(?string $message = 'Unauthorized'): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message ?? __('responses.unauthorized'),
        ], 401);
    }

    /**
     * Generate a forbidden response
     *
     * @param string|null $message
     * @return JsonResponse
     */
    public function forbiddenResponse(?string $message = 'Forbidden'): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message ?? __('response.forbidden'),
        ], 403);
    }

    /**
     * Validation Error Response.
     */
    public function validationErrorResponse($errors, $message = null): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message ?? __('responses.validation_failed'),
            'errors' => $errors,
        ], 422);
    }

    /**
     * Generate a not found response
     *
     * @param string|null $message
     * @return JsonResponse
     */
    public function notFoundResponse(?string $message = 'Resource not found'): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message ?? __('responses.not_found'),
        ], 404);
    }

    /**
     * Generate a server error response
     *
     * @param string|null $message
     * @param array|null $errors
     * @return JsonResponse
     */
    public function serverErrorResponse(?string $message = 'Internal server error', array $errors = null): JsonResponse
    {
        $response = [
            'status' => 'error',
            'message' => $message ?? __('response.server_error'),
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, 500);
    }

    /**
     * Generate a custom response
     *
     * @param string $status
     * @param mixed|null $data
     * @param string|null $message
     * @param int $statusCode
     * @param array $meta
     * @return JsonResponse
     */
    public function customResponse(string $status, mixed $data = null, string $message = null, int $statusCode = 200, array $meta = []): JsonResponse
    {
        $response = [
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }
}
