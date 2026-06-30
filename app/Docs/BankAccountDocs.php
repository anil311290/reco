<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/bank-accounts",
 *     tags={"Bank Accounts"},
 *     summary="List bank accounts",
 *     operationId="getBankAccounts",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/bank-accounts/{id}",
 *     tags={"Bank Accounts"},
 *     summary="Get bank account by ID",
 *     operationId="getBankAccount",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Post(
 *     path="/bank-accounts",
 *     tags={"Bank Accounts"},
 *     summary="Create bank account",
 *     operationId="createBankAccount",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(required=true, @OA\JsonContent(
 *         required={"bank_name","account_number","account_type"},
 *         @OA\Property(property="bank_name", type="string", example="State Bank of India"),
 *         @OA\Property(property="branch_name", type="string", example="Main Branch"),
 *         @OA\Property(property="account_number", type="string", example="1234567890"),
 *         @OA\Property(property="ifsc_code", type="string", example="SBIN0001234"),
 *         @OA\Property(property="account_holder_name", type="string", example="Reco Pvt Ltd"),
 *         @OA\Property(property="account_type", type="string", enum={"savings","current","fixed_deposit","cc_od"}),
 *         @OA\Property(property="opening_balance", type="number", format="float", example=50000),
 *         @OA\Property(property="upi_id", type="string", example="company@upi"),
 *         @OA\Property(property="is_default", type="boolean", example=false)
 *     )),
 *     @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Put(
 *     path="/bank-accounts/{id}",
 *     tags={"Bank Accounts"},
 *     summary="Update bank account",
 *     operationId="updateBankAccount",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(@OA\JsonContent(
 *         @OA\Property(property="bank_name", type="string"),
 *         @OA\Property(property="account_number", type="string"),
 *         @OA\Property(property="is_default", type="boolean")
 *     )),
 *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Patch(
 *     path="/bank-accounts/{id}/default",
 *     tags={"Bank Accounts"},
 *     summary="Set as default bank account",
 *     operationId="setDefaultBankAccount",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Default set", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 */
class BankAccountDocs
{
    //
}
