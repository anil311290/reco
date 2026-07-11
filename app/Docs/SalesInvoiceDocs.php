<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/sales-invoices",
 *     tags={"Sales Invoices", "Item Sales Invoices", "Service Sales Invoices"},
 *     summary="List sales invoices",
 *     description="Returns paginated sales invoices for both item-based and service-based flows. Use invoice_type=item for item invoices (default) or invoice_type=service for service invoices.",
 *     operationId="getSalesInvoices",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
 *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"draft","sent","partial","paid","overdue","cancelled"})),
 *     @OA\Parameter(name="party_id", in="query", @OA\Schema(type="integer")),
 *     @OA\Parameter(name="invoice_type", in="query", description="Filter by invoice type: item (default) or service", @OA\Schema(type="string", enum={"item","service"}, default="item")),
 *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/sales-invoices/overdue",
 *     tags={"Sales Invoices", "Item Sales Invoices", "Service Sales Invoices"},
 *     summary="Get overdue invoices",
 *     operationId="getOverdueSalesInvoices",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/sales-invoices/{id}",
 *     tags={"Sales Invoices", "Item Sales Invoices", "Service Sales Invoices"},
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
 *     tags={"Sales Invoices", "Item Sales Invoices", "Service Sales Invoices"},
 *     summary="Create sales invoice",
 *     description="Creates an item or service sales invoice. Set invoice_type=service for service-only invoices (no line items required, only service_lines). Default is item. This single API supports the same behavior used by the web item-sales and service-sales modules.",
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
 *     tags={"Sales Invoices", "Item Sales Invoices", "Service Sales Invoices"},
 *     summary="Record payment against invoice",
 *     operationId="recordSalesInvoicePayment",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(required=true, @OA\JsonContent(
 *         required={"amount"},
 *         @OA\Property(property="amount", type="number", format="float", example=500.00)
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
 */
class SalesInvoiceDocs
{
    //
}
