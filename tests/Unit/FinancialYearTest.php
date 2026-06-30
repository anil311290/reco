<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\FinancialYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialYearTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
    }

    public function test_can_create_financial_year(): void
    {
        $fy = FinancialYear::create([
            'company_id' => $this->company->id,
            'name' => '2025-2026',
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31',
            'is_current' => true,
        ]);

        $this->assertInstanceOf(FinancialYear::class, $fy);
        $this->assertEquals('2025-2026', $fy->name);
        $this->assertTrue($fy->is_current);
    }

    public function test_can_set_financial_year_as_current(): void
    {
        $fy1 = FinancialYear::factory()->create([
            'company_id' => $this->company->id,
            'is_current' => true,
        ]);

        $fy2 = FinancialYear::factory()->create([
            'company_id' => $this->company->id,
            'is_current' => false,
        ]);

        $fy2->setAsCurrent();

        $this->assertTrue($fy2->fresh()->is_current);
        $this->assertFalse($fy1->fresh()->is_current);
    }

    public function test_can_close_financial_year(): void
    {
        $fy = FinancialYear::factory()->create([
            'company_id' => $this->company->id,
            'is_current' => true,
            'is_closed' => false,
        ]);

        $fy->close();

        $this->assertTrue($fy->fresh()->is_closed);
        $this->assertFalse($fy->fresh()->is_current);
        $this->assertNotNull($fy->fresh()->closed_at);
    }

    public function test_can_get_current_financial_year(): void
    {
        FinancialYear::factory()->create([
            'company_id' => $this->company->id,
            'is_current' => false,
        ]);

        $current = FinancialYear::factory()->create([
            'company_id' => $this->company->id,
            'is_current' => true,
        ]);

        $result = FinancialYear::getCurrent($this->company->id);

        $this->assertNotNull($result);
        $this->assertEquals($current->id, $result->id);
    }

    public function test_can_scope_open_financial_years(): void
    {
        FinancialYear::factory()->create([
            'company_id' => $this->company->id,
            'is_closed' => true,
        ]);

        FinancialYear::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'is_closed' => false,
        ]);

        $openYears = FinancialYear::open()->get();

        $this->assertCount(2, $openYears);
    }

    public function test_can_scope_closed_financial_years(): void
    {
        FinancialYear::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'is_closed' => true,
        ]);

        FinancialYear::factory()->create([
            'company_id' => $this->company->id,
            'is_closed' => false,
        ]);

        $closedYears = FinancialYear::closed()->get();

        $this->assertCount(2, $closedYears);
    }

    public function test_financial_year_belongs_to_company(): void
    {
        $fy = FinancialYear::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->assertNotNull($fy->company);
        $this->assertEquals($this->company->id, $fy->company->id);
    }

    public function test_financial_year_dates_are_cast(): void
    {
        $fy = FinancialYear::factory()->create([
            'company_id' => $this->company->id,
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $fy->start_date);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $fy->end_date);
    }
}
