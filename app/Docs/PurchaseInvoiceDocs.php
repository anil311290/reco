<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/purchase-invoices",
 *     tags={"Purchase Invoices"},
 *     summary="List purchase invoices",
 *     operationId="getPurchaseInvoices",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
 *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"draft","verified","partial","paid","overdue","cancelled"})),
 *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/purchase-invoices/{id}",
 *     tags={"Purchase Invoices"},
 *     summary="Get invoice details",
 *     operationId="getPurchaseInvoice",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Post(
 *     path="/purchase-invoices",
 *     tags={"Purchase Invoices"},
 *     summary="Create purchase invoice",
 *     operationId="createPurchaseInvoice",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(required=true, @OA\JsonContent(
 *         required={"party_id","invoice_date","due_date","lines"},
 *         @OA\Property(property="party_id", type="integer", description="Supplier ID"),
 *         @OA\Property(property="supplier_invoice_number", type="string", example="SUP-INV-001"),
 *         @OA\Property(property="invoice_date", type="string", format="date"),
 *         @OA\Property(property="due_date", type="string", format="date"),
 *         @OA\Property(property="notes", type="string"),
 *         @OA\Property(property="discount_percentage", type="number", format="float"),
 *         @OA\Property(property="lines", type="array", @OA\Items(
 *             required={"quantity","unit_price"},
 *             @OA\Property(property="item_id", type="integer"),
 *             @OA\Property(property="account_id", type="integer"),
 *             @OA\Property(property="tax_rate_id", type="integer"),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="quantity", type="number", format="float"),
 *             @OA\Property(property="unit_price", type="number", format="float"),
 *             @OA\Property(property="discount_percentage", type="number", format="float")
 *         ))
 *     )),
 *     @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Post(
 *     path="/purchase-invoices/{id}/payment",
 *     tags={"Purchase Invoices"},
 *     summary="Record payment against purchase invoice",
 *     operationId="recordPurchaseInvoicePayment",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(required=true, @OA\JsonContent(
 *         required={"amount"},
 *         @OA\Property(property="amount", type="number", format="float")
 *     )),
 *     @OA\Response(response=200, description="Payment recorded", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Put(
 *     path="/purchase-invoices/{id}",
 *     tags={"Purchase Invoices"},
 *     summary="Update purchase invoice",
 *     operationId="updatePurchaseInvoice",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Delete(
 *     path="/purchase-invoices/{id}",
 *     tags={"Purchase Invoices"},
 *     summary="Delete purchase invoice",
 *     operationId="deletePurchaseInvoice",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Deleted", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 */
class PurchaseInvoiceDocs
{
    //
}
