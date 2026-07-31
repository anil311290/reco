<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/dashboard",
 *     tags={"Dashboard"},
 *     summary="Get dashboard statistics",
 *     description="Dashboard statistics computed from posted ledger entries, so figures match the Profit & Loss report. Taxes are excluded from income and expense because they sit on their own tax ledgers.",
 *     operationId="getDashboard",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="range",
 *         in="query",
 *         description="Period filter. this_year means the current financial year.",
 *         @OA\Schema(type="string", enum={"this_month", "last_month", "this_quarter", "this_year"}, example="this_year")
 *     ),
 *     @OA\Parameter(
 *         name="group",
 *         in="query",
 *         description="Chart bucket size",
 *         @OA\Schema(type="string", enum={"monthly", "quarterly", "yearly"}, example="monthly")
 *     ),
 *     @OA\Parameter(
 *         name="limit",
 *         in="query",
 *         description="Recent transactions to return (1-20)",
 *         @OA\Schema(type="integer", example=10)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Dashboard data",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="statistics", type="object",
 *                     @OA\Property(property="income", type="number", format="float", example=150000.00, description="Income ledger movement for the period, excluding tax"),
 *                     @OA\Property(property="expense", type="number", format="float", example=80000.00, description="Expense ledger movement for the period, excluding tax"),
 *                     @OA\Property(property="profit", type="number", format="float", example=70000.00, description="Income - expense. Negative means net loss"),
 *                     @OA\Property(property="receivables", type="number", format="float", example=45000.00, description="Outstanding receivables"),
 *                     @OA\Property(property="payables", type="number", format="float", example=25000.00, description="Outstanding payables"),
 *                     @OA\Property(property="cash_balance", type="number", format="float", example=120000.00, description="Cash + Bank + OD balance"),
 *                     @OA\Property(property="total_vouchers", type="integer", example=150, description="Posted vouchers in the period"),
 *                     @OA\Property(property="total_parties", type="integer", example=45, description="Total parties"),
 *                     @OA\Property(property="total_accounts", type="integer", example=30, description="Total accounts"),
 *                     @OA\Property(property="period", type="object",
 *                         @OA\Property(property="start", type="string", format="date", example="2026-04-01"),
 *                         @OA\Property(property="end", type="string", format="date", example="2027-03-31"),
 *                         @OA\Property(property="label", type="string", example="FY 2026-27")
 *                     )
 *                 ),
 *                 @OA\Property(property="recent_transactions", type="array", @OA\Items(ref="#/components/schemas/Voucher")),
 *                 @OA\Property(property="chart_data", type="object",
 *                     @OA\Property(property="labels", type="array", @OA\Items(type="string"), example={"Apr 2026", "May 2026"}),
 *                     @OA\Property(property="income", type="array", @OA\Items(type="number"), example={15000, 18000}),
 *                     @OA\Property(property="expense", type="array", @OA\Items(type="number"), example={8000, 9500})
 *                 ),
 *                 @OA\Property(property="period", type="object",
 *                     @OA\Property(property="start", type="string", format="date", example="2026-04-01"),
 *                     @OA\Property(property="end", type="string", format="date", example="2027-03-31")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 *
 * @OA\Get(
 *     path="/dashboard/monthly-data",
 *     tags={"Dashboard"},
 *     summary="Get monthly income/expense data",
 *     description="Monthly income and expense ledger movement for a calendar year, excluding tax",
 *     operationId="getMonthlyData",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="year",
 *         in="query",
 *         description="Year (default: current year)",
 *         @OA\Schema(type="integer", example=2025)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Monthly data",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="months", type="array", @OA\Items(type="string"), example={"Jan", "Feb", "Mar"}),
 *                 @OA\Property(property="income", type="array", @OA\Items(type="number"), example={15000, 18000, 22000}),
 *                 @OA\Property(property="expense", type="array", @OA\Items(type="number"), example={8000, 9500, 11000})
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 *
 * @OA\Get(
 *     path="/dashboard/receivables-trend",
 *     tags={"Dashboard"},
 *     summary="Get receivables trend",
 *     description="Outstanding receivables balance at each month end for the last N months",
 *     operationId="getReceivablesTrend",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="months",
 *         in="query",
 *         description="Number of months (default: 6)",
 *         @OA\Schema(type="integer", example=6)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Receivables trend data",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="labels", type="array", @OA\Items(type="string"), example={"Jan 2025", "Feb 2025"}),
 *                 @OA\Property(property="data", type="array", @OA\Items(type="number"), example={45000, 52000})
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 *
 * @OA\Get(
 *     path="/dashboard/payables-trend",
 *     tags={"Dashboard"},
 *     summary="Get payables trend",
 *     description="Outstanding payables balance at each month end for the last N months",
 *     operationId="getPayablesTrend",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="months",
 *         in="query",
 *         description="Number of months (default: 6)",
 *         @OA\Schema(type="integer", example=6)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Payables trend data",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="labels", type="array", @OA\Items(type="string"), example={"Jan 2025", "Feb 2025"}),
 *                 @OA\Property(property="data", type="array", @OA\Items(type="number"), example={25000, 28000})
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */
class DashboardDocs
{
    //
}
