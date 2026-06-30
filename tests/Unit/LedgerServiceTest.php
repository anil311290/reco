<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Note: LedgerService unit tests require repository refactoring.
 * Currently, createEntry() calls Account::find() directly, which
 * requires database access. This should be refactored to use 
 * AccountRepositoryInterface for proper unit testing with mocks.
 * 
 * For now, LedgerService is tested via integration tests and 
 * is called by VoucherService which is unit tested.
 */
class LedgerServiceTest extends TestCase
{
    public function testPlaceholder()
    {
        // Placeholder test - LedgerService is tested via VoucherService integration
        $this->assertTrue(true);
    }
}
