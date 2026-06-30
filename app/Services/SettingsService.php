<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Company;
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
                'theme.primary_color' => $data['primary_color'] ?? '#4f46e5',
                'theme.secondary_color' => $data['secondary_color'] ?? '#6b7280',
                'theme.sidebar_color' => $data['sidebar_color'] ?? '#1e1b4b',
                'theme.header_color' => $data['header_color'] ?? '#ffffff',
                'theme.dark_mode' => $data['dark_mode'] ?? '0',
            ];

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

            $settings = [
                'sales_tax_ledger_id' => $data['sales_tax_ledger_id'] ?? null,
                'purchase_tax_ledger_id' => $data['purchase_tax_ledger_id'] ?? null,
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

    /**
     * Get theme CSS variables
     */
    public function getThemeCss(?int $companyId = null): string
    {
        $primaryColor = Setting::getValue('theme.primary_color', '#7367f0', $companyId);
        $secondaryColor = Setting::getValue('theme.secondary_color', '#a8aaae', $companyId);
        $sidebarColor = Setting::getValue('theme.sidebar_color', '#ffffff', $companyId);
        $headerColor = Setting::getValue('theme.header_color', '#ffffff', $companyId);

        $css = "
            :root {
                --lp-primary: {$primaryColor};
                --lp-primary-hover: {$this->adjustBrightness($primaryColor, -20)};
                --lp-primary-light: {$this->hexToRgba($primaryColor, 0.1)};
                --lp-primary-50: {$this->hexToRgba($primaryColor, 0.08)};
                --lp-sidebar-bg: {$sidebarColor};
            }
            header.header { background: {$headerColor}; }
        ";

        return $css;
    }

    /**
     * Adjust brightness of a hex color
     */
    private function adjustBrightness(string $hex, int $steps): string
    {
        $hex = ltrim($hex, '#');
        
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
        
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return "rgba({$r}, {$g}, {$b}, {$alpha})";
    }
}
