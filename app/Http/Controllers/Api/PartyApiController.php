<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PartyResource;
use App\Services\PartyService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartyApiController extends Controller
{
    protected PartyService $partyService;

    public function __construct(PartyService $partyService)
    {
        $this->partyService = $partyService;
    }

    /**
     * Get all parties
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'type', 'is_active']);
        $filters['company_id'] = $request->user()->company_id;

        $parties = $this->partyService->getAll($filters);

        return ResponseHelper::success(
            PartyResource::collection($parties)
        );
    }

    /**
     * Get party by ID
     */
    public function show(int $id): JsonResponse
    {
        $party = $this->partyService->getById($id);

        if (!$party || $party->company_id !== request()->user()->company_id) {
            return ResponseHelper::notFound('Party not found');
        }

        return ResponseHelper::success(
            new PartyResource($party)
        );
    }

    /**
     * Get parties by type
     */
    public function getByType(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:debtor,creditor',
        ]);

        $companyId = $request->user()->company_id;
        $parties = $this->partyService->getForDropdown($companyId, $request->type);

        return ResponseHelper::success($parties);
    }
}
