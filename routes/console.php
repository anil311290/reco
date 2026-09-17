<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('backup:send-links')->everyMinute();
Schedule::command('sales-invoices:recreate-recurring')->dailyAt('00:05')->withoutOverlapping();
