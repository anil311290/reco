<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Authentication",
 *     description="API Endpoints for User Authentication"
 * )
 *
 * @OA\Tag(
 *     name="Dashboard",
 *     description="API Endpoints for Dashboard Data & Statistics"
 * )
 *
 * @OA\Tag(
 *     name="Accounts",
 *     description="API Endpoints for Account Master (Chart of Accounts)"
 * )
 *
 * @OA\Tag(
 *     name="Parties",
 *     description="API Endpoints for Party Master (Debtors & Creditors)"
 * )
 *
 * @OA\Tag(
 *     name="Vouchers",
 *     description="Generic voucher API (all types). Prefer /payments, /receipts, /adjustments for those modules."
 * )
 *
 * @OA\Tag(
 *     name="Payments",
 *     description="Payment vouchers (money out)"
 * )
 *
 * @OA\Tag(
 *     name="Receipts",
 *     description="Receipt vouchers (money in)"
 * )
 *
 * @OA\Tag(
 *     name="Adjustments",
 *     description="Journal / adjustment vouchers"
 * )
 *
 * @OA\Tag(
 *     name="Reports",
 *     description="API Endpoints for Financial Reports"
 * )
 *
 * @OA\Tag(
 *     name="Tax Rates",
 *     description="API Endpoints for Tax Rate Management (GST/IGST/VAT)"
 * )
 *
 * @OA\Tag(
 *     name="Items",
 *     description="API Endpoints for Item & Service Catalog Management"
 * )

 * @OA\Tag(
 *     name="Sales Invoices",
 *     description="Unified sales invoices (item lines and/or service/income lines) via /sales-invoices."
 * )
 *
 * @OA\Tag(
 *     name="Purchase Invoices",
 *     description="API Endpoints for Purchase Invoice (AP) Management"
 * )
 *
 * @OA\Tag(
 *     name="Subscriptions",
 *     description="API Endpoints for Subscription & Billing Management"
 * )
 *
 * @OA\Tag(
 *     name="Themes",
 *     description="API Endpoints for Theme & Appearance Settings"
 * )
 *
 * @OA\Tag(
 *     name="Settings",
 *     description="API Endpoints for Application Settings"
 * )
 *
 * @OA\Tag(
 *     name="Security",
 *     description="API Endpoints for PIN, App Lock & Device Security"
 * )
 *
 * @OA\Tag(
 *     name="Export",
 *     description="API Endpoints for Report Export (PDF)"
 * )
 *
 * @OA\Tag(
 *     name="Ledgers",
 *     description="API Endpoints for Ledger Accounts and Entries"
 * )
 *
 * @OA\Tag(
 *     name="Locations",
 *     description="API Endpoints for Country/State/City Lists"
 * )
 *
 * @OA\Tag(
 *     name="Sync",
 *     description="Offline-first sync: upload queue, manual sync, download delta, bootstrap"
 * )
 *
 * @OA\Tag(
 *     name="Notifications",
 *     description="In-app notifications for mobile and web"
 * )
 *
 * @OA\Tag(
 *     name="Audit Logs",
 *     description="Company audit trail (list + detail). Same as web Admin → Audit Logs."
 * )
 *
 * @OA\Tag(
 *     name="Support",
 *     description="Support ticketing and chat between tenant admin and SuperAdmin"
 * )
 *
 * @OA\Tag(
 *     name="Devices",
 *     description="Mobile device registration for sync tracking and push tokens"
 * )
 *
 * @OA\Tag(
 *     name="Content",
 *     description="Public marketing/legal content (FAQs, testimonials, CMS pages, contact form). No authentication required."
 * )
 */
class ApiTags
{
    //
}
