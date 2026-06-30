<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/dashboard",
 *     tags={"Dashboard"},
 *     summary="Get dashboard statistics",
 *     description="Get main dashboard statistics including income, expense, profit, receivables, payables",
 *     operationId="getDashboard",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Dashboard data",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="statistics", type="object",
 *                     @OA\Property(property="income", type="number", format="float", example=150000.00, description="Total income"),
 *                     @OA\Property(property="expense", type="number", format="float", example=80000.00, description="Total expense"),
 *                     @OA\Property(property="profit", type="number", format="float", example=70000.00, description="Net profit"),
 *                     @OA\Property(property="receivables", type="number", format="float", example=45000.00, description="Total receivables"),
 *                     @OA\Property(property="payables", type="number", format="float", example=25000.00, description="Total payables"),
 *                     @OA\Property(property="cash_balance", type="number", format="float", example=120000.00, description="Cash balance"),
 *                     @OA\Property(property="total_vouchers", type="integer", example=150, description="Total vouchers"),
 *                     @OA\Property(property="total_parties", type="integer", example=45, description="Total parties"),
 *                     @OA\Property(property="total_accounts", type="integer", example=30, description="Total accounts")
 *                 ),
 *                 @OA\Property(property="recent_transactions", type="array", @OA\Items(ref="#/components/schemas/Voucher"))
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
 *     description="Get monthly income and expense data for charts",
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
 *     description="Get receivables trend data for the last N months",
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
 *     description="Get payables trend data for the last N months",
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
