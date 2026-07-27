<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Company;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SettingsService
{
    /**
     * Get all settings grouped
     */
    public function getAllGrouped(?int $companyId = null): array
    {
        return Setting::when($companyId, function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
        ->orderBy('group')
        ->orderBy('key')
        ->get()
        ->groupBy('group')
        ->map(function ($groupedSettings, $group) {
            return $groupedSettings->mapWithKeys(function ($setting) use ($group) {
                $prefix = $group . '.';
                $key = Str::startsWith($setting->key, $prefix)
                    ? Str::after($setting->key, $prefix)
                    : $setting->key;

                return [$key => $setting->value];
            })->toArray();
        })
        ->toArray();
    }

    /**
     * Get settings by group
     */
    public function getByGroup(string $group, ?int $companyId = null): array
    {
        return Setting::getByGroup($group, $companyId);
    }

    /**
     * Get single setting value
     */
    public function get(string $key, $default = null, ?int $companyId = null)
    {
        return Setting::getValue($key, $default, $companyId);
    }

    /**
     * Update settings
     */
    public function update(array $data, ?int $companyId = null): bool
    {
        try {
            DB::beginTransaction();

            foreach ($data as $key => $value) {
                // Determine group from key prefix
                $parts = explode('.', $key);
                $group = count($parts) > 1 ? $parts[0] : 'general';
                
                Setting::setValue($key, $value, $companyId, $group);
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update company settings
     */
    public function updateCompanySettings(array $data, int $companyId): bool
    {
        try {
            DB::beginTransaction();

            // Update company record
            $company = Company::find($companyId);
            if ($company) {
                $company->update([
                    'name' => $data['company_name'] ?? $company->name,
                    'email' => $data['company_email'] ?? $company->email,
                    'phone' => $data['company_phone'] ?? $company->phone,
                    'address' => $data['company_address'] ?? $company->address,
                    'city' => $data['company_city'] ?? $company->city,
                    'state' => $data['company_state'] ?? $company->state,
                    'country' => $data['company_country'] ?? $company->country,
                    'postal_code' => $data['company_postal_code'] ?? $company->postal_code,
                    'gst_number' => $data['company_gst_number'] ?? $company->gst_number,
                    'pan_number' => $data['company_pan_number'] ?? $company->pan_number,
                    'currency' => $data['company_currency'] ?? $company->currency,
                    'timezone' => $data['company_timezone'] ?? $company->timezone,
                    'financial_year_start' => $data['financial_year_start'] ?? $company->financial_year_start,
                    'financial_year_end' => $data['financial_year_end'] ?? $company->financial_year_end,
                ]);
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update theme settings
     */
    public function updateThemeSettings(array $data, ?int $companyId = null): bool
    {
        try {
            DB::beginTransaction();

            $themeSettings = [
                'theme.primary_color' => $data['primary_color'] ?? '#1f6feb',
                'theme.secondary_color' => $data['secondary_color'] ?? '#6b7280',
                'theme.sidebar_color' => $data['sidebar_color'] ?? '#ffffff',
                'theme.header_color' => $data['header_color'] ?? '#ffffff',
            ];

            if (array_key_exists('dark_mode', $data)) {
                $themeSettings['theme.dark_mode'] = filter_var($data['dark_mode'], FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
            }

            foreach ($themeSettings as $key => $value) {
                Setting::setValue($key, $value, $companyId, 'theme');
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateAccountingSettings(array $data, int $companyId): bool
    {
        try {
            DB::beginTransaction();

            $salesTaxId = $this->resolveSystemTaxLedgerId($companyId, Account::CODE_SALES_TAX);
            $purchaseTaxId = $this->resolveSystemTaxLedgerId($companyId, Account::CODE_PURCHASE_TAX);

            $settings = [
                'sales_tax_ledger_id' => $salesTaxId,
                'purchase_tax_ledger_id' => $purchaseTaxId,
                'tds_ledger_id' => $data['tds_ledger_id'] ?? null,
                'tcs_ledger_id' => $data['tcs_ledger_id'] ?? null,
                'cess_ledger_id' => $data['cess_ledger_id'] ?? null,
            ];

            foreach ($settings as $key => $value) {
                Setting::setValue($key, $value, $companyId, 'accounting');
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function resolveSystemTaxLedgerId(int $companyId, string $code): ?string
    {
        $account = Account::where('company_id', $companyId)
            ->where('account_code', $code)
            ->first();

        return $account ? (string) $account->id : null;
    }

    /**
     * Get theme CSS variables
     */
    public function getThemeCss(?int $companyId = null): string
    {
        return $this->buildThemeCss([
            'primary_color' => Setting::getValue('theme.primary_color', '#1f6feb', $companyId),
            'secondary_color' => Setting::getValue('theme.secondary_color', '#6b7280', $companyId),
            'sidebar_color' => Setting::getValue('theme.sidebar_color', '#ffffff', $companyId),
            'header_color' => Setting::getValue('theme.header_color', '#ffffff', $companyId),
        ]);
    }

    /**
     * Build theme CSS from color values (used for saved theme and live preview).
     */
    public function buildThemeCss(array $colors): string
    {
        $primaryColor = $colors['primary_color'] ?? '#1f6feb';
        $secondaryColor = $colors['secondary_color'] ?? '#6b7280';
        $sidebarColor = $colors['sidebar_color'] ?? '#ffffff';
        $headerColor = $colors['header_color'] ?? '#ffffff';

        $primaryHover = $this->adjustBrightness($primaryColor, -20);
        $primaryLight = $this->hexToRgba($primaryColor, 0.1);
        $primary50 = $this->hexToRgba($primaryColor, 0.08);
        $sidebarHover = $this->hexToRgba($primaryColor, 0.06);
        $sidebarActiveBg = $this->hexToRgba($primaryColor, 0.1);

        return trim("
:root {
    --lp-primary: {$primaryColor};
    --lp-primary-hover: {$primaryHover};
    --lp-primary-light: {$primaryLight};
    --lp-primary-50: {$primary50};
    --lp-secondary: {$secondaryColor};
    --lp-sidebar-bg: {$sidebarColor};
    --lp-sidebar-active: {$primaryColor};
    --lp-sidebar-hover: {$sidebarHover};
    --lp-sidebar-active-bg: {$sidebarActiveBg};
    --bs-primary: {$primaryColor};
    --bs-primary-rgb: {$this->hexToRgbCsv($primaryColor)};
}
header.header { background: {$headerColor} !important; }
nav.sidebar { background: {$sidebarColor} !important; }
.btn-primary { background: {$primaryColor}; border-color: {$primaryColor}; }
.btn-primary:hover, .btn-primary:focus { background: {$primaryHover}; border-color: {$primaryHover}; }
");
    }

    /**
     * Adjust brightness of a hex color
     */
    private function adjustBrightness(string $hex, int $steps): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return '#' . $hex;
        }

        $r = max(0, min(255, hexdec(substr($hex, 0, 2)) + $steps));
        $g = max(0, min(255, hexdec(substr($hex, 2, 2)) + $steps));
        $b = max(0, min(255, hexdec(substr($hex, 4, 2)) + $steps));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Convert hex to rgba
     */
    private function hexToRgba(string $hex, float $alpha): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return "rgba(0, 0, 0, {$alpha})";
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return "rgba({$r}, {$g}, {$b}, {$alpha})";
    }

    private function hexToRgbCsv(string $hex): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return '31, 111, 235';
        }

        return hexdec(substr($hex, 0, 2)) . ', '
            . hexdec(substr($hex, 2, 2)) . ', '
            . hexdec(substr($hex, 4, 2));
    }
}
