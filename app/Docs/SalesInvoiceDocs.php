<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/sales-invoices",
 *     tags={"Sales Invoices"},
 *     summary="List sales invoices",
 *     description="Returns paginated sales invoices (item and service). Optional invoice_type filter; omit to list all (same as web Sales Invoice).",
 *     operationId="getSalesInvoices",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
 *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"draft","sent","partial","paid","overdue","cancelled"})),
 *     @OA\Parameter(name="party_id", in="query", @OA\Schema(type="integer")),
 *     @OA\Parameter(name="invoice_type", in="query", description="Optional filter: item or service. Omit to return all.", @OA\Schema(type="string", enum={"item","service"})),
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
 *     description="Unified create matching web Sales Invoice: send lines and/or service_lines. Default invoice_type is item.",
 *     operationId="createSalesInvoice",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(required=true, @OA\JsonContent(
 *         required={"party_id","invoice_date","due_date","lines"},
 *         @OA\Property(property="party_id", type="integer", example=1, description="Customer ID"),
 *         @OA\Property(property="invoice_type", type="string", enum={"item","service"}, example="item", description="Type of invoice: item (default) or service"),
 *         @OA\Property(property="invoice_date", type="string", format="date", example="2026-06-05"),
 *         @OA\Property(property="due_date", type="string", format="date", example="2026-07-05"),
 *         @OA\Property(property="reference_number", type="string", example="PO-001"),
 *         @OA\Property(property="notes", type="string"),
 *         @OA\Property(property="payment_terms", type="string", example="Net 30"),
 *         @OA\Property(property="delivery_terms", type="string", example="FOB"),
 *         @OA\Property(property="discount_percentage", type="number", format="float", example=0),
 *         @OA\Property(property="lines", type="array", description="Required for item invoices", @OA\Items(
 *             required={"quantity","unit_price"},
 *             @OA\Property(property="item_id", type="integer"),
 *             @OA\Property(property="account_id", type="integer"),
 *             @OA\Property(property="tax_rate_id", type="integer"),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="quantity", type="number", format="float", example=2),
 *             @OA\Property(property="unit_price", type="number", format="float", example=150.00),
 *             @OA\Property(property="discount_percentage", type="number", format="float", example=0)
 *         )),
 *         @OA\Property(property="service_lines", type="array", description="Service line items (for service invoices or additional services in item invoices)", @OA\Items(
 *             required={"account_id","amount"},
 *             @OA\Property(property="account_id", type="integer", description="Income/expense account ID"),
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
 *         required={"amount","payment_mode","cash_bank_account_id"},
 *         @OA\Property(property="amount", type="number", format="float", example=500.00),
 *         @OA\Property(property="payment_mode", type="string", enum={"cash","bank","od"}, example="bank"),
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
 * @OA\Get(
 *     path="/service-sales-invoices",
 *     tags={"Service Sales Invoices"},
 *     summary="List service sales invoices (legacy)",
 *     description="Legacy alias of GET /sales-invoices?invoice_type=service. Prefer unified /sales-invoices.",
 *     operationId="getServiceSalesInvoices",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
 *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
 *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Post(
 *     path="/service-sales-invoices",
 *     tags={"Service Sales Invoices"},
 *     summary="Create service sales invoice (legacy)",
 *     description="Legacy alias. Prefer POST /sales-invoices with service_lines (and optional item lines).",
 *     operationId="createServiceSalesInvoice",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(required=true, @OA\JsonContent(
 *         required={"party_id","invoice_date","due_date","service_lines"},
 *         @OA\Property(property="party_id", type="integer"),
 *         @OA\Property(property="invoice_date", type="string", format="date"),
 *         @OA\Property(property="due_date", type="string", format="date"),
 *         @OA\Property(property="service_lines", type="array", @OA\Items(
 *             @OA\Property(property="account_id", type="integer"),
 *             @OA\Property(property="tax_rate_id", type="integer"),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="amount", type="number")
 *         ))
 *     )),
 *     @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/service-sales-invoices/{id}",
 *     tags={"Service Sales Invoices"},
 *     summary="Get service sales invoice",
 *     operationId="getServiceSalesInvoiceById",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Put(
 *     path="/service-sales-invoices/{id}",
 *     tags={"Service Sales Invoices"},
 *     summary="Update service sales invoice",
 *     operationId="updateServiceSalesInvoice",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Delete(
 *     path="/service-sales-invoices/{id}",
 *     tags={"Service Sales Invoices"},
 *     summary="Delete service sales invoice",
 *     operationId="deleteServiceSalesInvoice",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Deleted", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Post(
 *     path="/service-sales-invoices/{id}/payment",
 *     tags={"Service Sales Invoices"},
 *     summary="Record payment against service sales invoice",
 *     operationId="recordServiceSalesInvoicePayment",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(required=true, @OA\JsonContent(
 *         required={"amount","payment_mode","cash_bank_account_id"},
 *         @OA\Property(property="amount", type="number", format="float"),
 *         @OA\Property(property="payment_mode", type="string", enum={"cash","bank","od"}),
 *         @OA\Property(property="cash_bank_account_id", type="integer"),
 *         @OA\Property(property="payment_date", type="string", format="date")
 *     )),
 *     @OA\Response(response=200, description="Payment recorded", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 */
class SalesInvoiceDocs
{
    //
}
