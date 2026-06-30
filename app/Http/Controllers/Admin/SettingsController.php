<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AccountService;
use App\Services\SettingsService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    protected SettingsService $settingsService;
    protected AccountService $accountService;

    public function __construct(SettingsService $settingsService, AccountService $accountService)
    {
        $this->settingsService = $settingsService;
        $this->accountService = $accountService;
    }

    /**
     * Display settings page
     */
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $settings = $this->settingsService->getAllGrouped($companyId);
        $company = Auth::user()->company;
        $accounts = $this->accountService->getAll([
            'company_id' => $companyId,
            'is_active' => true,
        ]);

        return view('admin.settings.index', compact('settings', 'company', 'accounts'));
    }

    /**
     * Update company settings
     */
    public function updateCompany(Request $request): JsonResponse
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'company_email' => 'nullable|email|max:255',
            'company_phone' => 'nullable|string|max:20',
            'company_address' => 'required|string|max:500',
            'company_city' => 'required|string|max:100',
            'company_state' => 'required|string|max:100',
            'company_country' => 'required|string|max:100',
            'company_postal_code' => 'nullable|string|max:20',
            'company_gst_number' => 'nullable|string|max:20',
            'company_pan_number' => 'nullable|string|max:20',
            'company_currency' => 'required|string|max:3',
            'company_timezone' => 'required|string|max:50',
            'financial_year_start' => 'required|string|max:5',
            'financial_year_end' => 'required|string|max:5',
        ]);

        try {
            $companyId = Auth::user()->company_id;
            $this->settingsService->updateCompanySettings($request->all(), $companyId);

            return ResponseHelper::success(null, 'Company settings updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Update theme settings
     */
    public function updateTheme(Request $request): JsonResponse
    {
        $request->validate([
            'primary_color' => 'required|string|max:7',
            'secondary_color' => 'required|string|max:7',
            'sidebar_color' => 'required|string|max:7',
            'header_color' => 'required|string|max:7',
            'dark_mode' => 'boolean',
        ]);

        try {
            $companyId = Auth::user()->company_id;
            $this->settingsService->updateThemeSettings($request->all(), $companyId);

            return ResponseHelper::success(null, 'Theme settings updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    public function updateAccounting(Request $request): JsonResponse
    {
        $companyId = Auth::user()->company_id;

        $request->validate([
            'sales_tax_ledger_id' => [
                'nullable',
                Rule::exists('accounts', 'id')->where('company_id', $companyId),
            ],
            'purchase_tax_ledger_id' => [
                'nullable',
                Rule::exists('accounts', 'id')->where('company_id', $companyId),
            ],
            'tds_ledger_id' => [
                'nullable',
                Rule::exists('accounts', 'id')->where('company_id', $companyId),
            ],
            'tcs_ledger_id' => [
                'nullable',
                Rule::exists('accounts', 'id')->where('company_id', $companyId),
            ],
            'cess_ledger_id' => [
                'nullable',
                Rule::exists('accounts', 'id')->where('company_id', $companyId),
            ],
        ]);

        try {
            $this->settingsService->updateAccountingSettings($request->all(), $companyId);

            return ResponseHelper::success(null, 'Accounting settings updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Get theme CSS
     */
    public function getThemeCss(): JsonResponse
    {
        $companyId = Auth::user()->company_id;
        $css = $this->settingsService->getThemeCss($companyId);

        return response()->json(['css' => $css]);
    }
}
