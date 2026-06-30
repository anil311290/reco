<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ResponseHelper
{
    /**
     * Return a success JSON response.
     */
    public static function success($data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Return an error JSON response.
     */
    public static function error(string $message = 'Error', int $code = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Return a validation error JSON response.
     */
    public static function validationError($errors, string $message = 'Validation Error'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], 422);
    }

    /**
     * Return a not found JSON response.
     */
    public static function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 404);
    }

    /**
     * Return an unauthorized JSON response.
     */
    public static function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 401);
    }

    /**
     * Return a forbidden JSON response.
     */
    public static function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 403);
    }
}
