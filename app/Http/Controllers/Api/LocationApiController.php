<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use Illuminate\Http\JsonResponse;

class LocationApiController extends Controller
{
    /**
     * GET /api/locations/countries
     */
    public function countries(): JsonResponse
    {
        $countries = Country::active()->get(['id', 'name', 'iso2', 'phone_code']);

        return response()->json(['success' => true, 'data' => $countries]);
    }

    /**
     * GET /api/locations/states?country_id=X
     */
    public function states(int $countryId): JsonResponse
    {
        $states = State::active()
            ->forCountry($countryId)
            ->get(['id', 'name', 'code']);

        return response()->json(['success' => true, 'data' => $states]);
    }

    /**
     * GET /api/locations/cities?state_id=X
     */
    public function cities(int $stateId): JsonResponse
    {
        $cities = City::active()
            ->forState($stateId)
            ->get(['id', 'name']);

        return response()->json(['success' => true, 'data' => $cities]);
    }
}
