<?php

declare(strict_types=1);

namespace App\Modules\Core\Support;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * The single shape every API response takes: { success, message, data }.
 */
final class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = '',
        int $status = Response::HTTP_OK,
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    public static function error(
        string $message,
        mixed $data = null,
        int $status = Response::HTTP_BAD_REQUEST,
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     */
    public static function validationError(array $errors, string $message = 'Validation failed.'): JsonResponse
    {
        return self::error($message, ['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public static function unauthorized(string $message = 'Unauthenticated.'): JsonResponse
    {
        return self::error($message, null, Response::HTTP_UNAUTHORIZED);
    }

    public static function forbidden(string $message = 'This action is not allowed.'): JsonResponse
    {
        return self::error($message, null, Response::HTTP_FORBIDDEN);
    }

    public static function notFound(string $message = 'Not found.'): JsonResponse
    {
        return self::error($message, null, Response::HTTP_NOT_FOUND);
    }
}
