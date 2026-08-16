<?php

namespace App\Providers;

use App\Interfaces\UserRepositoryInterface;
use App\Interfaces\RoleRepositoryInterface;
use App\Interfaces\PermissionRepositoryInterface;
use App\Interfaces\VoucherRepositoryInterface;
use App\Interfaces\VoucherLineRepositoryInterface;
use App\Interfaces\LedgerRepositoryInterface;
use App\Interfaces\LedgerPartyHistoryRepositoryInterface;
use App\Interfaces\AccountRepositoryInterface;
use App\Interfaces\SalesInvoiceRepositoryInterface;
use App\Interfaces\PurchaseInvoiceRepositoryInterface;
use App\Interfaces\PaymentInvoiceMappingRepositoryInterface;
use App\Repositories\UserRepository;
use App\Repositories\RoleRepository;
use App\Repositories\PermissionRepository;
use App\Repositories\VoucherRepository;
use App\Repositories\VoucherLineRepository;
use App\Repositories\LedgerRepository;
use App\Repositories\LedgerPartyHistoryRepository;
use App\Repositories\AccountRepository;
use App\Repositories\SalesInvoiceRepository;
use App\Repositories\PurchaseInvoiceRepository;
use App\Repositories\PaymentInvoiceMappingRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(RoleRepositoryInterface::class, RoleRepository::class);
        $this->app->bind(PermissionRepositoryInterface::class, PermissionRepository::class);
        $this->app->bind(VoucherRepositoryInterface::class, VoucherRepository::class);
        $this->app->bind(VoucherLineRepositoryInterface::class, VoucherLineRepository::class);
        $this->app->bind(LedgerRepositoryInterface::class, LedgerRepository::class);
        $this->app->bind(LedgerPartyHistoryRepositoryInterface::class, LedgerPartyHistoryRepository::class);
        $this->app->bind(AccountRepositoryInterface::class, AccountRepository::class);
        $this->app->bind(SalesInvoiceRepositoryInterface::class, SalesInvoiceRepository::class);
        $this->app->bind(PurchaseInvoiceRepositoryInterface::class, PurchaseInvoiceRepository::class);
        $this->app->bind(PaymentInvoiceMappingRepositoryInterface::class, PaymentInvoiceMappingRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
