<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/parties",
 *     tags={"Parties"},
 *     summary="Get all parties",
 *     operationId="getParties",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
 *     @OA\Parameter(name="type", in="query", @OA\Schema(type="string", enum={"debtor","creditor"})),
 *     @OA\Parameter(name="is_active", in="query", @OA\Schema(type="boolean")),
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Post(
 *     path="/parties",
 *     tags={"Parties"},
 *     summary="Create party",
 *     description="If a soft-deleted party with the same name exists, the API returns HTTP 409 with code SOFT_DELETED_PARTY_EXISTS. Retry with duplicate_action=restore or duplicate_action=new_entry.",
 *     operationId="createParty",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(required=true, @OA\JsonContent(
 *         required={"name","type","address","state_id","city_id","postal_code","opening_balance_type"},
 *         @OA\Property(property="name", type="string"),
 *         @OA\Property(property="type", type="string", enum={"debtor","creditor"}),
 *         @OA\Property(property="mobile", type="string"),
 *         @OA\Property(property="email", type="string"),
 *         @OA\Property(property="address", type="string"),
 *         @OA\Property(property="state_id", type="integer"),
 *         @OA\Property(property="city_id", type="integer"),
 *         @OA\Property(property="postal_code", type="string"),
 *         @OA\Property(property="opening_balance", type="number"),
 *         @OA\Property(property="opening_balance_type", type="string", enum={"debit","credit"}),
 *         @OA\Property(property="duplicate_action", type="string", enum={"restore","new_entry"}, description="Required only after a 409 soft-deleted duplicate response")
 *     )),
 *     @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=409, description="Soft-deleted party with the same name exists", @OA\JsonContent(
 *         @OA\Property(property="success", type="boolean", example=false),
 *         @OA\Property(property="code", type="string", example="SOFT_DELETED_PARTY_EXISTS"),
 *         @OA\Property(property="message", type="string"),
 *         @OA\Property(property="data", type="object",
 *             @OA\Property(property="party_code", type="string"),
 *             @OA\Property(property="party_name", type="string")
 *         )
 *     ))
 * )
 *
 * @OA\Get(
 *     path="/parties/by-type",
 *     tags={"Parties"},
 *     summary="Get parties by type",
 *     operationId="getPartiesByType",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="type", in="query", required=true, @OA\Schema(type="string", enum={"debtor","creditor"})),
 *     @OA\Response(
 *         response=200,
 *         description="Returns parties for the authenticated company plus the next party_code for that type",
 *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
 *     )
 * )
 *
 * @OA\Get(
 *     path="/parties/{id}/history",
 *     tags={"Parties"},
 *     summary="Get party transaction history (ledger)",
 *     description="Opening balance, all transactions, and running/closing balance for a party across the AR/AP control account.",
 *     operationId="getPartyHistory",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="financial_year_id", in="query", @OA\Schema(type="integer")),
 *     @OA\Parameter(name="date_from", in="query", @OA\Schema(type="string", format="date")),
 *     @OA\Parameter(name="date_to", in="query", @OA\Schema(type="string", format="date")),
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/parties/{id}",
 *     tags={"Parties"},
 *     summary="Get party by ID",
 *     operationId="getPartyById",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Put(
 *     path="/parties/{id}",
 *     tags={"Parties"},
 *     summary="Update party",
 *     description="Opening fields are immutable after creation. opening_balance, opening_balance_type, and opening_date are ignored on update.",
 *     operationId="updateParty",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Delete(
 *     path="/parties/{id}",
 *     tags={"Parties"},
 *     summary="Delete party",
 *     operationId="deleteParty",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Deleted", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Patch(
 *     path="/parties/{id}/status",
 *     tags={"Parties"},
 *     summary="Change party status",
 *     operationId="changePartyStatus",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(required=true, @OA\JsonContent(required={"status"}, @OA\Property(property="status", type="boolean"))),
 *     @OA\Response(response=200, description="Status updated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/parties/{id}/outstanding-invoices",
 *     tags={"Parties"},
 *     summary="Open invoices for a party",
 *     description="Returns unpaid, non-cancelled invoices with a balance due, oldest due date first. Use this to build a payment allocation screen before calling POST /parties/{id}/record-payment.",
 *     operationId="getPartyOutstandingInvoices",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=3)),
 *     @OA\Parameter(name="invoice_type", in="query", required=false, description="Defaults to sales for debtors and purchase for creditors", @OA\Schema(type="string", enum={"sales","purchase"})),
 *     @OA\Response(
 *         response=200,
 *         description="Outstanding invoices",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="array", @OA\Items(
 *                 @OA\Property(property="id", type="integer", example=10),
 *                 @OA\Property(property="invoice_number", type="string", example="INV-2026-0010"),
 *                 @OA\Property(property="invoice_date", type="string", example="01-Apr-2026"),
 *                 @OA\Property(property="due_date", type="string", example="15-Apr-2026"),
 *                 @OA\Property(property="total", type="number", example=11800.00),
 *                 @OA\Property(property="balance_due", type="number", example=5000.00),
 *                 @OA\Property(property="is_overdue", type="boolean", example=true),
 *                 @OA\Property(property="overdue_days", type="integer", example=12)
 *             ))
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *     @OA\Response(response=404, description="Party not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Post(
 *     path="/parties/{id}/record-payment",
 *     tags={"Parties"},
 *     summary="Record one payment/receipt allocated across multiple invoices",
 *     description="Creates a single receipt (debtor) or payment (creditor) voucher and settles it against the supplied invoices. Allocation amounts must not exceed each invoice balance_due.",
 *     operationId="recordPartyPayment",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=3)),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"cash_bank_account_id", "payment_date", "allocations"},
 *             @OA\Property(property="cash_bank_account_id", type="integer", example=4, description="From GET /accounts/cash-bank"),
 *             @OA\Property(property="payment_date", type="string", format="date", example="2026-04-20"),
 *             @OA\Property(property="allocations", type="array", minItems=1, @OA\Items(
 *                 required={"invoice_id", "amount"},
 *                 @OA\Property(property="invoice_id", type="integer", example=10),
 *                 @OA\Property(property="amount", type="number", example=5000.00),
 *                 @OA\Property(property="reference_number", type="string", maxLength=100, example="UPI-99887766")
 *             ))
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Payment recorded",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Payment recorded against 2 invoice(s)."),
 *             @OA\Property(property="data", type="object", @OA\Property(property="voucher_number", type="string", example="RCP-2026-0007"))
 *         )
 *     ),
 *     @OA\Response(response=400, description="Party is not a debtor/creditor or allocation failed", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *     @OA\Response(response=404, description="Party not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
class PartyDocs
{
    //
}
