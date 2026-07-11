<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Post(
 *     path="/devices/register",
 *     tags={"Devices"},
 *     summary="Register mobile device for sync and push",
 *     description="Call after login or when FCM token refreshes. Same fields can be sent on POST /login.",
 *     operationId="devicesRegister",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"device_id","device_type"},
 *             @OA\Property(property="device_id", type="string", example="android-abc123"),
 *             @OA\Property(property="device_type", type="string", enum={"web","android","ios"}, example="android"),
 *             @OA\Property(property="device_name", type="string", example="Samsung Galaxy S24"),
 *             @OA\Property(property="device_os", type="string", example="Android 14"),
 *             @OA\Property(property="fcm_token", type="string"),
 *             @OA\Property(property="push_token", type="string")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Device registered", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 */
class DeviceDocs
{
    //
}
