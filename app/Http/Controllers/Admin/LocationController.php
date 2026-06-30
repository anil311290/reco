<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    public function countries(): JsonResponse
    {
        $countries = Country::active()->get(['id', 'name', 'iso2']);
        return response()->json($countries);
    }

    public function states(int $countryId): JsonResponse
    {
        $states = State::active()->forCountry($countryId)->get(['id', 'name', 'code']);
        return response()->json($states);
    }

    public function cities(int $stateId): JsonResponse
    {
        $cities = City::active()->forState($stateId)->get(['id', 'name']);
        return response()->json($cities);
    }
}
