<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/accounts",
 *     tags={"Accounts"},
 *     summary="Get all accounts",
 *     operationId="getAccounts",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
 *     @OA\Parameter(name="account_type", in="query", @OA\Schema(type="string", enum={"asset", "liability", "income", "expense", "equity"})),
 *     @OA\Parameter(name="is_active", in="query", @OA\Schema(type="boolean")),
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Post(
 *     path="/accounts",
 *     tags={"Accounts"},
 *     summary="Create account",
 *     operationId="createAccount",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(required=true, @OA\JsonContent(
 *         required={"account_name","account_type"},
 *         @OA\Property(property="account_name", type="string"),
 *         @OA\Property(property="account_type", type="string", enum={"asset","liability","income","expense","equity"}),
 *         @OA\Property(property="transaction_mode", type="string", enum={"cash","bank","od"}),
 *         @OA\Property(property="opening_balance", type="number"),
 *         @OA\Property(property="balance_type", type="string", enum={"debit","credit"}),
 *         @OA\Property(property="is_active", type="boolean")
 *     )),
 *     @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/accounts/by-type",
 *     tags={"Accounts"},
 *     summary="Get accounts by type",
 *     operationId="getAccountsByType",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="type", in="query", required=true, @OA\Schema(type="string", enum={"asset","liability","income","expense","equity"})),
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/accounts/tree",
 *     tags={"Accounts"},
 *     summary="Get account tree",
 *     operationId="getAccountTree",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="type", in="query", @OA\Schema(type="string")),
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/accounts/{id}",
 *     tags={"Accounts"},
 *     summary="Get account by ID",
 *     operationId="getAccountById",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Put(
 *     path="/accounts/{id}",
 *     tags={"Accounts"},
 *     summary="Update account",
 *     operationId="updateAccount",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Delete(
 *     path="/accounts/{id}",
 *     tags={"Accounts"},
 *     summary="Delete account",
 *     operationId="deleteAccount",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Deleted", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Patch(
 *     path="/accounts/{id}/status",
 *     tags={"Accounts"},
 *     summary="Change account status",
 *     operationId="changeAccountStatus",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(required=true, @OA\JsonContent(required={"status"}, @OA\Property(property="status", type="boolean"))),
 *     @OA\Response(response=200, description="Status updated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/accounts/cash-bank",
 *     tags={"Accounts"},
 *     summary="Cash / Bank / OD accounts for payments and receipts",
 *     description="Dropdown helper matching web Paid From / Received In. Includes available_balance (null for OD).",
 *     operationId="getCashBankAccounts",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="mode", in="query", description="Filter by transaction mode", @OA\Schema(type="string", enum={"cash","bank","od"})),
 *     @OA\Parameter(name="financial_year_id", in="query", @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/accounts/payment-particulars",
 *     tags={"Accounts"},
 *     summary="Particulars for payment/receipt lines",
 *     description="Party ledgers only. type=payment returns creditors; type=receipt returns debtors.",
 *     operationId="getPaymentParticulars",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="type", in="query", required=true, @OA\Schema(type="string", enum={"payment","receipt"})),
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/accounts/adjustment-particulars",
 *     tags={"Accounts"},
 *     summary="Particulars for adjustment/journal vouchers",
 *     operationId="getAdjustmentParticulars",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 */
class AccountDocs
{
    //
}
