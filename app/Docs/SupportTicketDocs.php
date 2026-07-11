<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/support-tickets",
 *     tags={"Support"},
 *     summary="List support tickets",
 *     operationId="supportTicketsIndex",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
 *     @OA\Parameter(name="company_id", in="query", @OA\Schema(type="integer"), description="SuperAdmin only"),
 *     @OA\Response(response=200, description="Ticket list", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Post(
 *     path="/support-tickets",
 *     tags={"Support"},
 *     summary="Create support ticket",
 *     operationId="supportTicketsStore",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"subject","message"},
 *             @OA\Property(property="subject", type="string"),
 *             @OA\Property(property="message", type="string"),
 *             @OA\Property(property="category", type="string", enum={"general","billing","technical","feature","other"}),
 *             @OA\Property(property="priority", type="string", enum={"low","normal","high","urgent"})
 *         )
 *     ),
 *     @OA\Response(response=201, description="Ticket created", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/support-tickets/{id}",
 *     tags={"Support"},
 *     summary="Get ticket with messages",
 *     operationId="supportTicketsShow",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Ticket detail", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Post(
 *     path="/support-tickets/{id}/reply",
 *     tags={"Support"},
 *     summary="Reply to ticket (chat message)",
 *     operationId="supportTicketsReply",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"message"},
 *             @OA\Property(property="message", type="string"),
 *             @OA\Property(property="is_internal", type="boolean", description="SuperAdmin only")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Message sent", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Patch(
 *     path="/support-tickets/{id}/status",
 *     tags={"Support"},
 *     summary="Update ticket status (SuperAdmin)",
 *     operationId="supportTicketsUpdateStatus",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"status"},
 *             @OA\Property(property="status", type="string", enum={"open","in_progress","waiting_on_customer","resolved","closed"}),
 *             @OA\Property(property="assigned_to", type="integer")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Status updated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 */
class SupportTicketDocs
{
    //
}
