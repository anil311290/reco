<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/settings",
 *     tags={"Settings"},
 *     summary="Get all app settings",
 *     operationId="getSettings",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Settings fetched", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/settings/company",
 *     tags={"Settings"},
 *     summary="Get company settings",
 *     operationId="getCompanySettings",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Company settings fetched", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/settings/theme",
 *     tags={"Settings"},
 *     summary="Get theme settings",
 *     operationId="getThemeSettings",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Theme settings fetched", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Put(
 *     path="/settings/theme",
 *     tags={"Settings"},
 *     summary="Update theme settings",
 *     description="Colors must be 6-digit hex values including the leading #. Returns the regenerated theme CSS.",
 *     operationId="updateThemeSettings",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"primary_color", "secondary_color", "sidebar_color", "header_color"},
 *             @OA\Property(property="primary_color", type="string", example="#0d6efd"),
 *             @OA\Property(property="secondary_color", type="string", example="#6c757d"),
 *             @OA\Property(property="sidebar_color", type="string", example="#212529"),
 *             @OA\Property(property="header_color", type="string", example="#ffffff"),
 *             @OA\Property(property="dark_mode", type="boolean", example=false)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Theme settings updated",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Theme settings updated successfully"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="css", type="string", example=":root{--primary:#0d6efd;}"),
 *                 @OA\Property(property="theme", type="object")
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/settings/financial-years",
 *     tags={"Settings"},
 *     summary="Get financial years",
 *     operationId="getFinancialYears",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Financial years fetched", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/settings/financial-year/current",
 *     tags={"Settings"},
 *     summary="Get current financial year",
 *     operationId="getCurrentFinancialYear",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Current financial year fetched", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Put(
 *     path="/settings/company",
 *     tags={"Settings"},
 *     summary="Update company settings",
 *     operationId="updateCompanySettings",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Put(
 *     path="/settings/accounting",
 *     tags={"Settings"},
 *     summary="Update accounting settings",
 *     operationId="updateAccountingSettings",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 */
class SettingsDocs
{
    //
}
