<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/notifications",
 *     tags={"Notifications"},
 *     summary="List in-app notifications",
 *     operationId="notificationsIndex",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", example=20)),
 *     @OA\Response(response=200, description="Notifications list", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/notifications/unread-count",
 *     tags={"Notifications"},
 *     summary="Unread notification count",
 *     operationId="notificationsUnreadCount",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Unread count", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Patch(
 *     path="/notifications/{id}/read",
 *     tags={"Notifications"},
 *     summary="Mark notification as read",
 *     operationId="notificationsMarkRead",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="Marked as read",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="unread_count", type="integer", example=3)
 *             )
 *         )
 *     )
 * )
 *
 * @OA\Post(
 *     path="/notifications/read-all",
 *     tags={"Notifications"},
 *     summary="Mark all notifications as read",
 *     operationId="notificationsMarkAllRead",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="All marked as read",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="updated", type="integer", example=5),
 *                 @OA\Property(property="unread_count", type="integer", example=0)
 *             )
 *         )
 *     )
 * )
 */
class NotificationDocs
{
    //
}
