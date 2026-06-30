<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected SettingsService $settingsService;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->settingsService = $this->app->make(SettingsService::class);
    }

    public function test_can_create_setting(): void
    {
        $setting = Setting::create([
            'company_id' => $this->company->id,
            'group' => 'general',
            'key' => 'app_name',
            'value' => 'LedgerPro',
            'type' => 'text',
        ]);

        $this->assertInstanceOf(Setting::class, $setting);
        $this->assertEquals('LedgerPro', $setting->value);
    }

    public function test_can_get_setting_value(): void
    {
        Setting::create([
            'company_id' => $this->company->id,
            'group' => 'general',
            'key' => 'app_name',
            'value' => 'LedgerPro',
        ]);

        $value = Setting::getValue('app_name', null, $this->company->id);

        $this->assertEquals('LedgerPro', $value);
    }

    public function test_returns_default_when_setting_not_found(): void
    {
        $value = Setting::getValue('nonexistent', 'default', $this->company->id);

        $this->assertEquals('default', $value);
    }

    public function test_can_set_setting_value(): void
    {
        Setting::setValue('app_name', 'My App', $this->company->id, 'general');

        $this->assertDatabaseHas('settings', [
            'company_id' => $this->company->id,
            'key' => 'app_name',
            'value' => 'My App',
        ]);
    }

    public function test_can_update_existing_setting(): void
    {
        Setting::create([
            'company_id' => $this->company->id,
            'group' => 'general',
            'key' => 'app_name',
            'value' => 'Old Name',
        ]);

        Setting::setValue('app_name', 'New Name', $this->company->id, 'general');

        $this->assertEquals('New Name', Setting::getValue('app_name', null, $this->company->id));
    }

    public function test_can_get_settings_by_group(): void
    {
        Setting::create([
            'company_id' => $this->company->id,
            'group' => 'theme',
            'key' => 'primary_color',
            'value' => '#4f46e5',
        ]);

        Setting::create([
            'company_id' => $this->company->id,
            'group' => 'theme',
            'key' => 'secondary_color',
            'value' => '#6b7280',
        ]);

        $themeSettings = Setting::getByGroup('theme', $this->company->id);

        $this->assertCount(2, $themeSettings);
        $this->assertEquals('#4f46e5', $themeSettings['primary_color']);
    }

    public function test_can_update_company_settings(): void
    {
        $data = [
            'company_name' => 'Updated Company',
            'company_email' => 'updated@example.com',
            'company_currency' => 'USD',
            'company_timezone' => 'America/New_York',
            'financial_year_start' => '01-01',
            'financial_year_end' => '12-31',
        ];

        $result = $this->settingsService->updateCompanySettings($data, $this->company->id);

        $this->assertTrue($result);
        $this->assertEquals('Updated Company', $this->company->fresh()->name);
    }

    public function test_can_update_theme_settings(): void
    {
        $data = [
            'primary_color' => '#ff0000',
            'secondary_color' => '#00ff00',
            'sidebar_color' => '#0000ff',
            'header_color' => '#ffffff',
            'dark_mode' => '1',
        ];

        $result = $this->settingsService->updateThemeSettings($data, $this->company->id);

        $this->assertTrue($result);
        $this->assertEquals('#ff0000', Setting::getValue('theme.primary_color', null, $this->company->id));
    }

    public function test_can_generate_theme_css(): void
    {
        Setting::setValue('theme.primary_color', '#4f46e5', $this->company->id, 'theme');
        Setting::setValue('theme.sidebar_color', '#1e1b4b', $this->company->id, 'theme');

        $css = $this->settingsService->getThemeCss($this->company->id);

        $this->assertStringContainsString('--lp-primary:', $css);
        $this->assertStringContainsString('#4f46e5', $css);
    }

    public function test_can_get_all_settings_as_key_value(): void
    {
        Setting::create([
            'company_id' => $this->company->id,
            'group' => 'general',
            'key' => 'setting1',
            'value' => 'value1',
        ]);

        Setting::create([
            'company_id' => $this->company->id,
            'group' => 'general',
            'key' => 'setting2',
            'value' => 'value2',
        ]);

        $allSettings = Setting::getAll($this->company->id);

        $this->assertCount(2, $allSettings);
        $this->assertEquals('value1', $allSettings['setting1']);
    }

    public function test_settings_are_scoped_to_company(): void
    {
        $company2 = Company::factory()->create();

        Setting::create([
            'company_id' => $this->company->id,
            'group' => 'general',
            'key' => 'app_name',
            'value' => 'Company 1 App',
        ]);

        Setting::create([
            'company_id' => $company2->id,
            'group' => 'general',
            'key' => 'app_name',
            'value' => 'Company 2 App',
        ]);

        $value1 = Setting::getValue('app_name', null, $this->company->id);
        $value2 = Setting::getValue('app_name', null, $company2->id);

        $this->assertEquals('Company 1 App', $value1);
        $this->assertEquals('Company 2 App', $value2);
    }
}
