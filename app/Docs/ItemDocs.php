<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/items",
 *     tags={"Items"},
 *     summary="List all items",
 *     description="Get all items/services for the authenticated user's company",
 *     operationId="getItems",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
 *     @OA\Parameter(name="type", in="query", @OA\Schema(type="string", enum={"goods","service"})),
 *     @OA\Parameter(name="category_id", in="query", @OA\Schema(type="integer")),
 *     @OA\Parameter(name="is_active", in="query", @OA\Schema(type="boolean")),
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/items/low-stock",
 *     tags={"Items"},
 *     summary="Get low stock items",
 *     description="Get items below reorder level",
 *     operationId="getLowStockItems",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/items/{id}",
 *     tags={"Items"},
 *     summary="Get item by ID",
 *     operationId="getItem",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Post(
 *     path="/items",
 *     tags={"Items"},
 *     summary="Create item",
 *     operationId="createItem",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(required=true, @OA\JsonContent(
 *         required={"item_code","name","type"},
 *         @OA\Property(property="item_code", type="string", example="ITEM-001"),
 *         @OA\Property(property="name", type="string", example="Widget A"),
 *         @OA\Property(property="type", type="string", enum={"goods","service"}),
 *         @OA\Property(property="category_id", type="integer", example=2),
 *         @OA\Property(property="hsn_sac_code", type="string", example="8471"),
 *         @OA\Property(property="tax_rate_id", type="integer", example=1),
 *         @OA\Property(property="purchase_price", type="number", format="float", example=100.00),
 *         @OA\Property(property="selling_price", type="number", format="float", example=150.00),
 *         @OA\Property(property="unit", type="string", example="nos"),
 *         @OA\Property(property="opening_stock", type="number", format="float", example=50),
 *         @OA\Property(property="reorder_level", type="number", format="float", example=10)
 *     )),
 *     @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Put(
 *     path="/items/{id}",
 *     tags={"Items"},
 *     summary="Update item",
 *     operationId="updateItem",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(@OA\JsonContent(
 *         @OA\Property(property="name", type="string"),
 *         @OA\Property(property="selling_price", type="number", format="float"),
 *         @OA\Property(property="purchase_price", type="number", format="float"),
 *         @OA\Property(property="category_id", type="integer"),
 *         @OA\Property(property="tax_rate_id", type="integer")
 *     )),
 *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
class ItemDocs
{
    //
}
