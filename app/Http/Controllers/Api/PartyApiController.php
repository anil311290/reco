<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PartyRequest;
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

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'type', 'is_active']);
        $filters['company_id'] = $request->user()->company_id;

        $parties = $this->partyService->getAll($filters);

        return ResponseHelper::success(
            PartyResource::collection($parties)
        );
    }

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

    public function store(PartyRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['company_id'] = $request->user()->company_id;
            $data['created_by'] = $request->user()->id;
            $data['updated_by'] = $request->user()->id;
            $data['created_by_ip'] = $request->ip();
            $data['updated_by_ip'] = $request->ip();

            $party = $this->partyService->create($data);

            return ResponseHelper::success(
                new PartyResource($party),
                'Party created successfully',
                201
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function update(PartyRequest $request, int $id): JsonResponse
    {
        try {
            $party = $this->partyService->getById($id);

            if (!$party || $party->company_id !== $request->user()->company_id) {
                return ResponseHelper::notFound('Party not found');
            }

            $data = $request->validated();
            $data['updated_by'] = $request->user()->id;
            $data['updated_by_ip'] = $request->ip();

            $updated = $this->partyService->update($id, $data);

            if (!$updated) {
                return ResponseHelper::notFound('Party not found');
            }

            return ResponseHelper::success(
                new PartyResource($this->partyService->getById($id)),
                'Party updated successfully'
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $party = $this->partyService->getById($id);

            if (!$party || $party->company_id !== request()->user()->company_id) {
                return ResponseHelper::notFound('Party not found');
            }

            $deleted = $this->partyService->delete($id);

            if (!$deleted) {
                return ResponseHelper::notFound('Party not found');
            }

            return ResponseHelper::success(null, 'Party deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function changeStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|boolean',
        ]);

        try {
            $party = $this->partyService->getById($id);

            if (!$party || $party->company_id !== $request->user()->company_id) {
                return ResponseHelper::notFound('Party not found');
            }

            $updated = $this->partyService->update($id, [
                'is_active' => $request->status,
            ]);

            if (!$updated) {
                return ResponseHelper::notFound('Party not found');
            }

            $statusText = $request->status ? 'activated' : 'deactivated';

            return ResponseHelper::success(null, "Party {$statusText} successfully");
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

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
