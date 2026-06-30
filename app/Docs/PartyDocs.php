<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/parties",
 *     tags={"Parties"},
 *     summary="Get all parties",
 *     description="Get list of all parties (debtors & creditors) with optional filters",
 *     operationId="getParties",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="search", in="query", description="Search by name, code, or mobile", @OA\Schema(type="string")),
 *     @OA\Parameter(name="type", in="query", description="Filter by party type", @OA\Schema(type="string", enum={"debtor", "creditor"})),
 *     @OA\Parameter(name="is_active", in="query", description="Filter by status (1=active, 0=inactive)", @OA\Schema(type="boolean")),
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Party"))
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/parties/{id}",
 *     tags={"Parties"},
 *     summary="Get party by ID",
 *     description="Get single party details by ID",
 *     operationId="getPartyById",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, description="Party ID", @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", ref="#/components/schemas/Party")
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/parties/by-type",
 *     tags={"Parties"},
 *     summary="Get parties by type",
 *     description="Get parties filtered by type for dropdown selection",
 *     operationId="getPartiesByType",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="type", in="query", required=true, description="Party type", @OA\Schema(type="string", enum={"debtor", "creditor"})),
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="text", type="string", example="DEB0001 - ABC Company"),
 *                     @OA\Property(property="type", type="string", example="debtor")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
class PartyDocs
{
    //
}
