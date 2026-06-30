<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/reports/profit-loss",
 *     tags={"Reports"},
 *     summary="Get Profit & Loss report",
 *     description="Get Profit & Loss report for a financial year",
 *     operationId="getProfitLoss",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="financial_year_id", in="query", description="Financial year ID (default: current)", @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="income", type="object",
 *                     @OA\Property(property="accounts", type="array", @OA\Items(type="object")),
 *                     @OA\Property(property="total", type="number", example=150000.00)
 *                 ),
 *                 @OA\Property(property="expense", type="object",
 *                     @OA\Property(property="accounts", type="array", @OA\Items(type="object")),
 *                     @OA\Property(property="total", type="number", example=80000.00)
 *                 ),
 *                 @OA\Property(property="net_profit", type="number", example=70000.00),
 *                 @OA\Property(property="is_profit", type="boolean", example=true)
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/reports/balance-sheet",
 *     tags={"Reports"},
 *     summary="Get Balance Sheet report",
 *     description="Get Balance Sheet report for a financial year",
 *     operationId="getBalanceSheet",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="financial_year_id", in="query", description="Financial year ID (default: current)", @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="assets", type="object",
 *                     @OA\Property(property="accounts", type="array", @OA\Items(type="object")),
 *                     @OA\Property(property="total", type="number", example=500000.00)
 *                 ),
 *                 @OA\Property(property="liabilities", type="object",
 *                     @OA\Property(property="accounts", type="array", @OA\Items(type="object")),
 *                     @OA\Property(property="total", type="number", example=200000.00)
 *                 ),
 *                 @OA\Property(property="equity", type="object",
 *                     @OA\Property(property="accounts", type="array", @OA\Items(type="object")),
 *                     @OA\Property(property="total", type="number", example=300000.00)
 *                 ),
 *                 @OA\Property(property="is_balanced", type="boolean", example=true)
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/reports/trial-balance",
 *     tags={"Reports"},
 *     summary="Get Trial Balance report",
 *     description="Get Trial Balance report for a financial year",
 *     operationId="getTrialBalance",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="financial_year_id", in="query", description="Financial year ID (default: current)", @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="accounts", type="array", @OA\Items(type="object")),
 *                 @OA\Property(property="total_debit", type="number", example=500000.00),
 *                 @OA\Property(property="total_credit", type="number", example=500000.00),
 *                 @OA\Property(property="is_balanced", type="boolean", example=true)
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/reports/day-book",
 *     tags={"Reports"},
 *     summary="Get Day Book report",
 *     description="Get all transactions for a specific date",
 *     operationId="getDayBook",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="date", in="query", required=true, description="Date (YYYY-MM-DD)", @OA\Schema(type="string", format="date")),
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="date", type="string", example="2025-04-15"),
 *                 @OA\Property(property="vouchers", type="array", @OA\Items(ref="#/components/schemas/Voucher")),
 *                 @OA\Property(property="total_debit", type="number", example=25000.00),
 *                 @OA\Property(property="total_credit", type="number", example=25000.00)
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/reports/ledger",
 *     tags={"Reports"},
 *     summary="Get Ledger report",
 *     description="Get detailed ledger for a specific account",
 *     operationId="getReportLedger",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="account_id", in="query", required=true, description="Account ID", @OA\Schema(type="integer")),
 *     @OA\Parameter(name="date_from", in="query", description="From date", @OA\Schema(type="string", format="date")),
 *     @OA\Parameter(name="date_to", in="query", description="To date", @OA\Schema(type="string", format="date")),
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="account", ref="#/components/schemas/Account"),
 *                 @OA\Property(property="opening_balance", type="object"),
 *                 @OA\Property(property="entries", type="array", @OA\Items(type="object")),
 *                 @OA\Property(property="total_debit", type="number"),
 *                 @OA\Property(property="total_credit", type="number"),
 *                 @OA\Property(property="closing_balance", type="object")
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/reports/debtors-outstanding",
 *     tags={"Reports"},
 *     summary="Get Debtors Outstanding report",
 *     description="Get list of debtors with outstanding balances",
 *     operationId="getDebtorsOutstanding",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="debtors", type="array", @OA\Items(type="object")),
 *                 @OA\Property(property="total", type="number", example=150000.00)
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/reports/creditors-outstanding",
 *     tags={"Reports"},
 *     summary="Get Creditors Outstanding report",
 *     description="Get list of creditors with outstanding balances",
 *     operationId="getCreditorsOutstanding",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="creditors", type="array", @OA\Items(type="object")),
 *                 @OA\Property(property="total", type="number", example=75000.00)
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
class ReportDocs
{
    //
}
