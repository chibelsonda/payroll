<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiResponse
{
    /**
     * Create a successful API response
     *
     * @param mixed $data The response data
     * @param string $message Human readable success message
     * @param array $meta Additional metadata (pagination, etc.)
     * @param int $statusCode HTTP status code
     * @return JsonResponse
     */
    public static function success(
        $data = null,
        string $message = 'Success',
        array $meta = [],
        int $statusCode = Response::HTTP_OK
    ): JsonResponse {
        [$data, $meta] = self::extractPaginationData($data, $meta);

        return response()->json(
            self::buildResponseStructure(true, $message, $data, $meta),
            $statusCode
        );
    }

    /**
     * Create an error API response
     *
     * @param string $message Human readable error message
     * @param array $errors Detailed error information
     * @param array $meta Additional metadata
     * @param int $statusCode HTTP status code
     * @return JsonResponse
     */
    public static function error(
        string $message = 'An error occurred',
        array $errors = [],
        array $meta = [],
        int $statusCode = Response::HTTP_BAD_REQUEST
    ): JsonResponse {
        return response()->json(
            self::buildResponseStructure(false, $message, null, $meta, $errors),
            $statusCode
        );
    }

    /**
     * Create a validation error response
     *
     * @param array $errors Validation errors array
     * @param string $message Human readable message
     * @return JsonResponse
     */
    public static function validationError(
        array $errors,
        string $message = 'Validation failed'
    ): JsonResponse {
        return self::error($message, $errors, [], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Create an unauthorized error response
     *
     * @param string $message Human readable message
     * @return JsonResponse
     */
    public static function unauthorized(
        string $message = 'Unauthorized'
    ): JsonResponse {
        return self::error($message, [], [], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Create a forbidden error response
     *
     * @param string $message Human readable message
     * @return JsonResponse
     */
    public static function forbidden(
        string $message = 'Access denied'
    ): JsonResponse {
        return self::error($message, [], [], Response::HTTP_FORBIDDEN);
    }

    /**
     * Create a not found error response
     *
     * @param string $message Human readable message
     * @return JsonResponse
     */
    public static function notFound(
        string $message = 'Resource not found'
    ): JsonResponse {
        return self::error($message, [], [], Response::HTTP_NOT_FOUND);
    }

    /**
     * Create a server error response
     *
     * @param string $message Human readable message
     * @return JsonResponse
     */
    public static function serverError(
        string $message = 'Internal server error'
    ): JsonResponse {
        return self::error($message, [], [], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Create a created success response (for POST operations)
     *
     * @param mixed $data The created resource data
     * @param string $message Human readable success message
     * @param array $meta Additional metadata
     * @return JsonResponse
     */
    public static function created(
        $data = null,
        string $message = 'Resource created successfully',
        array $meta = []
    ): JsonResponse {
        return self::success($data, $message, $meta, Response::HTTP_CREATED);
    }

    /**
     * Create a success response for DELETE operations
     *
     * Returns a success message in the standard API response format.
     *
     * @param string $message Human readable success message
     * @param array $meta Additional metadata
     * @return JsonResponse
     */
    public static function noContent(
        string $message = 'Resource deleted successfully',
        array $meta = []
    ): JsonResponse {
        return response()->json(
            self::buildResponseStructure(true, $message, null, $meta),
            Response::HTTP_OK
        );
    }

    /**
     * Extract pagination data from paginator and merge with existing meta
     *
     * @param mixed $data The data that might be a paginator
     * @param array $meta Existing metadata
     * @return array{0: mixed, 1: array} Tuple of [extracted data, merged meta]
     */
    private static function extractPaginationData($data, array $meta): array
    {
        if ($data instanceof LengthAwarePaginator) {
            $meta = array_merge($meta, [
                'pagination' => [
                    'current_page' => $data->currentPage(),
                    'last_page' => $data->lastPage(),
                    'per_page' => $data->perPage(),
                    'total' => $data->total(),
                    'from' => $data->firstItem(),
                    'to' => $data->lastItem(),
                    'has_more_pages' => $data->hasMorePages(),
                ]
            ]);
            $data = $data->items();
        }

        return [$data, $meta];
    }

    /**
     * Build the standardized API response structure
     *
     * @param bool $success Whether the request was successful
     * @param string $message Human readable message
     * @param mixed $data Response data (null for errors)
     * @param array $meta Additional metadata
     * @param array|null $errors Error details (null for success responses)
     * @return array Response structure array
     */
    private static function buildResponseStructure(
        bool $success,
        string $message,
        $data = null,
        array $meta = [],
        ?array $errors = null
    ): array {
        $response = [
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
        ];

        // Only include errors field for error responses
        if (!$success && $errors !== null) {
            $response['errors'] = $errors;
        }

        return $response;
    }
}
