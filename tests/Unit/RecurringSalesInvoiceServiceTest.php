<?php

namespace Tests\Unit;

use App\Services\RecurringSalesInvoiceService;
use Carbon\Carbon;
use Tests\TestCase;

class RecurringSalesInvoiceServiceTest extends TestCase
{
    public function test_calculates_next_weekly_run_date(): void
    {
        $service = app(RecurringSalesInvoiceService::class);

        $nextRun = $service->initialNextRunDate(Carbon::parse('2026-09-17'), 'weekly', 1);

        $this->assertSame('2026-09-21', $nextRun->toDateString());
    }

    public function test_calculates_monthly_first_last_and_custom_run_dates(): void
    {
        $service = app(RecurringSalesInvoiceService::class);

        $this->assertSame('2026-10-01', $service->initialNextRunDate(Carbon::parse('2026-09-17'), 'monthly', null, 'first_day')->toDateString());
        $this->assertSame('2026-09-30', $service->initialNextRunDate(Carbon::parse('2026-09-17'), 'monthly', null, 'last_day')->toDateString());
        $this->assertSame('2026-09-25', $service->initialNextRunDate(Carbon::parse('2026-09-17'), 'monthly', null, 'custom_day', 25)->toDateString());
        $this->assertSame('2026-10-01', $service->initialNextRunDate(Carbon::parse('2026-09-17'), 'monthly', null, 'custom_day', 1)->toDateString());
    }
}