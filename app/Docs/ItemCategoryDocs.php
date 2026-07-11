<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/item-categories",
 *     tags={"Item Categories"},
 *     summary="List item categories",
 *     operationId="getItemCategories",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Post(
 *     path="/item-categories",
 *     tags={"Item Categories"},
 *     summary="Create item category",
 *     operationId="createItemCategory",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(required=true, @OA\JsonContent(required={"name"}, @OA\Property(property="name", type="string"))),
 *     @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/item-categories/dropdown",
 *     tags={"Item Categories"},
 *     summary="Get item categories for dropdown",
 *     operationId="getItemCategoriesDropdown",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/item-categories/{id}",
 *     tags={"Item Categories"},
 *     summary="Get item category",
 *     operationId="getItemCategory",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Put(
 *     path="/item-categories/{id}",
 *     tags={"Item Categories"},
 *     summary="Update item category",
 *     operationId="updateItemCategory",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Delete(
 *     path="/item-categories/{id}",
 *     tags={"Item Categories"},
 *     summary="Delete item category",
 *     operationId="deleteItemCategory",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Deleted", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Patch(
 *     path="/item-categories/{id}/status",
 *     tags={"Item Categories"},
 *     summary="Toggle item category status",
 *     operationId="toggleItemCategoryStatus",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 */
class ItemCategoryDocs
{
    //
}
