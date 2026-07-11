<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use App\Services\FinancialYearService;
use App\Http\Resources\CompanyResource;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsApiController extends Controller
{
    protected SettingsService $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Get company settings
     */
    public function getCompanySettings(Request $request): JsonResponse
    {
        $company = $request->user()->company;

        if (!$company) {
            return ResponseHelper::notFound('Company not found');
        }

        return ResponseHelper::success(
            new CompanyResource($company)
        );
    }

    /**
     * Get theme settings
     */
    public function getThemeSettings(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $settings = [
            'primary_color' => $this->settingsService->get('theme.primary_color', '#4f46e5', $companyId),
            'secondary_color' => $this->settingsService->get('theme.secondary_color', '#6b7280', $companyId),
            'sidebar_color' => $this->settingsService->get('theme.sidebar_color', '#1e1b4b', $companyId),
            'header_color' => $this->settingsService->get('theme.header_color', '#ffffff', $companyId),
            'dark_mode' => $this->settingsService->get('theme.dark_mode', '0', $companyId) === '1',
        ];

        return ResponseHelper::success($settings);
    }

    /**
     * Get financial years
     */
    public function getFinancialYears(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $financialYears = \App\Models\FinancialYear::where('company_id', $companyId)
            ->orderBy('start_date', 'desc')
            ->get();

        return ResponseHelper::success($financialYears);
    }

    /**
     * Get current financial year
     */
    public function getCurrentFinancialYear(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $financialYear = \App\Models\FinancialYear::getCurrent($companyId);

        if (!$financialYear) {
            return ResponseHelper::notFound('No active financial year found');
        }

        return ResponseHelper::success($financialYear);
    }

    /**
     * Get all settings
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $company = $request->user()->company;
        $theme = [
            'primary_color' => $this->settingsService->get('theme.primary_color', '#4f46e5', $companyId),
            'secondary_color' => $this->settingsService->get('theme.secondary_color', '#6b7280', $companyId),
            'sidebar_color' => $this->settingsService->get('theme.sidebar_color', '#1e1b4b', $companyId),
            'header_color' => $this->settingsService->get('theme.header_color', '#ffffff', $companyId),
            'dark_mode' => $this->settingsService->get('theme.dark_mode', '0', $companyId) === '1',
        ];

        $financialYear = \App\Models\FinancialYear::getCurrent($companyId);
        $accounting = [
            'sales_tax_ledger_id' => $this->settingsService->get('sales_tax_ledger_id', null, $companyId),
            'purchase_tax_ledger_id' => $this->settingsService->get('purchase_tax_ledger_id', null, $companyId),
            'tds_ledger_id' => $this->settingsService->get('tds_ledger_id', null, $companyId),
            'tcs_ledger_id' => $this->settingsService->get('tcs_ledger_id', null, $companyId),
            'cess_ledger_id' => $this->settingsService->get('cess_ledger_id', null, $companyId),
        ];

        return ResponseHelper::success([
            'company' => new CompanyResource($company),
            'theme' => $theme,
            'accounting' => $accounting,
            'financial_year' => $financialYear,
            'currency' => $company->currency ?? 'INR',
            'timezone' => $company->timezone ?? 'Asia/Kolkata',
        ]);
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
            $companyId = $request->user()->company_id;
            $this->settingsService->updateCompanySettings($request->all(), $companyId);

            return ResponseHelper::success(
                new CompanyResource($request->user()->company->fresh()),
                'Company settings updated successfully'
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Update accounting settings
     */
    public function updateAccounting(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $request->validate([
            'sales_tax_ledger_id' => [
                'nullable',
                \Illuminate\Validation\Rule::exists('accounts', 'id')->where('company_id', $companyId),
            ],
            'purchase_tax_ledger_id' => [
                'nullable',
                \Illuminate\Validation\Rule::exists('accounts', 'id')->where('company_id', $companyId),
            ],
            'tds_ledger_id' => [
                'nullable',
                \Illuminate\Validation\Rule::exists('accounts', 'id')->where('company_id', $companyId),
            ],
            'tcs_ledger_id' => [
                'nullable',
                \Illuminate\Validation\Rule::exists('accounts', 'id')->where('company_id', $companyId),
            ],
            'cess_ledger_id' => [
                'nullable',
                \Illuminate\Validation\Rule::exists('accounts', 'id')->where('company_id', $companyId),
            ],
        ]);

        try {
            $this->settingsService->updateAccountingSettings($request->all(), $companyId);

            return ResponseHelper::success(null, 'Accounting settings updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
