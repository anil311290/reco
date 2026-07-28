<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Authenticated smoke test for admin pages against current schema (sqlite :memory:).
 * Uses RefreshDatabase + minimal seed so pages that need FY/company still render.
 */
class AdminPagesSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->user = User::where('email', 'superadmin@reco.app')->first()
            ?? User::where('email', 'manager@ledgerpro.com')->first();

        $this->assertNotNull($this->user, 'Seeded admin user not found');
    }

    public function test_dashboard_and_core_modules_return_ok(): void
    {
        $paths = [
            '/admin/dashboard',
            '/admin/accounts',
            '/admin/accounts/create',
            '/admin/parties',
            '/admin/parties/create',
            '/admin/items',
            '/admin/items/create',
            '/admin/item-categories',
            '/admin/tax-rates',
            '/admin/vouchers',
            '/admin/vouchers/create/payment',
            '/admin/vouchers/create/receipt',
            '/admin/vouchers/create/journal',
            '/admin/sales-invoices',
            '/admin/sales-invoices/create',
            '/admin/purchase-invoices',
            '/admin/purchase-invoices/create',
            '/admin/financial-years',
            '/admin/settings',
            '/admin/reports',
            '/admin/reports/day-book',
            '/admin/reports/cash-book',
            '/admin/reports/bank-book',
            '/admin/reports/ledger',
            '/admin/reports/trial-balance',
            '/admin/reports/profit-loss',
            '/admin/reports/balance-sheet',
            '/admin/reports/debtors-outstanding',
            '/admin/reports/creditors-outstanding',
            '/admin/audit-logs',
        ];

        $failures = [];

        foreach ($paths as $path) {
            $response = $this->actingAs($this->user)->get($path);
            $status = $response->status();

            if (!in_array($status, [200, 302], true)) {
                $failures[] = "{$path} => HTTP {$status}";
            }
        }

        // Cash-flow legacy redirect
        $response = $this->actingAs($this->user)->get('/admin/reports/cash-flow');
        if (!in_array($response->status(), [301, 302], true)) {
            $failures[] = "/admin/reports/cash-flow => HTTP {$response->status()} (expected redirect)";
        }

        $this->assertEmpty(
            $failures,
            "Admin pages smoke failures:\n" . implode("\n", $failures)
        );
    }
}
