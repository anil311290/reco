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
