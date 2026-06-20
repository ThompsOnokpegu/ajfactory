<?php

namespace App\Console\Commands;

use App\Support\StudentProvisioner;
use Illuminate\Console\Command;

class EnrollUserManually extends Command
{
    /**
     * Usage: php artisan enroll:user {email} {name} {amount=79000} {currency=NGN}
     */
    protected $signature = 'enroll:user {email} {name} {amount=79000} {currency=NGN} {--cohort=}';
    protected $description = 'Manually enroll a student who paid offline and trigger the welcome automation';

    public function handle(StudentProvisioner $provisioner): int
    {
        $email = $this->argument('email');
        $this->info("Initializing manual enrollment for: {$email}...");

        $result = $provisioner->manualEnrol([
            'name' => $this->argument('name'),
            'email' => $email,
            'amount' => (float) $this->argument('amount'),
            'currency' => $this->argument('currency'),
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
