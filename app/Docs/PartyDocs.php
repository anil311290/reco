<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/parties",
 *     tags={"Parties"},
 *     summary="Get all parties",
 *     operationId="getParties",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
 *     @OA\Parameter(name="type", in="query", @OA\Schema(type="string", enum={"debtor","creditor"})),
 *     @OA\Parameter(name="is_active", in="query", @OA\Schema(type="boolean")),
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Post(
 *     path="/parties",
 *     tags={"Parties"},
 *     summary="Create party",
 *     operationId="createParty",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(required=true, @OA\JsonContent(
 *         required={"name","type","address","state_id","city_id","postal_code","opening_balance_type"},
 *         @OA\Property(property="name", type="string"),
 *         @OA\Property(property="type", type="string", enum={"debtor","creditor"}),
 *         @OA\Property(property="mobile", type="string"),
 *         @OA\Property(property="email", type="string"),
 *         @OA\Property(property="address", type="string"),
 *         @OA\Property(property="state_id", type="integer"),
 *         @OA\Property(property="city_id", type="integer"),
 *         @OA\Property(property="postal_code", type="string"),
 *         @OA\Property(property="opening_balance", type="number"),
 *         @OA\Property(property="opening_balance_type", type="string", enum={"debit","credit"})
 *     )),
 *     @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/parties/by-type",
 *     tags={"Parties"},
 *     summary="Get parties by type",
 *     operationId="getPartiesByType",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="type", in="query", required=true, @OA\Schema(type="string", enum={"debtor","creditor"})),
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/parties/{id}",
 *     tags={"Parties"},
 *     summary="Get party by ID",
 *     operationId="getPartyById",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Put(
 *     path="/parties/{id}",
 *     tags={"Parties"},
 *     summary="Update party",
 *     operationId="updateParty",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Delete(
 *     path="/parties/{id}",
 *     tags={"Parties"},
 *     summary="Delete party",
 *     operationId="deleteParty",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Deleted", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Patch(
 *     path="/parties/{id}/status",
 *     tags={"Parties"},
 *     summary="Change party status",
 *     operationId="changePartyStatus",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(required=true, @OA\JsonContent(required={"status"}, @OA\Property(property="status", type="boolean"))),
 *     @OA\Response(response=200, description="Status updated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 */
class PartyDocs
{
    //
}
