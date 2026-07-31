<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\Item;
use App\Models\User;
use App\Services\ItemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ServiceItemUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_item_ignores_unit_on_create(): void
    {
        $company = Company::factory()->create();

        $service = app(ItemService::class)->create([
            'company_id' => $company->id,
            'name' => 'Consulting',
            'type' => 'service',
            'selling_price' => 500,
            'unit' => 'hrs',
        ]);

        $this->assertNull($service->unit);
        $this->assertDatabaseHas('items', [
            'id' => $service->id,
            'unit' => null,
        ]);
    }

    public function test_goods_item_keeps_unit(): void
    {
        $company = Company::factory()->create();

        $goods = app(ItemService::class)->create([
            'company_id' => $company->id,
            'name' => 'Widget',
            'type' => 'goods',
            'selling_price' => 100,
            'unit' => 'pcs',
        ]);

        $this->assertSame('pcs', $goods->unit);
    }

    public function test_service_item_unit_is_cleared_on_update(): void
    {
        $company = Company::factory()->create();
        $item = Item::create([
            'company_id' => $company->id,
            'name' => 'Installation',
            'type' => 'service',
            'selling_price' => 200,
        ]);

        app(ItemService::class)->update($item->id, [
            'name' => 'Installation Service',
            'type' => 'service',
            'unit' => 'job',
        ]);

        $this->assertNull($item->fresh()->unit);
    }

    public function test_service_item_api_create_does_not_store_unit(): void
    {
        $company = Company::factory()->create();
        FinancialYear::factory()->create([
            'company_id' => $company->id,
            'is_current' => true,
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/items', [
            'name' => 'Annual Maintenance',
            'type' => 'service',
            'selling_price' => 1200,
            'unit' => 'hrs',
        ])
            ->assertCreated()
            ->assertJsonPath('data.unit', null);
    }
}
