<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseApiController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Return a successful response
     *
     * @param mixed $data
     * @param string $message
     * @param array $meta
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function successResponse(
        $data = null,
        string $message = 'Success',
        array $meta = [],
        int $statusCode = Response::HTTP_OK
    ): JsonResponse {
        return ApiResponse::success($data, $message, $meta, $statusCode);
    }

    /**
     * Return a created response
     *
     * @param mixed $data
     * @param string $message
     * @param array $meta
     * @return JsonResponse
     */
    protected function createdResponse(
        $data = null,
        string $message = 'Resource created successfully',
        array $meta = []
    ): JsonResponse {
        return ApiResponse::created($data, $message, $meta);
    }

    /**
     * Return a success response for DELETE operations
     *
     * @param string $message Human readable success message
     * @param array $meta Additional metadata
     * @return JsonResponse
     */
    protected function noContentResponse(
        string $message = 'Resource deleted successfully',
        array $meta = []
    ): JsonResponse {
        return ApiResponse::noContent($message, $meta);
    }

    /**
     * Return an error response
     *
     * @param string $message
     * @param array $errors
     * @param array $meta
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function errorResponse(
        string $message = 'An error occurred',
        array $errors = [],
        array $meta = [],
        int $statusCode = Response::HTTP_BAD_REQUEST
    ): JsonResponse {
        return ApiResponse::error($message, $errors, $meta, $statusCode);
    }

    /**
     * Return a validation error response
     *
     * @param array $errors
     * @param string $message
     * @return JsonResponse
     */
    protected function validationErrorResponse(
        array $errors,
        string $message = 'Validation failed'
    ): JsonResponse {
        return ApiResponse::validationError($errors, $message);
    }

    /**
     * Return an unauthorized response
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function unauthorizedResponse(
        string $message = 'Unauthorized'
    ): JsonResponse {
        return ApiResponse::unauthorized($message);
    }

    /**
     * Return a forbidden response
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function forbiddenResponse(
        string $message = 'Access denied'
    ): JsonResponse {
        return ApiResponse::forbidden($message);
    }

    /**
     * Return a not found response
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function notFoundResponse(
        string $message = 'Resource not found'
    ): JsonResponse {
        return ApiResponse::notFound($message);
    }

    /**
     * Return a server error response
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function serverErrorResponse(
        string $message = 'Internal server error'
    ): JsonResponse {
        return ApiResponse::serverError($message);
    }
}
