<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/audit-logs",
 *     tags={"Audit Logs"},
 *     summary="List audit logs",
 *     description="Paginated company audit trail with optional filters. Same data as web Admin → Audit Logs.",
 *     operationId="getAuditLogs",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="search", in="query", description="Search description or module", @OA\Schema(type="string")),
 *     @OA\Parameter(name="action", in="query", description="Filter by action (create, update, delete, etc.)", @OA\Schema(type="string")),
 *     @OA\Parameter(name="module", in="query", description="Filter by module name", @OA\Schema(type="string")),
 *     @OA\Parameter(name="user_id", in="query", description="Filter by user id", @OA\Schema(type="integer")),
 *     @OA\Parameter(name="record_id", in="query", description="Filter by related record id", @OA\Schema(type="integer")),
 *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", default=1)),
 *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=25, maximum=100)),
 *     @OA\Response(
 *         response=200,
 *         description="Audit logs list",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="logs", type="array", @OA\Items(type="object")),
 *                 @OA\Property(property="pagination", type="object",
 *                     @OA\Property(property="current_page", type="integer", example=1),
 *                     @OA\Property(property="last_page", type="integer", example=5),
 *                     @OA\Property(property="per_page", type="integer", example=25),
 *                     @OA\Property(property="total", type="integer", example=120),
 *                     @OA\Property(property="has_more", type="boolean", example=true)
 *                 ),
 *                 @OA\Property(property="statistics", type="object"),
 *                 @OA\Property(property="filters", type="object",
 *                     @OA\Property(property="actions", type="array", @OA\Items(type="string")),
 *                     @OA\Property(property="modules", type="array", @OA\Items(type="string")),
 *                     @OA\Property(property="users", type="array", @OA\Items(type="object"))
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/audit-logs/{id}",
 *     tags={"Audit Logs"},
 *     summary="Get audit log details",
 *     description="Single audit log record for the authenticated user's company.",
 *     operationId="getAuditLog",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Audit log detail", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
class AuditLogDocs
{
    //
}
