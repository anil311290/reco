<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/ledgers",
 *     tags={"Ledgers"},
 *     summary="List ledgers",
 *     operationId="getLedgers",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
 *     @OA\Response(response=200, description="Ledgers fetched", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/ledgers/{id}",
 *     tags={"Ledgers"},
 *     summary="Get ledger details",
 *     operationId="getLedger",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Ledger fetched", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/ledgers/{id}/entries",
 *     tags={"Ledgers"},
 *     summary="Get ledger entries",
 *     operationId="getLedgerEntries",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="from_date", in="query", required=false, @OA\Schema(type="string", format="date")),
 *     @OA\Parameter(name="to_date", in="query", required=false, @OA\Schema(type="string", format="date")),
 *     @OA\Response(response=200, description="Ledger entries fetched", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/ledgers/{id}/history",
 *     tags={"Ledgers"},
 *     summary="Get ledger history",
 *     operationId="getLedgerHistory",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Ledger history fetched", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
class LedgerDocs
{
    //
}
