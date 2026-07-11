<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Reco API",
 *     version="1.0.0",
 *     description="Reco - Offline-First Accounting SaaS Platform API Documentation",
 *     @OA\Contact(
 *         name="Reco Support",
 *         email="support@reco.app",
 *         url="https://reco.app/support"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://127.0.0.1:8000/api/v1",
 *     description="Local Development Server"
 * )
 *
 * @OA\Server(
 *     url="https://reco.aaochaletaxi.app/api/v1",
 *     description="Production Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your Bearer token in the format: 1|abc123..."
 * )
 */
class ApiInfo
{
    //
}
