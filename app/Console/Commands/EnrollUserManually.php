<?php

namespace App\Console\Commands;

use App\Support\Accelerator;
use App\Support\StudentProvisioner;
use Illuminate\Console\Command;

class EnrollUserManually extends Command
{
    /**
     * Usage: php artisan enroll:user {email} {name} {amount?} {currency=NGN}
     *
     * `amount` defaults to the configured pay-in-full price for the currency, so a price
     * change in config/accelerator.php never leaves a stale number typed here.
     */
    protected $signature = 'enroll:user {email} {name} {amount?} {currency=NGN} {--cohort=}';
    protected $description = 'Manually enroll a student who paid offline and trigger the welcome automation';

    public function handle(StudentProvisioner $provisioner): int
    {
        $email = $this->argument('email');
        $this->info("Initializing manual enrollment for: {$email}...");

        $currency = (string) $this->argument('currency');
        $amount = $this->argument('amount') !== null
            ? (float) $this->argument('amount')
            : Accelerator::regularFullPrice($currency);

        $result = $provisioner->manualEnrol([
            'name' => $this->argument('name'),
            'email' => $email,
            'amount' => $amount,
            'currency' => $currency,
            'cohort' => $this->option('cohort') !== null ? (int) $this->option('cohort') : (int) config('accelerator.cohort_number', 2),
        ]);

        $this->info('Enrollment created + welcome automation triggered.');

        if ($result['temp_password']) {
            $this->line("Temp password (share if the email doesn't arrive): {$result['temp_password']}");
        } else {
            $this->warn('User already existed — kept their current password.');
        }

        return self::SUCCESS;
    }
}
