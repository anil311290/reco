<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Get(
 *     path="/locations/countries",
 *     tags={"Locations"},
 *     summary="Get countries list",
 *     operationId="getCountries",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Countries fetched", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/locations/{countryId}/states",
 *     tags={"Locations"},
 *     summary="Get states by country",
 *     operationId="getStatesByCountry",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="countryId", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="States fetched", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=404, description="Country not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/locations/{stateId}/cities",
 *     tags={"Locations"},
 *     summary="Get cities by state",
 *     operationId="getCitiesByState",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="stateId", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Cities fetched", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=404, description="State not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/states",
 *     tags={"Locations"},
 *     summary="Get list of states",
 *     operationId="getStates",
 *     @OA\Response(response=200, description="States fetched", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/states/{stateId}/cities",
 *     tags={"Locations"},
 *     summary="Get cities by state without authentication",
 *     operationId="getStatesCitiesByState",
 *     @OA\Parameter(name="stateId", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Cities fetched", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
 *     @OA\Response(response=404, description="State not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
 * )
 */
class LocationDocs
{
    //
}
