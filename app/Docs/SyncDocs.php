<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Post(
 *     path="/sync/upload",
 *     tags={"Sync"},
 *     summary="Upload offline changes (batch queue)",
 *     operationId="syncUpload",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"entries"},
 *             @OA\Property(property="device_id", type="string", example="android-abc123"),
 *             @OA\Property(property="auto_process", type="boolean", example=true),
 *             @OA\Property(
 *                 property="entries",
 *                 type="array",
 *                 @OA\Items(
 *                     required={"table_name","record_uuid","operation"},
 *                     @OA\Property(property="table_name", type="string", example="accounts"),
 *                     @OA\Property(property="record_uuid", type="string", format="uuid"),
 *                     @OA\Property(property="operation", type="string", enum={"create","update","delete"}),
 *                     @OA\Property(property="payload", type="object"),
 *                     @OA\Property(property="local_version", type="integer", example=1)
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response=200, description="Upload queued/processed", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Post(
 *     path="/sync/run",
 *     tags={"Sync"},
 *     summary="Manual sync — process pending queue",
 *     operationId="syncRun",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         @OA\JsonContent(@OA\Property(property="device_id", type="string", example="android-abc123"))
 *     ),
 *     @OA\Response(response=200, description="Manual sync completed", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/sync/download",
 *     tags={"Sync"},
 *     summary="Download server changes since timestamp",
 *     operationId="syncDownload",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="since", in="query", @OA\Schema(type="string", format="date-time")),
 *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", example=1)),
 *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", example=100)),
 *     @OA\Response(response=200, description="Delta download", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/sync/bootstrap",
 *     tags={"Sync"},
 *     summary="Initial full data load for offline app",
 *     operationId="syncBootstrap",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Bootstrap payload", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 *
 * @OA\Get(
 *     path="/sync/status",
 *     tags={"Sync"},
 *     summary="Sync queue status and syncable tables",
 *     operationId="syncStatus",
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="device_id", in="query", @OA\Schema(type="string")),
 *     @OA\Response(response=200, description="Sync status", @OA\JsonContent(ref="#/components/schemas/SuccessResponse"))
 * )
 */
class SyncDocs
{
    //
}
