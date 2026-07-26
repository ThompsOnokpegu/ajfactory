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

// Masterclass: fire the reminder (Meet link + WhatsApp group), day-of nudge,
// and post-session Accelerator follow-up — each once, when due.
// Every 15 min (not hourly) so a single missed cron tick can't swallow a
// touch inside its 2-hour window. Each touch is idempotent, so extra ticks are safe.
Schedule::command('masterclass:remind')
    ->everyFifteenMinutes()
    ->timezone('Africa/Lagos');

// Masterclass: re-invite waitlisters + recent past registrants to register for
// the current session. Idempotent (one invite per person per session) and gated
// on registrationOpen, so a daily tick sends each lead exactly once per session
// and no-ops otherwise. Runs once registration is open for a new date.
Schedule::command('masterclass:announce')
    ->dailyAt('10:00')
    ->timezone('Africa/Lagos');
