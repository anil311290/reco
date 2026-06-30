<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/accounts",
 *     tags={"Accounts"},
 *     summary="Get all accounts",
 *     description="Get list of all accounts with optional filters",
 *     operationId="getAccounts",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="search", in="query", description="Search by name or code", @OA\Schema(type="string")),
 *     @OA\Parameter(name="account_type", in="query", description="Filter by account type", @OA\Schema(type="string", enum={"asset", "liability", "income", "expense", "equity"})),
 *     @OA\Parameter(name="is_active", in="query", description="Filter by status (1=active, 0=inactive)", @OA\Schema(type="boolean")),
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Account"))
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/accounts/{id}",
 *     tags={"Accounts"},
 *     summary="Get account by ID",
 *     description="Get single account details by ID",
 *     operationId="getAccountById",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, description="Account ID", @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", ref="#/components/schemas/Account")
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/accounts/by-type",
 *     tags={"Accounts"},
 *     summary="Get accounts by type",
 *     description="Get accounts filtered by type for dropdown selection",
 *     operationId="getAccountsByType",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="type", in="query", required=true, description="Account type", @OA\Schema(type="string", enum={"asset", "liability", "income", "expense", "equity"})),
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="text", type="string", example="AST0001 - Cash"),
 *                     @OA\Property(property="type", type="string", example="asset")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
class AccountDocs
{
    //
}
