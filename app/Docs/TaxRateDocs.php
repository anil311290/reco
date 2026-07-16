<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/tax-rates",
 *     tags={"Tax Rates"},
 *     summary="List all tax rates",
 *     description="Get all tax rates for the authenticated user's company",
 *     operationId="getTaxRates",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(parameter="search", name="search", in="query", description="Search by name or code", @OA\Schema(type="string")),
 *     @OA\Parameter(parameter="tax_type", name="tax_type", in="query", description="Filter by tax type", @OA\Schema(type="string", enum={"addition","deduction"})),
 *     @OA\Parameter(parameter="tax_category", name="tax_category", in="query", description="Filter by tax category", @OA\Schema(type="string", enum={"GST","CGST","SGST","IGST","TDS","TCS","CESS","OTHER"})),
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/tax-rates/{id}",
 *     tags={"Tax Rates"},
 *     summary="Get tax rate by ID",
 *     operationId="getTaxRate",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(parameter="tax_rate_id", name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Post(
 *     path="/tax-rates",
 *     tags={"Tax Rates"},
 *     summary="Create tax rate",
 *     operationId="createTaxRate",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(required=true, @OA\JsonContent(
 *         required={"tax_name","tax_rate","tax_type","tax_category"},
 *         @OA\Property(property="tax_name", type="string", example="GST 18%"),
 *         @OA\Property(property="tax_code", type="string", example="GST18"),
 *         @OA\Property(property="tax_rate", type="number", format="float", example=18.0),
 *         @OA\Property(property="tax_type", type="string", enum={"addition","deduction"}),
 *         @OA\Property(property="tax_category", type="string", enum={"GST","CGST","SGST","IGST","TDS","TCS","CESS","OTHER"}),
 *         @OA\Property(property="notes", type="string", example="Default GST configuration"),
 *         @OA\Property(property="status", type="string", enum={"active","inactive"}, example="active")
 *     )),
 *     @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Put(
 *     path="/tax-rates/{id}",
 *     tags={"Tax Rates"},
 *     summary="Update tax rate",
 *     operationId="updateTaxRate",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(parameter="tax_rate_id_update", name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(@OA\JsonContent(
 *         @OA\Property(property="tax_name", type="string"),
 *         @OA\Property(property="tax_rate", type="number", format="float"),
 *         @OA\Property(property="tax_type", type="string", enum={"addition","deduction"}),
 *         @OA\Property(property="tax_category", type="string", enum={"GST","CGST","SGST","IGST","TDS","TCS","CESS","OTHER"}),
 *         @OA\Property(property="status", type="string", enum={"active","inactive"})
 *     )),
 *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Delete(
 *     path="/tax-rates/{id}",
 *     tags={"Tax Rates"},
 *     summary="Delete tax rate",
 *     operationId="deleteTaxRate",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Deleted", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/tax-rates/dropdown",
 *     tags={"Tax Rates"},
 *     summary="Tax rates dropdown list",
 *     operationId="getTaxRatesDropdown",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Patch(
 *     path="/tax-rates/{id}/status",
 *     tags={"Tax Rates"},
 *     summary="Toggle tax rate status",
 *     operationId="toggleTaxRateStatus",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(@OA\JsonContent(@OA\Property(property="status", type="string", enum={"active","inactive"}))),
 *     @OA\Response(response=200, description="Status updated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 */
class TaxRateDocs
{
    //
}
