<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Reco API",
 *     version="1.0.0",
 *     description="Reco offline-first accounting SaaS API under /api/v1. Payment/receipt/adjustment APIs match web Tally forms (cash_bank_account_id + payment_rows / adjustment_rows; Cash/Bank/OD ledgers). Helpers: GET /accounts/cash-bank, /accounts/payment-particulars, /accounts/adjustment-particulars. Sales invoices are unified (item + service lines) via /sales-invoices. Invoice settlement requires amount + payment_mode + cash_bank_account_id. PATCH /financial-years/{id}/set-current carries forward opening balances. Period lock applies when FY is closed or date is outside FY. Audit logs: GET /audit-logs and GET /audit-logs/{id}. Public registration: GET /plans and POST /register. Web-only: roles, CMS, Excel/CSV export.",
 *     @OA\Contact(
 *         name="Reco Support",
 *         email="support@reco.app",
 *         url="https://reco.app/support"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://127.0.0.1:8000/api/v1",
 *     description="Local Development Server (artisan serve)"
 * )
 *
 * @OA\Server(
 *     url="http://127.0.0.1:8002/api/v1",
 *     description="Local Development Server (XAMPP)"
 * )
 *
 * @OA\Server(
 *     url="https://reco.aaochaletaxi.app/api/v1",
 *     description="Production Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your Bearer token in the format: 1|abc123..."
 * )
 */
class ApiInfo
{
    //
}
