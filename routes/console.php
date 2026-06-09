<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Installment plan: send 2nd-payment links on the due date and suspend overdue balances.
Schedule::command('installments:process')
    ->dailyAt('09:00')
    ->timezone('Africa/Lagos');
