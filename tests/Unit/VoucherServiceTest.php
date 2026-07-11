<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\VoucherService;
use App\Services\JournalEntryService;
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
            ->onlyMethods(['post', 'refresh', 'load'])
            ->getMock();

        $voucher->company_id = 1;
        $voucher->id = 123;
        $voucher->status = 'draft';
        $voucher->voucher_type = 'payment';
        $voucher->lines = collect([]);

        $voucher->expects($this->once())->method('post')->willReturn(true);
        $voucher->expects($this->once())->method('refresh');
        $voucher->method('load')->willReturn($voucher);

        $voucherRepo->method('find')->willReturn($voucher);

        $ledgerService = $this->createMock(LedgerService::class);
        $ledgerService->expects($this->once())->method('generateForVoucher')->with($voucher);

        $journalEntryService = $this->createMock(JournalEntryService::class);
        $journalEntryService->expects($this->once())->method('syncFromVoucher')->with($voucher);

        $service = new VoucherService($voucherRepo, $voucherLineRepo, $ledgerService, $journalEntryService);

        $result = $service->post(123);

        $this->assertTrue($result);
    }
}
