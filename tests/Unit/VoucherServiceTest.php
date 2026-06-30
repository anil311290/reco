<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\VoucherService;
use App\Interfaces\VoucherRepositoryInterface;
use App\Interfaces\VoucherLineRepositoryInterface;
use App\Services\LedgerService;

class VoucherServiceTest extends TestCase
{
    public function testPostCallsLedgerServiceWhenPosted()
    {
        $voucherRepo = $this->createMock(VoucherRepositoryInterface::class);
        $voucherLineRepo = $this->createMock(VoucherLineRepositoryInterface::class);

        $voucher = $this->getMockBuilder(\App\Models\Voucher::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['post', 'refresh'])
            ->getMock();

        $voucher->company_id = 1;
        $voucher->id = 123;
        $voucher->status = 'draft';
        $voucher->lines = collect([]);

        $voucher->expects($this->once())->method('post')->willReturn(true);
        $voucher->expects($this->once())->method('refresh');

        $voucherRepo->method('find')->willReturn($voucher);

        $ledgerService = $this->createMock(LedgerService::class);
        $ledgerService->expects($this->once())->method('generateForVoucher')->with($voucher);

        $service = new VoucherService($voucherRepo, $voucherLineRepo, $ledgerService);

        $result = $service->post(123);

        $this->assertTrue($result);
    }
}
