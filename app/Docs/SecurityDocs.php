<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Post(
 *     path="/pin/login",
 *     tags={"Security"},
 *     summary="Login with PIN",
 *     operationId="pinLogin",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"pin"},
 *             @OA\Property(property="pin", type="string", example="1234")
 *         )
 *     ),
 *     @OA\Response(response=200, description="PIN login successful", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Post(
 *     path="/pin/set",
 *     tags={"Security"},
 *     summary="Set PIN",
 *     operationId="setPin",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"pin", "pin_confirmation"},
 *             @OA\Property(property="pin", type="string", example="1234"),
 *             @OA\Property(property="pin_confirmation", type="string", example="1234")
 *         )
 *     ),
 *     @OA\Response(response=200, description="PIN set successfully", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Post(
 *     path="/pin/verify",
 *     tags={"Security"},
 *     summary="Verify PIN",
 *     operationId="verifyPin",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"pin"},
 *             @OA\Property(property="pin", type="string", example="1234")
 *         )
 *     ),
 *     @OA\Response(response=200, description="PIN verified", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Put(
 *     path="/security/app-lock",
 *     tags={"Security"},
 *     summary="Enable or disable app lock",
 *     operationId="toggleAppLock",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"is_enabled"},
 *             @OA\Property(property="is_enabled", type="boolean", example=true)
 *         )
 *     ),
 *     @OA\Response(response=200, description="App lock updated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/security/settings",
 *     tags={"Security"},
 *     summary="Get security settings",
 *     operationId="getSecuritySettings",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Security settings fetched", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Put(
 *     path="/security/settings",
 *     tags={"Security"},
 *     summary="Update security settings",
 *     operationId="updateSecuritySettings",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="auto_lock_minutes", type="integer", example=15),
 *             @OA\Property(property="biometric_enabled", type="boolean", example=true),
 *             @OA\Property(property="remember_device", type="boolean", example=true)
 *         )
 *     ),
 *     @OA\Response(response=200, description="Security settings updated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
class SecurityDocs
{
    //
}
