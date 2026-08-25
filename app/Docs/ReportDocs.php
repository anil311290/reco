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
 *     @OA\Parameter(name="financial_year_id", in="query", description="Financial year ID (default: current)", @OA\Schema(type="integer")),
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
 *     path="/reports/receipt-payment",
 *     tags={"Reports"},
 *     summary="Get Receipt & Payment report",
 *     description="Cash, bank, and OD movement for the period grouped by contra ledger head. Opening + Receipts always equals Payments + Closing; transfers between two cash / bank ledgers are excluded from both sides. Dates default to the financial year range.",
 *     operationId="getReceiptPayment",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="date_from", in="query", description="From date (default: financial year start)", @OA\Schema(type="string", format="date")),
 *     @OA\Parameter(name="date_to", in="query", description="To date (default: financial year end)", @OA\Schema(type="string", format="date")),
 *     @OA\Parameter(name="financial_year_id", in="query", description="Financial year ID (default: current)", @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="date_from", type="string", format="date", example="2025-04-01"),
 *                 @OA\Property(property="date_to", type="string", format="date", example="2026-03-31"),
 *                 @OA\Property(property="financial_year_id", type="integer", example=1),
 *                 @OA\Property(
 *                     property="accounts",
 *                     type="array",
 *                     description="Per cash / bank / OD ledger movement",
 *                     @OA\Items(
 *                         @OA\Property(property="account", ref="#/components/schemas/Account"),
 *                         @OA\Property(property="opening", type="number", example=15000.00),
 *                         @OA\Property(property="received", type="number", example=48000.00),
 *                         @OA\Property(property="paid", type="number", example=32000.00),
 *                         @OA\Property(property="closing", type="number", example=31000.00)
 *                     )
 *                 ),
 *                 @OA\Property(
 *                     property="receipts",
 *                     type="object",
 *                     @OA\Property(
 *                         property="rows",
 *                         type="array",
 *                         @OA\Items(
 *                             @OA\Property(property="account", ref="#/components/schemas/Account"),
 *                             @OA\Property(property="code", type="string", example="4001"),
 *                             @OA\Property(property="label", type="string", example="Sales"),
 *                             @OA\Property(property="amount", type="number", example=48000.00)
 *                         )
 *                     ),
 *                     @OA\Property(property="total", type="number", example=48000.00)
 *                 ),
 *                 @OA\Property(
 *                     property="payments",
 *                     type="object",
 *                     @OA\Property(
 *                         property="rows",
 *                         type="array",
 *                         @OA\Items(
 *                             @OA\Property(property="account", ref="#/components/schemas/Account"),
 *                             @OA\Property(property="code", type="string", example="5001"),
 *                             @OA\Property(property="label", type="string", example="Rent"),
 *                             @OA\Property(property="amount", type="number", example=32000.00)
 *                         )
 *                     ),
 *                     @OA\Property(property="total", type="number", example=32000.00)
 *                 ),
 *                 @OA\Property(property="opening_total", type="number", example=15000.00),
 *                 @OA\Property(property="closing_total", type="number", example=31000.00),
 *                 @OA\Property(property="receipts_side_total", type="number", description="opening_total + receipts.total", example=63000.00),
 *                 @OA\Property(property="payments_side_total", type="number", description="payments.total + closing_total", example=63000.00),
 *                 @OA\Property(property="is_balanced", type="boolean", example=true),
 *                 @OA\Property(property="message", type="string", nullable=true, description="Set when no cash / bank / OD ledger exists")
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
 *     description="Invoice-wise receivables outstanding. Supports the same financial year / as-of-date / aging filters as the web report.",
 *     operationId="getDebtorsOutstanding",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="financial_year_id", in="query", required=false, description="Defaults to the current financial year", @OA\Schema(type="integer", example=1)),
 *     @OA\Parameter(name="as_of_date", in="query", required=false, description="Cut-off date (defaults to today)", @OA\Schema(type="string", format="date", example="2026-03-31")),
 *     @OA\Parameter(name="overdue_status", in="query", required=false, @OA\Schema(type="string", enum={"all","due","not_due"}, example="due")),
 *     @OA\Parameter(name="age_bucket", in="query", required=false, @OA\Schema(type="string", enum={"all","current","1_30","31_60","61_90","91_plus","custom"}, example="31_60")),
 *     @OA\Parameter(name="basis", in="query", required=false, description="Age from invoice date (billed) or due date (due)", @OA\Schema(type="string", enum={"billed","due"}, example="due")),
 *     @OA\Parameter(name="age_min", in="query", required=false, description="Only used when age_bucket=custom", @OA\Schema(type="integer", minimum=0, example=15)),
 *     @OA\Parameter(name="age_max", in="query", required=false, description="Only used when age_bucket=custom", @OA\Schema(type="integer", minimum=0, example=45)),
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="debtors", type="array", @OA\Items(type="object")),
 *                 @OA\Property(property="total", type="number", example=150000.00),
 *                 @OA\Property(property="as_of_date", type="string", format="date", example="2026-03-31"),
 *                 @OA\Property(property="financial_year_id", type="integer", example=1),
 *                 @OA\Property(property="filters", type="object")
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/reports/creditors-outstanding",
 *     tags={"Reports"},
 *     summary="Get Creditors Outstanding report",
 *     description="Invoice-wise payables outstanding. Accepts the same filters as /reports/debtors-outstanding.",
 *     operationId="getCreditorsOutstanding",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="financial_year_id", in="query", required=false, @OA\Schema(type="integer", example=1)),
 *     @OA\Parameter(name="as_of_date", in="query", required=false, @OA\Schema(type="string", format="date", example="2026-03-31")),
 *     @OA\Parameter(name="overdue_status", in="query", required=false, @OA\Schema(type="string", enum={"all","due","not_due"})),
 *     @OA\Parameter(name="age_bucket", in="query", required=false, @OA\Schema(type="string", enum={"all","current","1_30","31_60","61_90","91_plus","custom"})),
 *     @OA\Parameter(name="basis", in="query", required=false, @OA\Schema(type="string", enum={"billed","due"})),
 *     @OA\Parameter(name="age_min", in="query", required=false, @OA\Schema(type="integer", minimum=0)),
 *     @OA\Parameter(name="age_max", in="query", required=false, @OA\Schema(type="integer", minimum=0)),
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="creditors", type="array", @OA\Items(type="object")),
 *                 @OA\Property(property="total", type="number", example=75000.00),
 *                 @OA\Property(property="as_of_date", type="string", format="date", example="2026-03-31"),
 *                 @OA\Property(property="financial_year_id", type="integer", example=1),
 *                 @OA\Property(property="filters", type="object")
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/reports/aging-summary",
 *     tags={"Reports"},
 *     summary="Combined receivables + payables aging report",
 *     description="Mirrors the web Aging Summary report. Rows are sorted by most overdue first and bucketed into Current / 1-30 / 31-60 / 61-90 / 91+ days.",
 *     operationId="getAgingSummary",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="financial_year_id", in="query", required=false, @OA\Schema(type="integer", example=1)),
 *     @OA\Parameter(name="as_of_date", in="query", required=false, @OA\Schema(type="string", format="date", example="2026-03-31")),
 *     @OA\Parameter(name="overdue_status", in="query", required=false, @OA\Schema(type="string", enum={"all","due","not_due"})),
 *     @OA\Parameter(name="age_bucket", in="query", required=false, @OA\Schema(type="string", enum={"all","current","1_30","31_60","61_90","91_plus","custom"})),
 *     @OA\Parameter(name="basis", in="query", required=false, @OA\Schema(type="string", enum={"billed","due"})),
 *     @OA\Parameter(name="age_min", in="query", required=false, @OA\Schema(type="integer", minimum=0)),
 *     @OA\Parameter(name="age_max", in="query", required=false, @OA\Schema(type="integer", minimum=0)),
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="rows", type="array", @OA\Items(type="object"), description="Each row carries report_type = Receivable | Payable"),
 *                 @OA\Property(property="summary", type="object",
 *                     @OA\Property(property="receivables_total", type="number", example=150000.00),
 *                     @OA\Property(property="payables_total", type="number", example=75000.00),
 *                     @OA\Property(property="receivables", type="object", example={"current":{"label":"Current","count":2,"amount":5000},"1_30":{"label":"1-30 Days","count":1,"amount":2500}}),
 *                     @OA\Property(property="payables", type="object")
 *                 ),
 *                 @OA\Property(property="as_of_date", type="string", format="date", example="2026-03-31"),
 *                 @OA\Property(property="financial_year_id", type="integer", example=1)
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/reports/unapplied-receipts",
 *     tags={"Reports"},
 *     summary="Unapplied cash (receipts & payments not fully allocated to invoices)",
 *     description="Defaults to the current month when no dates are supplied.",
 *     operationId="getUnappliedReceipts",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="from_date", in="query", required=false, @OA\Schema(type="string", format="date", example="2026-04-01")),
 *     @OA\Parameter(name="to_date", in="query", required=false, description="Must be on or after from_date", @OA\Schema(type="string", format="date", example="2026-04-30")),
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="from_date", type="string", format="date", example="2026-04-01"),
 *                 @OA\Property(property="to_date", type="string", format="date", example="2026-04-30"),
 *                 @OA\Property(property="receipts", type="array", @OA\Items(type="object")),
 *                 @OA\Property(property="payments", type="array", @OA\Items(type="object"))
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/reports/stock-register",
 *     tags={"Reports"},
 *     summary="Stock movement register",
 *     description="Movement rows are only returned when item_id is supplied (matches the web report). closing_quantity always reflects every movement up to to_date, ignoring from_date.",
 *     operationId="getStockRegister",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="item_id", in="query", required=false, description="Stockable goods item", @OA\Schema(type="integer", example=5)),
 *     @OA\Parameter(name="from_date", in="query", required=false, @OA\Schema(type="string", format="date", example="2026-04-01")),
 *     @OA\Parameter(name="to_date", in="query", required=false, @OA\Schema(type="string", format="date", example="2026-04-30")),
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="rows", type="array", @OA\Items(type="object")),
 *                 @OA\Property(property="total_movements", type="integer", example=12),
 *                 @OA\Property(property="total_in", type="number", example=120.5),
 *                 @OA\Property(property="total_out", type="number", example=80.0),
 *                 @OA\Property(property="closing_quantity", type="number", example=40.5),
 *                 @OA\Property(property="from_date", type="string", format="date", nullable=true),
 *                 @OA\Property(property="to_date", type="string", format="date", nullable=true),
 *                 @OA\Property(property="item_id", type="integer", nullable=true, example=5)
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/reports/settlement-audit",
 *     tags={"Reports"},
 *     summary="Payment-to-invoice settlement audit trail",
 *     operationId="getSettlementAudit",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="date_from", in="query", required=false, @OA\Schema(type="string", format="date", example="2026-04-01")),
 *     @OA\Parameter(name="date_to", in="query", required=false, @OA\Schema(type="string", format="date", example="2026-04-30")),
 *     @OA\Parameter(name="filters[status]", in="query", required=false, @OA\Schema(type="string", enum={"all","pending","partial","settled","reversed"}, example="all")),
 *     @OA\Parameter(name="filters[type]", in="query", required=false, @OA\Schema(type="string", enum={"all","sales","purchase"}, example="all")),
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="mappings", type="array", @OA\Items(type="object")),
 *                 @OA\Property(property="summary", type="object",
 *                     @OA\Property(property="total_mappings", type="integer", example=25),
 *                     @OA\Property(property="total_allocated", type="number", example=125000.00),
 *                     @OA\Property(property="total_settled", type="number", example=120000.00),
 *                     @OA\Property(property="total_outstanding", type="number", example=5000.00)
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/reports/invoice-settlement-details",
 *     tags={"Reports"},
 *     summary="Settlement breakdown for one invoice",
 *     operationId="getInvoiceSettlementDetails",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="invoice_type", in="query", required=true, @OA\Schema(type="string", enum={"sales","purchase"}, example="sales")),
 *     @OA\Parameter(name="invoice_id", in="query", required=true, @OA\Schema(type="integer", example=10)),
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *     @OA\Response(response=400, description="invoice_type / invoice_id missing or invalid", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/reports/payment-settlement-details",
 *     tags={"Reports"},
 *     summary="Settlement breakdown for one payment/receipt voucher",
 *     operationId="getPaymentSettlementDetails",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="voucher_id", in="query", required=true, @OA\Schema(type="integer", example=42)),
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *     @OA\Response(response=400, description="voucher_id missing", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
class ReportDocs
{
    //
}
