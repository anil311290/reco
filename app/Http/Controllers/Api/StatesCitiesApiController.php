<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\State;
use App\Models\City;
use Illuminate\Http\JsonResponse;

class StatesCitiesApiController extends Controller
{
    /**
     * GET /api/states
     */
    public function states(): JsonResponse
    {
        $states = State::all(['id', 'name', 'code'])->sortBy('name');
        return response()->json($states->values());
    }

    /**
     * GET /api/states/{stateId}/cities
     */
    public function cities(int $stateId): JsonResponse
    {
        $cities = City::where('state_id', $stateId)
            ->get(['id', 'name'])
            ->sortBy('name');
        return response()->json($cities->values());
    }
}
