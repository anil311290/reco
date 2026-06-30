<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionPlanController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $plans = SubscriptionPlan::orderBy('sort_order')->get();

            return response()->json([
                'data' => $plans,
                'recordsTotal' => $plans->count(),
                'recordsFiltered' => $plans->count(),
                'draw' => $request->input('draw'),
            ]);
        }

        return view('admin.subscriptions.plans-management');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:subscription_plans,slug',
            'description' => 'nullable|string',
            'monthly_price' => 'required|numeric|min:0',
            'yearly_price' => 'required|numeric|min:0',
            'lifetime_price' => 'nullable|numeric|min:0',
            'trial_days' => 'required|integer|min:0',
            'max_users' => 'required|integer|min:1',
            'max_transactions' => 'required|integer|min:0',
            'max_accounts' => 'required|integer|min:0',
            'max_parties' => 'required|integer|min:0',
            'features' => 'nullable|array',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'is_visible' => 'boolean',
        ]);

        $plan = SubscriptionPlan::create($validated);

        return ResponseHelper::success($plan, 'Plan created successfully');
    }

    public function show(int $id): JsonResponse
    {
        $plan = SubscriptionPlan::findOrFail($id);
        return ResponseHelper::success($plan);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $plan = SubscriptionPlan::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:subscription_plans,slug,' . $id,
            'description' => 'nullable|string',
            'monthly_price' => 'sometimes|numeric|min:0',
            'yearly_price' => 'sometimes|numeric|min:0',
            'lifetime_price' => 'nullable|numeric|min:0',
            'trial_days' => 'sometimes|integer|min:0',
            'max_users' => 'sometimes|integer|min:1',
            'max_transactions' => 'sometimes|integer|min:0',
            'max_accounts' => 'sometimes|integer|min:0',
            'max_parties' => 'sometimes|integer|min:0',
            'features' => 'nullable|array',
            'sort_order' => 'sometimes|integer|min:0',
            'is_active' => 'boolean',
            'is_visible' => 'boolean',
        ]);

        $plan->update($validated);

        return ResponseHelper::success($plan, 'Plan updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $plan = SubscriptionPlan::findOrFail($id);
        $plan->delete();

        return ResponseHelper::success(null, 'Plan deleted successfully');
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $plan = SubscriptionPlan::findOrFail($id);
        $plan->update(['is_active' => !$plan->is_active]);

        return ResponseHelper::success($plan, 'Status updated');
    }
}