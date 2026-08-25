<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/sales-invoices",
 *     tags={"Sales Invoices"},
 *     summary="List sales invoices",
 *     description="Returns paginated unified sales invoices (item lines and/or service/income lines).",
 *     operationId="getSalesInvoices",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
 *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"draft","sent","partial","paid","overdue","cancelled"})),
 *     @OA\Parameter(name="party_id", in="query", @OA\Schema(type="integer")),
 *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/sales-invoices/overdue",
 *     tags={"Sales Invoices"},
 *     summary="Get overdue invoices",
 *     operationId="getOverdueSalesInvoices",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/sales-invoices/{id}",
 *     tags={"Sales Invoices"},
 *     summary="Get invoice details",
 *     operationId="getSalesInvoice",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Post(
 *     path="/sales-invoices",
 *     tags={"Sales Invoices"},
 *     summary="Create sales invoice",
 *     description="Unified create: send lines and/or service_lines. Invoice numbers follow INV-202627/0001.",
 *     operationId="createSalesInvoice",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(required=true, @OA\JsonContent(
 *         required={"party_id","invoice_date","due_date"},
 *         @OA\Property(property="party_id", type="integer", example=1, description="Customer ID"),
 *         @OA\Property(property="invoice_date", type="string", format="date", example="2026-06-05"),
 *         @OA\Property(property="due_date", type="string", format="date", example="2026-07-05"),
 *         @OA\Property(property="reference_number", type="string", example="PO-001"),
 *         @OA\Property(property="notes", type="string"),
 *         @OA\Property(property="payment_terms", type="string", example="Net 30"),
 *         @OA\Property(property="delivery_terms", type="string", example="FOB"),
 *         @OA\Property(property="discount_percentage", type="number", format="float", example=0),
 *         @OA\Property(property="lines", type="array", description="Goods/item lines", @OA\Items(
 *             required={"quantity","unit_price"},
 *             @OA\Property(property="item_id", type="integer"),
 *             @OA\Property(property="account_id", type="integer"),
 *             @OA\Property(property="tax_rate_id", type="integer"),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="quantity", type="number", format="float", example=2),
 *             @OA\Property(property="unit_price", type="number", format="float", example=150.00),
 *             @OA\Property(property="discount_percentage", type="number", format="float", example=0)
 *         )),
 *         @OA\Property(property="service_lines", type="array", description="Service/income lines", @OA\Items(
 *             required={"account_id","amount"},
 *             @OA\Property(property="account_id", type="integer", description="Income account ID"),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="amount", type="number", format="float", example=500.00),
 *             @OA\Property(property="tax_rate_id", type="integer")
 *         ))
 *     )),
 *     @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Post(
 *     path="/sales-invoices/{id}/payment",
 *     tags={"Sales Invoices"},
 *     summary="Record payment against invoice",
 *     operationId="recordSalesInvoicePayment",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(required=true, @OA\JsonContent(
 *         required={"amount","cash_bank_account_id"},
 *         @OA\Property(property="amount", type="number", format="float", example=500.00),
 *         @OA\Property(property="cash_bank_account_id", type="integer", example=12, description="Received In cash/bank/OD account"),
 *         @OA\Property(property="payment_date", type="string", format="date", example="2026-07-16")
 *     )),
 *     @OA\Response(response=200, description="Payment recorded", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Put(
 *     path="/sales-invoices/{id}",
 *     tags={"Sales Invoices"},
 *     summary="Update sales invoice",
 *     operationId="updateSalesInvoice",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Delete(
 *     path="/sales-invoices/{id}",
 *     tags={"Sales Invoices"},
 *     summary="Delete sales invoice",
 *     operationId="deleteSalesInvoice",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Deleted", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/sales-invoices/{id}/pdf",
 *     tags={"Sales Invoices"},
 *     summary="Export sales invoice PDF",
 *     operationId="exportSalesInvoicePdf",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="PDF generated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Post(
 *     path="/sales-invoices/{id}/cancel",
 *     tags={"Sales Invoices"},
 *     summary="Cancel a sales invoice",
 *     description="Reverses the linked voucher and its ledger entries. Fails if payments have already been settled against the invoice.",
 *     operationId="cancelSalesInvoice",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=10)),
 *     @OA\Response(
 *         response=200,
 *         description="Invoice cancelled",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Invoice cancelled successfully"),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *     @OA\Response(response=400, description="Invoice cannot be cancelled", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *     @OA\Response(response=404, description="Invoice not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Post(
 *     path="/sales-invoices/{id}/post",
 *     tags={"Sales Invoices"},
 *     summary="Post a draft sales invoice to the ledger",
 *     description="Generates the accounting voucher for a draft invoice. Only invoices with status=draft can be posted.",
 *     operationId="postSalesInvoice",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=10)),
 *     @OA\Response(
 *         response=200,
 *         description="Invoice posted",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Sales invoice posted successfully"),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *     @OA\Response(response=400, description="Only draft invoices can be posted, or account mapping missing", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *     @OA\Response(response=404, description="Invoice not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
class SalesInvoiceDocs
{
    //
}
