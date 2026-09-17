<?php

namespace App\Console\Commands;

use App\Services\RecurringSalesInvoiceService;
use Illuminate\Console\Command;

class CreateRecurringSalesInvoices extends Command
{
    protected $signature = 'sales-invoices:recreate-recurring';

    protected $description = 'Create due recurring sales invoices';

    public function handle(RecurringSalesInvoiceService $recurringSalesInvoiceService): int
    {
        $createdCount = $recurringSalesInvoiceService->createDueInvoices();
        $this->info("Created {$createdCount} recurring sales invoice(s).");

        return self::SUCCESS;
    }
}