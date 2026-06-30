<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/themes/current",
 *     tags={"Themes"},
 *     summary="Get current theme",
 *     operationId="getCurrentTheme",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/themes",
 *     tags={"Themes"},
 *     summary="List available themes",
 *     operationId="getThemes",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Put(
 *     path="/themes",
 *     tags={"Themes"},
 *     summary="Update theme settings",
 *     operationId="updateTheme",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(@OA\JsonContent(
 *         @OA\Property(property="primary_color", type="string", example="#6366f1"),
 *         @OA\Property(property="secondary_color", type="string", example="#8b5cf6"),
 *         @OA\Property(property="sidebar_color", type="string", example="#1e1b4b"),
 *         @OA\Property(property="header_color", type="string", example="#ffffff"),
 *         @OA\Property(property="dark_mode", type="boolean", example=false),
 *         @OA\Property(property="font_family", type="string", example="Inter")
 *     )),
 *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Post(
 *     path="/themes/apply",
 *     tags={"Themes"},
 *     summary="Apply a predefined theme",
 *     operationId="applyTheme",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(required=true, @OA\JsonContent(
 *         required={"theme_id"},
 *         @OA\Property(property="theme_id", type="integer", example=1)
 *     )),
 *     @OA\Response(response=200, description="Applied", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Post(
 *     path="/themes/toggle-dark-mode",
 *     tags={"Themes"},
 *     summary="Toggle dark mode",
 *     operationId="toggleDarkMode",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Toggled", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 */
class ThemeDocs
{
    //
}
